<?php

namespace App\Services\Media;

use App\Jobs\ProcessListingPhoto;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\User;
use App\Services\Listings\DraftConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ListingPhotos
{
    public function __construct(private ListingImageProcessor $processor, private FileCleanup $cleanup) {}

    public function upload(User $actor, Listing $draft, array $files, int $version): int
    {
        $files = array_values($files);
        $metadata = array_map(fn ($file) => $this->processor->inspect($file), $files);
        $photos = DB::transaction(function () use ($actor, $draft, $files, $metadata, $version) {
            $listing = $this->locked($actor, $draft, $version);
            $count = ListingPhoto::where('listing_id', $listing->id)->count();
            if ($count + count($files) > 8) {
                throw ValidationException::withMessages(['photos' => __('photo.limit')]);
            }
            $records = [];
            foreach ($metadata as $index => $data) {
                $id = (string) Str::uuid();
                $photo = new ListingPhoto;
                $photo->forceFill([...$data, 'public_id' => $id, 'listing_id' => $listing->id, 'position' => $count + $index, 'status' => 'uploading', 'storage_prefix' => 'listings/'.$listing->public_id.'/'.$id])->save();
                $records[] = $photo;
            }
            $this->touchDraft($listing);

            return $records;
        });
        $failed = 0;
        foreach ($photos as $index => $photo) {
            try {
                $queued = DB::transaction(function () use ($photo, $files, $index) {
                    $current = ListingPhoto::whereKey($photo->id)->lockForUpdate()->first();
                    if (! $current || $current->status !== 'uploading') {
                        return false;
                    }
                    $stream = fopen($files[$index]->getRealPath(), 'rb');
                    try {
                        if (! Storage::disk('s3')->put($current->sourcePath(), $stream, ['visibility' => 'private', 'ContentType' => 'application/octet-stream'])) {
                            throw new \RuntimeException('Source upload failed.');
                        }
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                    $current->forceFill(['status' => 'queued'])->save();

                    return true;
                });
                if ($queued) {
                    $this->dispatch($photo->id);
                }
            } catch (\Throwable $exception) {
                Log::warning('Listing source upload failed.', ['photo_id' => $photo->id]);
                $this->fail($photo->id, 'uploading');
                $failed++;
            }
        }

        return $failed;
    }

    public function dispatch(int $photoId): void
    {
        try {
            ProcessListingPhoto::dispatch($photoId);
            ListingPhoto::whereKey($photoId)->where('status', 'queued')->update(['dispatched_at' => now()]);
        } catch (\Throwable $exception) {
            // The queued record is a durable outbox; the scheduler retries dispatch.
            Log::warning('Listing image dispatch will retry.', ['photo_id' => $photoId]);
        }
    }

    public function arrange(User $actor, Listing $draft, ListingPhoto $photo, string $action, int $version): void
    {
        DB::transaction(function () use ($actor, $draft, $photo, $action, $version) {
            $listing = $this->locked($actor, $draft, $version);
            $photos = ListingPhoto::where('listing_id', $listing->id)->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            $index = $photos->search(fn ($item) => $item->id === $photo->id);
            abort_if($index === false, 404);
            $target = match ($action) {
                'primary' => 0, 'left' => max(0, $index - 1), 'right' => min($photos->count() - 1, $index + 1), default => throw new \InvalidArgumentException('Unknown photo action.')
            };
            $ordered = $photos->all();
            $moved = array_splice($ordered, $index, 1);
            array_splice($ordered, $target, 0, $moved);
            foreach ($ordered as $position => $item) {
                $item->forceFill(['position' => $position])->save();
            }
            $this->touchDraft($listing);
        });
    }

    public function remove(User $actor, Listing $draft, ListingPhoto $photo, int $version): void
    {
        $retired = DB::transaction(function () use ($actor, $draft, $photo, $version) {
            $listing = $this->locked($actor, $draft, $version);
            $current = ListingPhoto::whereKey($photo->id)->where('listing_id', $listing->id)->lockForUpdate()->firstOrFail();
            $ids = $this->retire($current);
            $current->delete();
            $remaining = ListingPhoto::where('listing_id', $listing->id)->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            foreach ($remaining as $position => $item) {
                $item->forceFill(['position' => $position])->save();
            }
            $this->touchDraft($listing);

            return $ids;
        });
        foreach ($retired as $id) {
            $this->cleanup->remove($id);
        }
    }

    public function fail(int $photoId, string $expected = 'queued'): void
    {
        $retired = DB::transaction(function () use ($photoId, $expected) {
            $photo = ListingPhoto::whereKey($photoId)->lockForUpdate()->first();
            if (! $photo || $photo->status !== $expected) {
                return [];
            }
            $photo->forceFill(['status' => 'failed'])->save();

            return $this->retire($photo);
        });
        foreach ($retired as $id) {
            $this->cleanup->remove($id);
        }
    }

    public function retire(ListingPhoto $photo): array
    {
        return array_map(fn ($path) => $this->cleanup->remember($path)->id, $photo->paths());
    }

    private function locked(User $actor, Listing $draft, int $version): Listing
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        $listing = Listing::whereKey($draft->id)->lockForUpdate()->firstOrFail();
        Gate::forUser($user)->authorize('updateDraft', $listing);
        if ($listing->version !== $version) {
            throw new DraftConflict;
        }

        return $listing;
    }

    private function touchDraft(Listing $listing): void
    {
        $listing->forceFill(['version' => $listing->version + 1, 'last_step' => 'photos'])->save();
    }
}

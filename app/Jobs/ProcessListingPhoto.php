<?php

namespace App\Jobs;

use App\Models\ListingPhoto;
use App\Services\Media\FileCleanup;
use App\Services\Media\ListingImageProcessor;
use App\Services\Media\ListingPhotos;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessListingPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 80;

    public bool $failOnTimeout = true;

    public function __construct(public int $photoId) {}

    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(ListingImageProcessor $processor, FileCleanup $cleanup): void
    {
        $retired = DB::transaction(function () use ($processor, $cleanup) {
            // Holding the photo row serializes duplicate jobs and deletion with storage writes.
            $photo = ListingPhoto::whereKey($this->photoId)->lockForUpdate()->first();
            if (! $photo || $photo->status !== 'queued') {
                return null;
            }
            $variants = $processor->process($photo);
            $photo->forceFill(['status' => 'ready', 'variants' => $variants])->save();

            return $cleanup->remember($photo->sourcePath())->id;
        });
        if ($retired) {
            $cleanup->remove($retired);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Listing image processing failed.', ['photo_id' => $this->photoId]);
        app(ListingPhotos::class)->fail($this->photoId);
    }
}

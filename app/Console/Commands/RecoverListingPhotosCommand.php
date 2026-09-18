<?php

namespace App\Console\Commands;

use App\Models\ListingPhoto;
use App\Services\Media\ListingPhotos;
use Illuminate\Console\Command;

class RecoverListingPhotosCommand extends Command
{
    protected $signature = 'media:recover-listings';

    protected $description = 'Recover a bounded batch of interrupted listing uploads and undispatched image jobs';

    public function handle(ListingPhotos $photos): int
    {
        $stale = ListingPhoto::where('status', 'uploading')->where('updated_at', '<', now()->subMinutes(15))->orderBy('id')->limit(25)->pluck('id');
        foreach ($stale as $id) {
            $photos->fail($id, 'uploading');
        }
        $queued = ListingPhoto::where('status', 'queued')->where('updated_at', '<', now()->subMinutes(2))
            ->where(fn ($query) => $query->whereNull('dispatched_at')->orWhere('dispatched_at', '<', now()->subMinutes(5)))
            ->orderBy('id')->limit(25)->pluck('id');
        foreach ($queued as $id) {
            $photos->dispatch($id);
        }
        $this->info('Recovered '.$stale->count().' interrupted uploads; dispatched '.$queued->count().' image jobs.');

        return self::SUCCESS;
    }
}

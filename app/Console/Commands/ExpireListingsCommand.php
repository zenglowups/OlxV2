<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\Listings\ListingLifecycle;
use Illuminate\Console\Command;

class ExpireListingsCommand extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Expire a bounded batch of listings whose public lifetime has ended';

    public function handle(ListingLifecycle $lifecycle): int
    {
        $due = Listing::whereIn('status', ['active', 'reserved'])->where('expires_at', '<=', now())->orderBy('expires_at')->orderBy('id')->limit(100)->get(['id', 'user_id']);
        $expired = 0;
        foreach ($due as $listing) {
            $expired += (int) $lifecycle->expire($listing->id, $listing->user_id);
        }
        $this->info('Expired '.$expired.' listings.');

        return self::SUCCESS;
    }
}

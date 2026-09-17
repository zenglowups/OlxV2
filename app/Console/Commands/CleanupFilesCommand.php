<?php

namespace App\Console\Commands;

use App\Models\PendingFileDeletion;
use App\Services\Media\FileCleanup;
use Illuminate\Console\Command;

class CleanupFilesCommand extends Command
{
    protected $signature = 'media:cleanup';

    protected $description = 'Retry a bounded batch of explicitly retired objects';

    public function handle(FileCleanup $cleanup): int
    {
        $ids = PendingFileDeletion::where('available_at', '<=', now())->orderBy('id')->limit(50)->pluck('id');
        foreach ($ids as $id) {
            $cleanup->remove($id);
        }
        $this->info('Processed '.$ids->count().' pending object deletions.');

        return self::SUCCESS;
    }
}

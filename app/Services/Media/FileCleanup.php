<?php

namespace App\Services\Media;

use App\Models\PendingFileDeletion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileCleanup
{
    public function remember(string $path): PendingFileDeletion
    {
        return PendingFileDeletion::firstOrCreate(['path' => $path], ['disk' => 's3', 'available_at' => now()]);
    }

    public function remove(int $id): void
    {
        DB::transaction(function () use ($id) {
            $record = PendingFileDeletion::whereKey($id)->lockForUpdate()->first();
            if (! $record || $record->available_at->isFuture()) {
                return;
            }
            try {
                if (! Storage::disk($record->disk)->delete($record->path)) {
                    throw new \RuntimeException('Object deletion failed.');
                }
                $record->delete();
            } catch (\Throwable $exception) {
                $record->update(['attempts' => $record->attempts + 1, 'available_at' => now()->addMinutes(10)]);
                Log::warning('Object cleanup will retry.', ['cleanup_id' => $record->id]);
            }
        });
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReadinessChecker
{
    /** @return array<string, bool> */
    public function check(): array
    {
        $checks = [
            'database' => function (): void {
                DB::select('SELECT 1');
            },
            'redis' => function (): void {
                $key = 'readiness:'.Str::uuid();
                Cache::store('redis')->put($key, 'ok', 10);
                try {
                    if (Cache::store('redis')->get($key) !== 'ok') {
                        throw new \RuntimeException('Cache round-trip failed.');
                    }
                } finally {
                    Cache::store('redis')->forget($key);
                }
            },
            'storage' => function (): void {
                $disk = Storage::disk('s3');
                $disk->getClient()->headBucket(['Bucket' => config('filesystems.disks.s3.bucket')]);
            },
        ];
        $results = [];
        foreach ($checks as $name => $check) {
            try {
                $check();
                $results[$name] = true;
            } catch (Throwable) {
                $results[$name] = false;
            }
        }

        return $results;
    }

    public function ready(): bool
    {
        return ! in_array(false, $this->check(), true);
    }
}

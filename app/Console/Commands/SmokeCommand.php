<?php

namespace App\Console\Commands;

use App\Jobs\InfrastructureProbe;
use App\Services\ReadinessChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SmokeCommand extends Command
{
    protected $signature = 'app:smoke';

    protected $description = 'Local-only database, S3, Redis queue and Mailpit smoke check';

    public function handle(ReadinessChecker $checker): int
    {
        if (! app()->environment('local') || config('mail.mailers.smtp.host') !== 'mailpit'
            || config('queue.default') !== 'redis') {
            $this->error('This command requires the local Compose environment and Mailpit.');

            return self::FAILURE;
        }
        if (! $checker->ready()) {
            $this->error('Readiness checks failed. Run app:ready.');

            return self::FAILURE;
        }
        $schedulerDeadline = microtime(true) + 65;
        do {
            $heartbeat = Cache::store('redis')->get('scheduler:heartbeat');
            if ($heartbeat) {
                break;
            }
            usleep(250000);
        } while (microtime(true) < $schedulerDeadline);
        if (! $heartbeat || now()->diffInSeconds(Carbon::parse($heartbeat), true) > 180) {
            $this->error('Scheduler heartbeat missing or stale. Allow one minute after startup.');

            return self::FAILURE;
        }
        $id = (string) Str::uuid();
        DB::beginTransaction();
        try {
            DB::table('cache')->insert(['key' => 'smoke:'.$id, 'value' => 'probe', 'expiration' => time() + 60]);
            if (! DB::table('cache')->where('key', 'smoke:'.$id)->exists()) {
                throw new \RuntimeException('Database write/read failed.');
            }
        } finally {
            DB::rollBack();
        }
        $disk = Storage::disk('s3');
        $path = 'infrastructure/smoke-'.$id.'.txt';
        try {
            $disk->put($path, $id);
            if ($disk->get($path) !== $id) {
                throw new \RuntimeException('Object storage round-trip failed.');
            }
        } finally {
            $disk->delete($path);
        }
        InfrastructureProbe::dispatch($id);
        $deadline = microtime(true) + 25;
        do {
            if (Cache::store('redis')->pull('smoke:'.$id) === 'completed') {
                $this->info('PASS: PostgreSQL write/rollback, private S3 write/read/delete, Redis queue, scheduler heartbeat and Mailpit SMTP.');
                $this->line('Mailpit subject: Infrastructure probe '.$id);

                return self::SUCCESS;
            }
            usleep(250000);
        } while (microtime(true) < $deadline);
        $this->error('Queue probe timed out. Inspect queue logs and queue:failed.');

        return self::FAILURE;
    }
}

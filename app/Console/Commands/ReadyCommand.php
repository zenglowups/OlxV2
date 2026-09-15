<?php

namespace App\Console\Commands;

use App\Services\ReadinessChecker;
use Illuminate\Console\Command;

class ReadyCommand extends Command
{
    protected $signature = 'app:ready';

    protected $description = 'Check PostgreSQL, Redis, and the private S3 bucket';

    public function handle(ReadinessChecker $checker): int
    {
        $checks = $checker->check();
        foreach ($checks as $name => $ok) {
            $this->line($name.': '.($ok ? 'OK' : 'FAILED'));
        }

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}

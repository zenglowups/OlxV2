<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class InfrastructureProbe implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $id) {}

    public function handle(): void
    {
        // This job is only dispatched by the local infrastructure smoke command.
        if (! app()->environment('local', 'testing')) {
            return;
        }
        Mail::raw('Local infrastructure check. No customer data.', function (Message $message): void {
            $message->to('smoke@example.test')->subject('Infrastructure probe '.$this->id);
        });
        Cache::store('redis')->put('smoke:'.$this->id, 'completed', 300);
    }
}

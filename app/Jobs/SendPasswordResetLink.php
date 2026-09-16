<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        // Resolve existence in the worker so request timing does not disclose accounts.
        $user = User::where('email', $this->email)->first();
        if ($user?->canAuthenticate()) {
            Password::sendResetLink(['email' => $this->email]);
        }
    }
}

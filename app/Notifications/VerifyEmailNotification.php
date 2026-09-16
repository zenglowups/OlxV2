<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    protected function verificationUrl($notifiable): string
    {
        return rtrim(config('app.url'), '/').URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'locale' => $notifiable->preferredLocale(),
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ], absolute: false);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject(__('auth.verify_subject'))
            ->greeting(__('auth.hello', ['name' => $notifiable->name]))
            ->line(__('auth.verify_body'))->action(__('auth.verify_action'), $this->verificationUrl($notifiable))
            ->line(__('auth.verify_expiry'))->line(__('auth.ignore_mail'));
    }

    public function shouldSend($notifiable, string $channel): bool
    {
        return ! $notifiable->hasVerifiedEmail() && $notifiable->canAuthenticate();
    }
}

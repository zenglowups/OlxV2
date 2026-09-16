<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct($token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['locale' => $notifiable->preferredLocale(), 'token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)->subject(__('auth.reset_subject'))
            ->greeting(__('auth.hello', ['name' => $notifiable->name]))
            ->line(__('auth.reset_body'))->action(__('auth.reset_action'), $url)
            ->line(__('auth.reset_expiry', ['minutes' => config('auth.passwords.users.expire')]))
            ->line(__('auth.ignore_mail'));
    }

    public function shouldSend($notifiable, string $channel): bool
    {
        return $notifiable->canAuthenticate();
    }
}

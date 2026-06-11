<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword $notifiable): MailMessage
    {
        $name = $notifiable->name ?? $notifiable->username ?? 'there';
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->from($this->fromAddress(), 'BotHost Pro')
            ->subject('Reset Your BotHost Pro Password')
            ->greeting('Hello '.$name.',')
            ->line('We received a request to reset your BotHost Pro account password.')
            ->action('Reset Password', $this->resetUrl($notifiable))
            ->line("This password reset link expires in {$expire} minutes.")
            ->line('If you did not request this, you can safely ignore this email.');
    }

    private function resetUrl(CanResetPassword $notifiable): string
    {
        return route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    private function fromAddress(): string
    {
        return (string) config('mail.from.address', 'hello@example.com');
    }
}

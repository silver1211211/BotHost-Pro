<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(MustVerifyEmail $notifiable): MailMessage
    {
        $expire = (int) config('auth.verification.expire', 5);

        return (new MailMessage)
            ->from($this->fromAddress(), 'BotHost Pro')
            ->subject('Verify Your BotHost Pro Email')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Please verify your email address to finish setting up your BotHost Pro account.')
            ->action('Verify Email Address', $this->verificationUrl($notifiable, $expire))
            ->line('This email verification link expires in '.$expire.' minutes.')
            ->line('If you did not create this account, you can safely ignore this email.');
    }

    private function verificationUrl(MustVerifyEmail $notifiable, int $expire): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expire),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'token' => $this->token,
            ],
        );
    }

    private function fromAddress(): string
    {
        return (string) config('mail.from.address', 'hello@example.com');
    }
}

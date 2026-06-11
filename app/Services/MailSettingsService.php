<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class MailSettingsService
{
    public function __construct(private readonly PlatformSettingsService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->boolean('mail_enabled', false);
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return [
            'mail_enabled' => $this->enabled(),
            'mail_mailer' => (string) $this->settings->get('mail_mailer', 'smtp'),
            'mail_host' => (string) $this->settings->get('mail_host', 'smtp.gmail.com'),
            'mail_port' => (int) $this->settings->get('mail_port', 587),
            'mail_username' => (string) $this->settings->get('mail_username', ''),
            'mail_password' => (string) $this->settings->get('mail_password', ''),
            'mail_password_masked' => PlatformSetting::maskedValue('mail_password'),
            'mail_encryption' => $this->normalizedEncryption($this->settings->get('mail_encryption', 'tls')),
            'mail_from_address' => (string) $this->settings->get('mail_from_address', ''),
            'mail_from_name' => (string) $this->settings->get('mail_from_name', 'BotHost Pro'),
            'mail_test_recipient' => (string) $this->settings->get('mail_test_recipient', ''),
            'admin_notification_email' => (string) $this->settings->get('admin_notification_email', $this->settings->get('admin_alert_email', '')),
        ];
    }

    public function apply(): void
    {
        $settings = $this->settings();
        $mailer = $settings['mail_mailer'] ?: 'smtp';

        Config::set('mail.default', $mailer);
        Config::set("mail.mailers.{$mailer}.transport", $mailer);

        Config::set('mail.mailers.smtp.host', $settings['mail_host']);
        Config::set('mail.mailers.smtp.port', $settings['mail_port']);
        Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?: null);
        Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?: null);
        Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption']);
        Config::set('mail.from.address', $settings['mail_from_address'] ?: Config::get('mail.from.address'));
        Config::set('mail.from.name', $settings['mail_from_name'] ?: 'BotHost Pro');
    }

    public function adminRecipient(): ?string
    {
        $recipient = (string) $this->settings->get('admin_notification_email', $this->settings->get('admin_alert_email', ''));

        if (filled($recipient)) {
            return $recipient;
        }

        $supportEmail = (string) $this->settings->get('support_email', '');
        if (filled($supportEmail)) {
            return $supportEmail;
        }

        return User::query()->where('role', 'admin')->whereNotNull('email')->oldest('id')->value('email');
    }

    private function normalizedEncryption(mixed $value): ?string
    {
        $value = strtolower((string) $value);

        return in_array($value, ['tls', 'ssl'], true) ? $value : null;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DockerRuntimeService;
use App\Services\MailSettingsService;
use App\Services\OxaPayService;
use App\Services\PlatformSettingsService;
use App\Services\RuntimeSettingsService;
use App\Services\TelegramWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
use Illuminate\Http\UploadedFile;
use App\Support\PublicCallbackUrl;
use App\Support\Branding;

class SettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly AuditLogService $audit,
        private readonly MailSettingsService $mailSettings,
        private readonly RuntimeSettingsService $runtimeSettings,
    ) {}

    public function index(Request $request): View
    {
        $s      = $this->settings;
        $oxaPay = app(OxaPayService::class);

        $mail = $this->mailSettings->settings();
        $runtime = $this->runtimeSettings->all();
        $runtimeDocker = app(DockerRuntimeService::class);
        $runtimeDockerSummary = $runtimeDocker->activeContainerSummary();

        $appPublicUrl = PublicCallbackUrl::base();

        return view('admin.settings.index', [
            'tab' => $request->query('tab', 'general'),

            // General
            'platformName'    => $s->get('platform_name', config('app.name', 'BotHost Pro')),
            'appUrl'          => rtrim((string) config('app.url'), '/'),
            'appPublicUrl'    => $appPublicUrl,
            'supportEmail'    => $s->get('support_email', ''),
            'defaultCurrency' => $s->get('default_currency', 'USD'),
            'platformMode'    => $s->get('platform_mode', 'live'),

            // Branding
            'primaryColor'  => $s->get('platform_primary_color', '#8B5CF6'),
            'accentColor'   => $s->get('platform_accent_color', '#38BDF8'),
            'loginTitle'    => $s->get('login_page_title', ''),
            'loginSubtitle' => $s->get('login_page_subtitle', ''),
            'footerText'    => $s->get('footer_text', ''),
            'platformLogoUrl' => Branding::platformLogoUrl(),
            'faviconUrl'      => Branding::faviconUrl(),
            'adminLogoUrl'    => Branding::adminLogoUrl(),

            // Payments
            'maskedApiKey'       => PlatformSetting::maskedValue('oxapay_merchant_api_key'),
            'oxaPayBaseUrl'      => $oxaPay->baseUrl(),
            'oxaPayEnabled'      => $oxaPay->enabled(),
            'oxaPaySandbox'      => $oxaPay->sandbox(),
            'feePaidByUser'      => $oxaPay->feePaidByUser(),
            'invoiceLifetime'    => $oxaPay->invoiceLifetime(),
            'underPaidCoverage'  => $s->get('oxapay_under_paid_coverage', ''),
            'oxaPayWebhookUrl'   => $oxaPay->webhookUrl(),
            'publicCallbackUrl'  => $oxaPay->publicCallbackBaseUrl(),
            'providerConfigured' => filled($oxaPay->merchantApiKey()),

            // Trigger Webhooks
            'triggerWebhooksEnabled'  => $s->boolean('trigger_webhooks_enabled', true),
            'triggerPaymentSuccess'   => $s->boolean('trigger_webhook_payment_success', true),
            'triggerTemplatePurchase' => $s->boolean('trigger_webhook_template_purchase', true),
            'triggerPlanUpgrade'      => $s->boolean('trigger_webhook_plan_upgrade', true),
            'triggerBotCreated'       => $s->boolean('trigger_webhook_bot_created', true),
            'triggerCommandError'     => $s->boolean('trigger_webhook_command_error', false),

            // Storage
            'storageDisk'             => $s->get('storage_default_disk', 'local'),
            'storageTrackingEnabled'  => $s->boolean('storage_tracking_enabled', true),
            'clearBotStorageOnDelete' => $s->boolean('clear_bot_storage_on_delete', true),
            'storageWarningThreshold' => $s->get('storage_warning_threshold_percent', '80'),
            'storageCriticalThreshold'=> $s->get('storage_critical_threshold_percent', '95'),

            // Security
            'requireEmailVerification' => $s->boolean('require_email_verification', false),
            'allowRegistration'        => $this->registrationEnabled(),
            'sessionTimeoutMinutes'    => $s->get('session_timeout_minutes', ''),
            'maxLoginAttempts'         => $s->get('max_login_attempts', '5'),
            'lockoutMinutes'           => $s->get('lockout_minutes', '1'),
            'adminMaintenanceAccess'   => $s->boolean('admin_maintenance_access', true),
            'maintenanceAllowedIps'     => $s->get('maintenance_allowed_ips', ''),
            'currentAdminIp'            => $request->ip(),

            // Notifications
            'notifyPaymentEvents'   => $s->boolean('notify_payment_events', true),
            'notifyNewUserSignup'   => $s->boolean('notify_new_user_signup', false),
            'notifyTemplateEvents'  => $s->boolean('notify_template_events', true),
            'notifyPlanEvents'      => $s->boolean('notify_plan_events', true),
            'notifyBotErrors'       => $s->boolean('notify_bot_errors', false),
            'notifyStorageWarnings' => $s->boolean('notify_storage_warnings', true),
            'adminAlertEmail'       => $mail['admin_notification_email'],
            'adminNotificationEmail'=> $mail['admin_notification_email'],
            'mailEnabled'           => $mail['mail_enabled'],
            'mailMailer'            => $mail['mail_mailer'],
            'mailHost'              => $mail['mail_host'],
            'mailPort'              => $mail['mail_port'],
            'mailUsername'          => $mail['mail_username'],
            'mailPassword'          => $mail['mail_password_masked'],
            'mailEncryption'        => $mail['mail_encryption'] ?? '',
            'mailFromAddress'       => $mail['mail_from_address'],
            'mailFromName'          => $mail['mail_from_name'],
            'mailTestRecipient'     => $mail['mail_test_recipient'],

            // Automations
            'automationProcessBroadcasts' => $s->boolean('automation_process_broadcasts_enabled', true),
            'automationPruneLogs'        => $s->boolean('automation_prune_logs_enabled', true),
            'automationExpireInvoices'   => $s->boolean('automation_expire_invoices_enabled', true),
            'automationCleanUploads'     => $s->boolean('automation_clean_temp_uploads_enabled', true),
            'automationCheckPayments'    => $s->boolean('automation_check_pending_payments_enabled', true),
            'automationReconnectWebhooks'=> $s->boolean(
                'automation_reconnect_webhooks_enabled',
                $s->boolean('automation_webhook_health_check_enabled', false),
            ),

            // Links
            'telegramCommunityUrl' => $s->get('telegram_community_url', ''),
            'tutorialsUrl'         => $s->get('tutorials_url', ''),
            'supportUrl'           => $s->get('support_url', ''),

            // Runtime / Performance
            'runtimeSettings' => $runtime,
            'runtimeDockerStatus' => [
                'docker_available' => $runtimeDockerSummary['available'] ?? false,
                'image_exists' => $runtimeDocker->imageExists(),
                'active_containers' => $runtimeDockerSummary['active'] ?? 0,
                'unhealthy_containers' => $runtimeDockerSummary['unhealthy'] ?? 0,
                'last_error' => $runtimeDockerSummary['error'] ?? null,
            ],
            'redisPasswordMasked' => PlatformSetting::maskedValue('redis_password'),
        ]);
    }

    // ─────────────────────────────────────────
    // SECTION SAVES
    // ─────────────────────────────────────────

    public function saveGeneral(Request $request): RedirectResponse
    {
        $oldMode = (string) $this->settings->get('platform_mode', 'live');

        $data = $request->validate([
            'platform_name'    => ['required', 'string', 'max:100'],
            'support_email'    => ['nullable', 'email', 'max:255'],
            'default_currency' => ['required', Rule::in(['USD', 'EUR', 'GBP', 'CAD', 'AUD'])],
            'platform_mode'    => ['required', Rule::in(['live', 'maintenance'])],
        ]);

        $this->settings->set('platform_name', $data['platform_name']);
        $this->settings->set('support_email', $data['support_email'] ?? '');
        $this->settings->set('default_currency', $data['default_currency']);
        $this->settings->set('platform_mode', $data['platform_mode']);

        if ($oldMode !== $data['platform_mode']) {
            $message = $data['platform_mode'] === 'live'
                ? 'Platform restored to live mode.'
                : 'Maintenance mode enabled.';

            $this->audit->log('security', $data['platform_mode'] === 'live' ? 'maintenance.disabled' : 'maintenance.enabled', $message, [
                'old_value' => $oldMode,
                'new_value' => $data['platform_mode'],
            ], $request->user());

            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->with('status', $message);
        }

        $this->audit->log('admin', 'admin_settings_updated', 'General settings updated.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'general'])
            ->with('status', 'General settings saved.');
    }

    public function saveBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'platform_accent_color'  => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'login_page_title'       => ['nullable', 'string', 'max:200'],
            'login_page_subtitle'    => ['nullable', 'string', 'max:500'],
            'footer_text'            => ['nullable', 'string', 'max:300'],
            'platform_logo'          => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon'                => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp,svg', 'max:1024'],
            'admin_logo'             => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        foreach (['platform_primary_color', 'platform_accent_color', 'login_page_title', 'login_page_subtitle', 'footer_text'] as $key) {
            $value = $data[$key] ?? '';
            $this->settings->set($key, $value ?? '');
        }

        $this->storeBrandingUpload($request->file('platform_logo'), 'platform_logo_path', 'platform-logo');
        $this->storeBrandingUpload($request->file('favicon'), 'favicon_path', 'favicon');
        $this->storeBrandingUpload($request->file('admin_logo'), 'admin_logo_path', 'admin-logo');

        return redirect()->route('admin.settings.index', ['tab' => 'branding'])
            ->with('status', 'Branding settings saved.');
    }

    private function storeBrandingUpload(?UploadedFile $file, string $settingKey, string $name): void
    {
        if (! $file) {
            return;
        }

        $oldPath = (string) $this->settings->get($settingKey, '');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $path = $file->storeAs('branding', $name.'-'.time().'-'.bin2hex(random_bytes(4)).'.'.$extension, 'public');

        if ($path) {
            $this->settings->set($settingKey, $path);

            if ($oldPath !== '' && str_starts_with($oldPath, 'branding/') && $oldPath !== $path) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }

    public function saveLinks(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telegram_community_url' => ['nullable', 'url', 'max:500'],
            'tutorials_url'          => ['nullable', 'url', 'max:500'],
            'support_url'            => ['nullable', 'string', 'max:500'],
        ]);

        $this->settings->set('telegram_community_url', $data['telegram_community_url'] ?? '');
        $this->settings->set('tutorials_url', $data['tutorials_url'] ?? '');
        $this->settings->set('support_url', $data['support_url'] ?? '');

        $this->audit->log('admin', 'admin_settings_updated', 'Link settings updated.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'links'])
            ->with('status', 'Link settings saved.');
    }

    public function savePayments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'merchant_api_key'    => ['nullable', 'string', 'max:500'],
            'base_url'            => ['required', 'url', 'max:255', 'starts_with:https://'],
            'invoice_lifetime'    => ['nullable', 'integer', 'min:15', 'max:2880'],
            'under_paid_coverage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (filled($data['merchant_api_key'] ?? null)) {
            PlatformSetting::setValue('oxapay_merchant_api_key', $data['merchant_api_key'], true);
        }

        PlatformSetting::setValue('oxapay_base_url', $data['base_url']);
        PlatformSetting::setValue('oxapay_enabled', $request->boolean('oxapay_enabled') ? '1' : '0');
        PlatformSetting::setValue('oxapay_fee_paid_by_user', $request->boolean('fee_paid_by_user') ? '1' : '0');
        PlatformSetting::setValue('oxapay_sandbox', $request->boolean('sandbox') ? '1' : '0');
        PlatformSetting::setValue('oxapay_invoice_lifetime', filled($data['invoice_lifetime'] ?? null) ? (string) $data['invoice_lifetime'] : '60');
        PlatformSetting::setValue('oxapay_under_paid_coverage', filled($data['under_paid_coverage'] ?? null) ? (string) $data['under_paid_coverage'] : null);

        $this->audit->log('payment', 'payment_settings.updated', 'Payment settings updated.', [
            'base_url' => $data['base_url'],
            'merchant_api_key_updated' => filled($data['merchant_api_key'] ?? null),
            'oxapay_enabled' => $request->boolean('oxapay_enabled'),
            'sandbox' => $request->boolean('sandbox'),
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'payments'])
            ->with('status', 'Payment settings saved.');
    }

    public function saveTriggerWebhooks(Request $request): RedirectResponse
    {
        $keys = [
            'trigger_webhooks_enabled',
            'trigger_webhook_payment_success',
            'trigger_webhook_template_purchase',
            'trigger_webhook_plan_upgrade',
            'trigger_webhook_bot_created',
            'trigger_webhook_command_error',
        ];

        foreach ($keys as $key) {
            $field = str_replace('trigger_webhook_', '', str_replace('trigger_webhooks_', '', $key));
            $this->settings->set($key, $request->boolean($key) ? '1' : '0');
        }

        $this->audit->log('admin', 'platform_settings.updated', 'Trigger webhook settings updated.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'triggers'])
            ->with('status', 'Trigger webhook settings saved.');
    }

    public function saveStorage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'storage_default_disk'             => ['required', Rule::in(['local', 's3', 'public'])],
            'storage_warning_threshold_percent' => ['required', 'integer', 'min:50', 'max:99'],
            'storage_critical_threshold_percent'=> ['required', 'integer', 'min:51', 'max:100'],
        ]);

        $this->settings->set('storage_default_disk', $data['storage_default_disk']);
        $this->settings->set('storage_tracking_enabled', $request->boolean('storage_tracking_enabled') ? '1' : '0');
        $this->settings->set('clear_bot_storage_on_delete', $request->boolean('clear_bot_storage_on_delete') ? '1' : '0');
        $this->settings->set('storage_warning_threshold_percent', (string) $data['storage_warning_threshold_percent']);
        $this->settings->set('storage_critical_threshold_percent', (string) $data['storage_critical_threshold_percent']);

        $this->audit->log('admin', 'platform_settings.updated', 'Storage settings updated.', [
            'storage_default_disk' => $data['storage_default_disk'],
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'storage'])
            ->with('status', 'Storage settings saved.');
    }

    public function saveSecurity(Request $request): RedirectResponse
    {
        $oldRegistrationEnabled = $this->registrationEnabled();
        $oldAllowedIps = (string) $this->settings->get('maintenance_allowed_ips', '');

        $data = $request->validate([
            'session_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'max_login_attempts'      => ['nullable', 'integer', 'min:3', 'max:20'],
            'lockout_minutes'         => ['nullable', 'integer', 'min:1', 'max:1440'],
            'maintenance_allowed_ips' => ['nullable', 'string', 'max:1000'],
        ]);

        $registrationEnabled = $request->boolean('allow_registration');
        $normalizedAllowedIps = $this->normalizeIpList($data['maintenance_allowed_ips'] ?? '');

        $this->settings->set('require_email_verification', $request->boolean('require_email_verification') ? '1' : '0');
        foreach ($this->registrationSettingKeys() as $key) {
            $this->settings->set($key, $registrationEnabled ? '1' : '0');
        }
        $this->settings->set('admin_maintenance_access', $request->boolean('admin_maintenance_access') ? '1' : '0');
        $this->settings->set('admin_access_during_maintenance', $request->boolean('admin_maintenance_access') ? '1' : '0');
        $this->settings->set('maintenance_allowed_ips', $normalizedAllowedIps);
        $this->settings->set('session_timeout_minutes', filled($data['session_timeout_minutes'] ?? null) ? (string) $data['session_timeout_minutes'] : '');
        $this->settings->set('max_login_attempts', filled($data['max_login_attempts'] ?? null) ? (string) $data['max_login_attempts'] : '5');
        $this->settings->set('lockout_minutes', filled($data['lockout_minutes'] ?? null) ? (string) $data['lockout_minutes'] : '1');

        if ($oldRegistrationEnabled !== $registrationEnabled) {
            $this->audit->log('security', $registrationEnabled ? 'registration.enabled' : 'registration.disabled', 'Registration setting updated.', [
                'old_value' => $oldRegistrationEnabled,
                'new_value' => $registrationEnabled,
                'max_login_attempts' => filled($data['max_login_attempts'] ?? null) ? (string) $data['max_login_attempts'] : '5',
                'lockout_minutes' => filled($data['lockout_minutes'] ?? null) ? (string) $data['lockout_minutes'] : '1',
                'session_timeout_minutes' => $data['session_timeout_minutes'] ?? null,
            ], $request->user());
        }

        if ($oldAllowedIps !== $normalizedAllowedIps) {
            $this->audit->log('security', 'maintenance.allowed_ips.updated', 'Maintenance allowed IPs updated.', [
                'old_value' => $oldAllowedIps,
                'new_value' => $normalizedAllowedIps,
            ], $request->user());
        }

        $this->audit->log('security', $request->boolean('require_email_verification') ? 'email_verification.enabled' : 'email_verification.disabled', 'Email verification setting updated.', [
            'require_email_verification' => $request->boolean('require_email_verification'),
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'security'])
            ->with('status', 'Security settings saved.');
    }

    public function saveNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_mailer' => ['nullable', 'string', 'max:50'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'null', 'none', ''])],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_test_recipient' => ['nullable', 'email', 'max:255'],
            'admin_alert_email' => ['nullable', 'email', 'max:255'],
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $encryption = $data['mail_encryption'] ?? 'tls';
        if (in_array($encryption, ['null', 'none', ''], true)) {
            $encryption = '';
        }

        $adminEmail = $data['admin_notification_email'] ?? $data['admin_alert_email'] ?? '';

        $this->settings->set('mail_enabled', $request->boolean('mail_enabled') ? '1' : '0');
        $this->settings->set('mail_mailer', ($data['mail_mailer'] ?? '') ?: 'smtp');
        $this->settings->set('mail_host', ($data['mail_host'] ?? '') ?: 'smtp.gmail.com');
        $this->settings->set('mail_port', filled($data['mail_port'] ?? null) ? (string) $data['mail_port'] : '587');
        $this->settings->set('mail_username', $data['mail_username'] ?? '');
        if (filled($data['mail_password'] ?? null)) {
            $this->settings->set('mail_password', $data['mail_password'], true);
        }
        $this->settings->set('mail_encryption', $encryption);
        $this->settings->set('mail_from_address', $data['mail_from_address'] ?? '');
        $this->settings->set('mail_from_name', ($data['mail_from_name'] ?? '') ?: 'BotHost Pro');
        $this->settings->set('mail_test_recipient', $data['mail_test_recipient'] ?? '');
        $this->settings->set('admin_notification_email', $adminEmail);

        $this->settings->set('notify_payment_events', $request->boolean('notify_payment_events') ? '1' : '0');
        $this->settings->set('notify_new_user_signup', $request->boolean('notify_new_user_signup') ? '1' : '0');
        $this->settings->set('notify_template_events', $request->boolean('notify_template_events') ? '1' : '0');
        $this->settings->set('notify_plan_events', $request->boolean('notify_plan_events') ? '1' : '0');
        $this->settings->set('notify_bot_errors', $request->boolean('notify_bot_errors') ? '1' : '0');
        $this->settings->set('notify_storage_warnings', $request->boolean('notify_storage_warnings') ? '1' : '0');
        $this->settings->set('admin_alert_email', $adminEmail);

        $this->mailSettings->apply();

        $this->audit->log('admin', 'platform_settings.updated', 'Notification settings updated.', [
            'mail_enabled' => $request->boolean('mail_enabled'),
            'mail_mailer' => ($data['mail_mailer'] ?? '') ?: 'smtp',
            'password_updated' => filled($data['mail_password'] ?? null),
            'notify_new_user_signup' => $request->boolean('notify_new_user_signup'),
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'notifications'])
            ->with('status', 'Notification settings saved.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['nullable', 'email', 'max:255'],
            'mail_test_recipient' => ['nullable', 'email', 'max:255'],
        ]);

        if (! $this->mailSettings->enabled()) {
            return back()->withErrors(['email' => 'Email sending is disabled. Enable email sending first.']);
        }

        $settings = $this->mailSettings->settings();
        $recipient = $data['recipient']
            ?? $data['mail_test_recipient']
            ?? $settings['mail_test_recipient']
            ?? $settings['admin_notification_email']
            ?? $request->user()->email;

        if (! filled($recipient)) {
            return back()->withErrors(['email' => 'Enter a test recipient email address first.']);
        }

        try {
            $this->mailSettings->apply();
            Mail::raw('Your email settings are working correctly.', function ($message) use ($recipient): void {
                $message->to($recipient)->subject('BotHost Pro Test Email');
            });

            $this->audit->log('email', 'test_email.sent', 'Test email sent.', [
                'recipient' => $recipient,
            ], $request->user());

            return redirect()->route('admin.settings.index', ['tab' => 'notifications'])
                ->with('status', 'Test email sent successfully.');
        } catch (Throwable $e) {
            Log::warning('Test email failed.', ['error' => $e->getMessage()]);
            $this->audit->log('email', 'test_email.failed', 'Test email failed.', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ], $request->user(), 'failed');

            return back()->withErrors(['email' => 'Unable to send test email. Please check your SMTP settings and try again.']);
        }
    }

    public function saveAutomations(Request $request): RedirectResponse
    {
        $keys = [
            'automation_process_broadcasts_enabled',
            'automation_prune_logs_enabled',
            'automation_reconnect_webhooks_enabled',
        ];

        foreach ($keys as $key) {
            $this->settings->set($key, $request->boolean($key) ? '1' : '0');
        }

        $this->settings->set(
            'automation_webhook_health_check_enabled',
            $request->boolean('automation_reconnect_webhooks_enabled') ? '1' : '0',
        );

        $this->audit->log('admin', 'platform_settings.updated', 'Automation settings updated.', [
            'broadcast_queue' => $request->boolean('automation_process_broadcasts_enabled'),
            'telegram_reconnect' => $request->boolean('automation_reconnect_webhooks_enabled'),
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'automations'])
            ->with('status', 'Automation settings saved.');
    }

    // ─────────────────────────────────────────
    // MAINTENANCE TOOLS
    // ─────────────────────────────────────────

    public function saveRuntimePerformance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'redis_enabled' => ['nullable', 'boolean'],
            'redis_host' => ['required', 'string', 'max:255'],
            'redis_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'redis_password' => ['nullable', 'string', 'max:255'],
            'redis_db' => ['required', 'integer', 'min:0', 'max:255'],
            'cache_store' => ['required', Rule::in(['file', 'database', 'redis'])],
            'queue_connection' => ['required', Rule::in(['sync', 'database', 'redis'])],
            'runtime_warm_enabled' => ['nullable', 'boolean'],
            'queue_simple_commands' => ['nullable', 'boolean'],
            'command_timeout_ms' => ['required', 'integer', 'min:1000', 'max:30000'],
            'max_delay_ms' => ['required', 'integer', 'min:0', 'max:30000'],
            'slow_command_threshold_ms' => ['required', 'integer', 'min:100', 'max:30000'],
            'log_slow_commands' => ['nullable', 'boolean'],
            'runtime_mode' => ['required', Rule::in(['local', 'docker'])],
            'runtime_host' => ['required', 'string', 'max:255'],
            'runtime_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'runtime_base_url' => ['required', 'url', 'max:500'],
            'runtime_health_url' => ['required', 'url', 'max:500'],
            'runtime_execute_url' => ['required', 'url', 'max:500'],
            'runtime_docker_enabled' => ['nullable', 'boolean'],
            'runtime_docker_image' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._\\/-]*(?::[a-zA-Z0-9._-]+)?$/'],
            'runtime_container_prefix' => ['required', 'string', 'max:60', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/'],
            'runtime_http_port_start' => ['required', 'integer', 'min:1024', 'max:65500'],
            'runtime_memory_limit' => ['required', 'string', 'max:20', 'regex:/^[0-9]+[kKmMgG]?$/'],
            'runtime_cpu_limit' => ['required', 'numeric', 'min:0.05', 'max:4'],
            'runtime_keep_paused_warm' => ['nullable', 'boolean'],
            'runtime_auto_restart' => ['nullable', 'boolean'],
            'show_user_code_errors_to_owners' => ['nullable', 'boolean'],
            'log_user_code_errors' => ['nullable', 'boolean'],
            'log_backend_runtime_errors' => ['nullable', 'boolean'],
            'log_webhook_errors' => ['nullable', 'boolean'],
            'log_telegram_api_errors' => ['nullable', 'boolean'],
            'log_redis_errors' => ['nullable', 'boolean'],
            'log_docker_errors' => ['nullable', 'boolean'],
        ]);

        foreach ([
            'redis_enabled',
            'runtime_docker_enabled',
            'runtime_warm_enabled',
            'queue_simple_commands',
            'log_slow_commands',
            'runtime_keep_paused_warm',
            'runtime_auto_restart',
            'show_user_code_errors_to_owners',
            'log_user_code_errors',
            'log_backend_runtime_errors',
            'log_webhook_errors',
            'log_telegram_api_errors',
            'log_redis_errors',
            'log_docker_errors',
        ] as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }

        foreach (['runtime_base_url', 'runtime_health_url', 'runtime_execute_url'] as $urlKey) {
            $data[$urlKey] = rtrim(trim((string) ($data[$urlKey] ?? '')), '/');
        }

        $this->runtimeSettings->save($data);

        try {
            Artisan::call('cache:clear');
            Artisan::call('queue:restart');
        } catch (Throwable $exception) {
            Log::warning('Runtime setting post-save maintenance failed.', [
                'error' => $this->safeRuntimeMessage($exception->getMessage()),
            ]);
        }

        $this->audit->log('admin', 'runtime.performance.updated', 'Runtime performance settings updated.', [
            'redis_enabled' => $request->boolean('redis_enabled'),
            'cache_store' => $data['cache_store'],
            'queue_connection' => $data['queue_connection'],
            'runtime_mode' => $data['runtime_mode'],
            'runtime_docker_image' => $data['runtime_docker_image'],
        ], $request->user());

        $message = 'Runtime performance settings saved.';
        $runtimeUrlWarning = $this->runtimeUrlWarning($data['runtime_base_url'] ?? '');

        if ($runtimeUrlWarning !== null) {
            $message .= ' Warning: '.$runtimeUrlWarning;
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', $message);
    }

    public function testRuntime(Request $request): RedirectResponse
    {
        $settings = $this->runtimeSettings->all();
        $url = rtrim((string) ($settings['runtime_health_url'] ?? ''), '/');

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Runtime unavailable: invalid runtime health URL.']);
        }

        try {
            $response = Http::connectTimeout(1)->timeout(2)->acceptJson()->get($url);
        } catch (Throwable $exception) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Runtime unavailable: '.$this->safeRuntimeMessage($exception->getMessage())]);
        }

        if (! $response->successful()) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Runtime unavailable: HTTP '.$response->status().'.']);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Runtime online.');
    }

    public function resetRuntimeUrls(Request $request): RedirectResponse
    {
        $defaults = [
            'runtime_host' => '127.0.0.1',
            'runtime_port' => '8787',
            'runtime_base_url' => 'http://127.0.0.1:8787',
            'runtime_health_url' => 'http://127.0.0.1:8787/health',
            'runtime_execute_url' => 'http://127.0.0.1:8787/execute',
        ];

        foreach ($defaults as $key => $value) {
            $this->settings->set($key, $value);
        }

        $this->runtimeSettings->clear();

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Runtime URLs reset to local defaults.');
    }

    public function testRedis(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'redis_host' => ['nullable', 'string', 'max:255'],
            'redis_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'redis_password' => ['nullable', 'string', 'max:255'],
            'redis_db' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $settings = $this->runtimeSettings->all();
        $host = $data['redis_host'] ?? $settings['redis_host'] ?? config('database.redis.default.host') ?? env('REDIS_HOST', '127.0.0.1');
        $port = $data['redis_port'] ?? $settings['redis_port'] ?? config('database.redis.default.port') ?? env('REDIS_PORT', 6379);
        $password = filled($data['redis_password'] ?? null)
            ? $data['redis_password']
            : ($settings['redis_password'] ?? config('database.redis.default.password') ?? env('REDIS_PASSWORD'));
        $database = $data['redis_db'] ?? $settings['redis_db'] ?? env('REDIS_DB', 0);

        if ($password === 'null' || $password === '') {
            $password = null;
        }

        config([
            'database.redis.default.host' => $host,
            'database.redis.default.port' => (string) $port,
            'database.redis.default.password' => $password,
            'database.redis.default.database' => (string) $database,
        ]);

        try {
            Redis::purge('default');
            Redis::connection('default')->ping();
        } catch (Throwable $exception) {
            if ($this->runtimeSettings->boolean('log_redis_errors', true)) {
                Log::warning('Redis connection test failed.', [
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                    'database' => config('database.redis.default.database'),
                    'error' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['redis' => 'Redis connection failed. Check host, port, password, and database.']);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Redis connection successful.');
    }

    public function testDocker(Request $request, DockerRuntimeService $runtime): RedirectResponse
    {
        if (! $runtime->dockerAvailable()) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Docker is not available on this machine. Local runtime mode will be used.']);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Docker is available.');
    }

    public function checkRuntimeImage(Request $request, DockerRuntimeService $runtime): RedirectResponse
    {
        if (! $runtime->imageExists()) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Docker runtime image is missing. Build the runtime image before enabling Docker mode.']);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Docker runtime image exists.');
    }

    public function buildRuntimeImage(Request $request, DockerRuntimeService $runtime): RedirectResponse
    {
        $result = $runtime->buildImage();

        if (! ($result['ok'] ?? false)) {
            return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
                ->withErrors(['runtime' => 'Runtime image build failed. '.$this->safeRuntimeMessage($result['error'] ?? '')]);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Runtime image built successfully.');
    }

    public function runtimeHealthCheck(Request $request): RedirectResponse
    {
        return $this->testRuntime($request);
    }

    public function clearCache(Request $request): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');

        $this->audit->log('system', 'cache.cleared', 'Application and config cache cleared.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Application and config cache cleared.');
    }

    public function clearViews(Request $request): RedirectResponse
    {
        Artisan::call('view:clear');

        $this->audit->log('system', 'view_cache.cleared', 'View cache cleared.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'View cache cleared.');
    }

    public function clearRoutes(Request $request): RedirectResponse
    {
        Artisan::call('route:clear');

        $this->audit->log('system', 'route_cache.cleared', 'Route cache cleared.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', 'Route cache cleared.');
    }

    public function storageLink(Request $request): RedirectResponse
    {
        try {
            Artisan::call('storage:link', ['--force' => true]);
            $message = 'Storage symlink created.';
            $status = 'success';
        } catch (\Throwable $e) {
            $message = 'Storage link: ' . $e->getMessage();
            $status = 'failed';
        }

        $this->audit->log('system', 'storage_link.created', $message, [], $request->user(), $status);

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', $message);
    }

    // ─────────────────────────────────────────
    // WEBHOOKS TOOLS
    // ─────────────────────────────────────────

    public function saveWebhookPublicUrl(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_public_url' => ['required', 'url', 'max:500', 'starts_with:https://'],
        ]);

        $publicUrl = PublicCallbackUrl::normalize((string) $data['app_public_url']);

        $this->settings->set('app_public_url', $publicUrl);
        $this->clearUrlCaches();

        $this->audit->log('webhook', 'public_callback_url.updated', 'Public callback URL updated.', [
            'public_url' => $publicUrl,
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'webhooks'])
            ->with('status', 'Public callback URL saved.');
    }

    public function resetAllWebhooks(Request $request, TelegramWebhookService $webhooks): RedirectResponse
    {
        $this->audit->log('webhook', 'telegram_webhooks.reset.started', 'Telegram webhook reset started.', [], $request->user());

        $data = $request->validate([
            'app_public_url' => ['nullable', 'url', 'max:500', 'starts_with:https://'],
        ]);

        if (filled($data['app_public_url'] ?? null)) {
            $this->settings->set('app_public_url', PublicCallbackUrl::normalize((string) $data['app_public_url']));
            $this->clearUrlCaches();
        }

        $publicUrl = PublicCallbackUrl::base();
        $validUrl = PublicCallbackUrl::isPublicHttps($publicUrl);

        if (! $validUrl) {
            $this->audit->log('webhook', 'telegram_webhooks.reset', 'Telegram webhook reset failed: public URL invalid.', [
                'public_url_configured' => filled($publicUrl),
            ], $request->user(), 'failed');

            return redirect()->route('admin.settings.index', ['tab' => 'webhooks'])
                ->withErrors(['webhook' => 'The public callback URL must be a public HTTPS URL to set Telegram webhooks.']);
        }

        $summary = $webhooks->resetAllWebhooks();
        $set = $summary['success'];
        $failed = $summary['failed'];
        $checked = $summary['checked'];
        $started = $summary['started'];

        $this->audit->log('webhook', 'telegram_webhooks.reset', "Webhooks reset: {$set} successful, {$failed} failed, {$started} started.", [
            'public_url' => $publicUrl,
            'total_bots_checked' => $checked,
            'success_count' => $set,
            'failed_count' => $failed,
            'started_count' => $started,
        ], $request->user(), $failed > 0 ? 'partial' : 'success');

        return redirect()->route('admin.settings.index', ['tab' => 'webhooks'])
            ->with('status', "Webhooks reset: {$set} successful, {$failed} failed, {$started} bot(s) started.");
    }

    private function clearUrlCaches(): void
    {
        foreach (['config:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable $exception) {
                Log::warning('Failed to clear cache after public URL update.', [
                    'command' => $command,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────
    // DANGER ZONE
    // ─────────────────────────────────────────

    public function updateMaintenanceMode(Request $request): RedirectResponse
    {
        $oldMode = (string) $this->settings->get('platform_mode', 'live');

        $data = $request->validate([
            'platform_mode' => ['required', Rule::in(['live', 'maintenance'])],
        ]);

        $this->settings->set('platform_mode', $data['platform_mode']);

        $message = $data['platform_mode'] === 'live'
            ? 'Platform restored to live mode.'
            : 'Maintenance mode enabled.';

        $this->audit->log('security', $data['platform_mode'] === 'live' ? 'maintenance.disabled' : 'maintenance.enabled', $message, [
            'old_value' => $oldMode,
            'new_value' => $data['platform_mode'],
        ], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('status', $message);
    }

    public function enableMaintenance(Request $request): RedirectResponse
    {
        $request->merge(['platform_mode' => 'maintenance']);

        return $this->updateMaintenanceMode($request);
    }

    public function disableMaintenance(Request $request): RedirectResponse
    {
        $request->merge(['platform_mode' => 'live']);

        return $this->updateMaintenanceMode($request);
    }

    public function disableRegistrations(Request $request): RedirectResponse
    {
        foreach ($this->registrationSettingKeys() as $key) {
            $this->settings->set($key, '0');
        }

        $this->audit->log('security', 'registration.disabled', 'New user registrations disabled.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'security'])
            ->with('status', 'New user registrations disabled.');
    }

    public function enableRegistrations(Request $request): RedirectResponse
    {
        foreach ($this->registrationSettingKeys() as $key) {
            $this->settings->set($key, '1');
        }

        $this->audit->log('security', 'registration.enabled', 'User registrations re-enabled.', [], $request->user());

        return redirect()->route('admin.settings.index', ['tab' => 'security'])
            ->with('status', 'User registrations re-enabled.');
    }

    private function registrationEnabled(): bool
    {
        foreach (['registration_enabled', 'allow_registration', 'enable_registration', 'new_registration_enabled'] as $key) {
            if ($this->settings->get($key) !== null) {
                return $this->settings->boolean($key, true);
            }
        }

        return true;
    }

    private function registrationSettingKeys(): array
    {
        $keys = collect(['registration_enabled', 'allow_registration', 'enable_registration', 'new_registration_enabled'])
            ->filter(fn (string $key) => PlatformSetting::query()->where('key', $key)->exists())
            ->values()
            ->all();

        return $keys !== [] ? $keys : ['registration_enabled'];
    }

    private function normalizeIpList(?string $value): string
    {
        return collect(preg_split('/[\s,]+/', (string) $value) ?: [])
            ->map(fn ($ip) => trim($ip))
            ->filter()
            ->unique()
            ->implode(', ');
    }

    private function safeRuntimeMessage(string $message): string
    {
        return str($message)
            ->replaceMatches('/\d{6,}:[A-Za-z0-9_-]{20,}/', '[redacted-token]')
            ->replaceMatches('/(password|secret|token|api[_-]?key)=\S+/i', '$1=[redacted]')
            ->limit(300, '')
            ->toString();
    }

    private function runtimeUrlWarning(string $runtimeBaseUrl): ?string
    {
        $host = strtolower((string) parse_url($runtimeBaseUrl, PHP_URL_HOST));

        if ($host === '') {
            return null;
        }

        $publicHostHints = ['trycloudflare.com', 'ngrok', 'ngrok-free.app'];

        foreach ($publicHostHints as $hint) {
            if (str_contains($host, $hint)) {
                return 'Runtime Base URL should point to the Node.js runtime server, not the public Cloudflare app URL.';
            }
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $publicHost = strtolower((string) parse_url((string) \App\Support\PublicCallbackUrl::base(), PHP_URL_HOST));

        if ($host !== '' && in_array($host, array_filter([$appHost, $publicHost]), true)) {
            return 'This looks like your public app URL. Runtime URL should be your internal Node.js runtime URL, for example http://127.0.0.1:8787.';
        }

        return null;
    }
}

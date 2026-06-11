<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(): View
    {
        $failedLoginBase = AuditLog::query()
            ->where('category', 'security')
            ->where('status', 'failed')
            ->where('action', 'like', 'login.%')
            ->where('created_at', '>=', now()->subDay());

        $suspiciousIps = (clone $failedLoginBase)
            ->selectRaw('ip_address, count(*) as failures')
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('count(*) >= 5')
            ->count();

        $suspiciousEmails = (clone $failedLoginBase)
            ->get(['metadata'])
            ->map(fn (AuditLog $log) => strtolower((string) ($log->metadata['email'] ?? '')))
            ->filter()
            ->countBy()
            ->filter(fn (int $count) => $count >= 5)
            ->count();

        return view('admin.security.index', [
            'summary' => [
                'admin_users' => User::where('role', 'admin')->count(),
                'failed_login_24h' => (clone $failedLoginBase)->count(),
                'admin_login_success_24h' => AuditLog::where('category', 'security')->where('action', 'login.success')->where('created_at', '>=', now()->subDay())->whereHas('actor', fn ($query) => $query->where('role', 'admin'))->count(),
                'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
                'security_events' => AuditLog::where('category', 'security')->whereDate('created_at', today())->count(),
                'suspicious_activity' => $suspiciousIps + $suspiciousEmails + AuditLog::where('category', 'security')->whereIn('status', ['failed', 'blocked'])->where('created_at', '>=', now()->subDay())->count(),
                'maintenance_mode' => PlatformSetting::getValue('platform_mode', 'live') === 'maintenance',
                'registration_enabled' => filter_var(PlatformSetting::getValue('registration_enabled', PlatformSetting::getValue('allow_registration', '1')), FILTER_VALIDATE_BOOLEAN),
                'last_webhook_reset' => AuditLog::whereIn('action', ['telegram_webhooks.reset', 'telegram_webhooks_reset_completed'])->latest()->first(),
                'active_admin_sessions' => Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')
                    ? null
                    : null,
            ],
            'recentLogs' => AuditLog::whereIn('category', ['security', 'admin', 'webhook'])->latest()->limit(10)->get(),
        ]);
    }
}

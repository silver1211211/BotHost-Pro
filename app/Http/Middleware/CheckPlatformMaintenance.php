<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = PlatformSetting::getValue('platform_mode', 'live');

        if ($mode !== 'maintenance') {
            return $next($request);
        }

        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        if ($this->ipAllowed($request)) {
            return $next($request);
        }

        $user = $request->user();
        if ($this->adminAccessEnabled() && $user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        return response()->view('errors.503', [], 503);
    }

    private function isAllowedRoute(Request $request): bool
    {
        if ($request->is(
            'admin',
            'admin/*',
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'email/verify',
            'email/verify/*',
            'email/verification-notification',
            'confirm-password',
            'account/locked',
            'telegram/*',
            'webhooks/*',
            'payment/*',
            'payments/callback/*',
            'oxapay/*',
            'build/*',
            'css/*',
            'js/*',
            'images/*',
            'storage/*',
            'favicon.ico',
            'up'
        )) {
            return true;
        }

        return $request->routeIs(
            'login',
            'logout',
            'password.*',
            'verification.*',
            'admin.login',
            'admin.login.store',
            'admin.logout',
            'account.locked'
        );
    }

    private function ipAllowed(Request $request): bool
    {
        $raw = (string) PlatformSetting::getValue('maintenance_allowed_ips', '');

        if ($raw === '') {
            return false;
        }

        $allowed = collect(preg_split('/[\s,]+/', $raw) ?: [])
            ->map(fn ($ip) => trim($ip))
            ->filter()
            ->all();

        return in_array($request->ip(), $allowed, true);
    }

    private function adminAccessEnabled(): bool
    {
        $value = PlatformSetting::getValue('admin_access_during_maintenance');

        if ($value === null) {
            $value = PlatformSetting::getValue('admin_maintenance_access', '1');
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

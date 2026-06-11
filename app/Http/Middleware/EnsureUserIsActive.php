<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Always allow the locked screen itself through (prevent redirect loop)
        if ($request->routeIs('account.locked')) {
            return $next($request);
        }

        // Permanent ban
        if ($user->isBanned()) {
            return redirect()->route('account.locked');
        }

        // Suspended
        if ($user->isSuspended()) {
            // Auto-lift if timed suspension has expired
            if ($user->suspensionExpired()) {
                $user->update([
                    'status'             => 'active',
                    'suspended_until'    => null,
                    'suspension_message' => null,
                    'suspension_cta_label' => null,
                    'suspension_cta_url'   => null,
                ]);

                return $next($request);
            }

            return redirect()->route('account.locked');
        }

        return $next($request);
    }
}

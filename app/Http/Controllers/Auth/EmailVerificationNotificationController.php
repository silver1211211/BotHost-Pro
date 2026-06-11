<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\MailSettingsService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $verificationRequired = app(PlatformSettingsService::class)->boolean('require_email_verification', false);

        if (! $verificationRequired || $user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $mailSettings = app(MailSettingsService::class);
        $audit = app(AuditLogService::class);

        if (! $mailSettings->enabled()) {
            $audit->log('email', 'verification_email.failed', 'Verification email not sent because mail is disabled.', [
                'user_id' => $user->id,
            ], $user, 'failed', User::class, $user->id);

            return back()->withErrors(['email' => 'Email sending is disabled. Please contact support.']);
        }

        try {
            $mailSettings->apply();
            $user->sendEmailVerificationNotification();

            $audit->log('email', 'verification_email.sent', 'Verification email sent.', [
                'user_id' => $user->id,
            ], $user, 'success', User::class, $user->id);
        } catch (Throwable $e) {
            Log::warning('Verification email resend failed.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $audit->log('email', 'verification_email.failed', 'Verification email resend failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ], $user, 'failed', User::class, $user->id);

            return back()->withErrors(['email' => 'Unable to send verification email. Please try again later.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}

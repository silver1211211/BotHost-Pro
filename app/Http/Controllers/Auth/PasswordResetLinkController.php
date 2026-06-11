<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\MailSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->audit->log('security', 'password_reset.requested', 'Password reset requested.', [
            'email' => $data['email'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (! $this->mailSettings->enabled()) {
            $this->audit->log('security', 'password_reset.email_failed', 'Password reset email not sent because mail is disabled.', [
                'email' => $data['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ], status: 'failed');

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email sending is currently disabled. Please contact support.']);
        }

        try {
            $this->mailSettings->apply();

            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable) {
            $this->audit->log('security', 'password_reset.email_failed', 'Password reset email failed to send.', [
                'email' => $data['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ], status: 'failed');

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not send a reset link. Please check the email address or try again later.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            $this->audit->log('security', 'password_reset.email_sent', 'Password reset email sent.', [
                'email' => $data['email'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->with('status', 'We have emailed your password reset link.');
        }

        $this->audit->log('security', 'password_reset.email_failed', 'Password reset email was not sent.', [
            'email' => $data['email'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], status: 'failed');

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'We could not send a reset link. Please check the email address or try again later.']);
    }
}

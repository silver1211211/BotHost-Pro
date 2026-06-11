<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\MailSettingsService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settings,
        private readonly MailSettingsService $mailSettings,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        if (! $this->registrationEnabled()) {
            return view('auth.registration-closed');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $this->registrationEnabled()) {
            throw ValidationException::withMessages([
                'registration' => 'New registration is currently disabled.',
            ]);
        }

        $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'max:40', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $verificationRequired = $this->settings->boolean('require_email_verification', false);

        $user = User::create([
            'name' => str($request->username)->replace(['-', '_'], ' ')->title(),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => $verificationRequired ? null : now(),
        ]);

        $this->sendVerificationEmailIfRequired($user, $verificationRequired);
        $this->sendSignupNotification($request, $user);

        Auth::login($user);

        if ($verificationRequired) {
            return redirect()->route('verification.notice');
        }

        return redirect(route('dashboard', absolute: false));
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

    private function sendVerificationEmailIfRequired(User $user, bool $verificationRequired): void
    {
        if (! $verificationRequired) {
            return;
        }

        if (! $this->mailSettings->enabled()) {
            $this->audit->log('email', 'verification_email.failed', 'Verification email not sent because mail is disabled.', [
                'user_id' => $user->id,
            ], $user, 'failed', User::class, $user->id);

            return;
        }

        try {
            $this->mailSettings->apply();
            $user->sendEmailVerificationNotification();
            $this->audit->log('email', 'verification_email.sent', 'Verification email sent.', [
                'user_id' => $user->id,
            ], $user, 'success', User::class, $user->id);
        } catch (Throwable $e) {
            Log::warning('Verification email failed.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->audit->log('email', 'verification_email.failed', 'Verification email failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ], $user, 'failed', User::class, $user->id);
        }
    }

    private function sendSignupNotification(Request $request, User $user): void
    {
        if (! $this->mailSettings->enabled() || ! $this->settings->boolean('notify_new_user_signup', false)) {
            return;
        }

        $recipient = $this->mailSettings->adminRecipient();
        if (! filled($recipient)) {
            Log::warning('Signup notification skipped: no admin notification email configured.');

            return;
        }

        try {
            $this->mailSettings->apply();
            $registeredAt = $user->created_at?->toDayDateTimeString() ?? now()->toDayDateTimeString();
            $plan = $user->subscription_plan ?: 'free';
            $body = implode("\n", [
                'A new user registered on BotHost Pro.',
                '',
                'Name: '.$user->name,
                'Username: '.$user->username,
                'Email: '.$user->email,
                'Plan: '.$plan,
                'Registered: '.$registeredAt,
                'IP Address: '.($request->ip() ?: 'Unavailable'),
            ]);

            Mail::raw($body, function ($message) use ($recipient): void {
                $message->to($recipient)->subject('New User Registered on BotHost Pro');
            });

            $this->audit->log('email', 'new_user_signup_notification.sent', 'New user signup notification sent.', [
                'user_id' => $user->id,
                'recipient' => $recipient,
            ], $user, 'success', User::class, $user->id);
        } catch (Throwable $e) {
            Log::warning('Signup notification failed.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->audit->log('email', 'new_user_signup_notification.failed', 'New user signup notification failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ], $user, 'failed', User::class, $user->id);
        }
    }
}

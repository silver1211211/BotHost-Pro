<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if (! $this->hasValidLatestToken($request)) {
            throw new HttpException(403, 'This verification link is invalid or has expired.');
        }

        if ($request->user()->markEmailAsVerified()) {
            $request->user()->forceFill([
                'email_verification_token' => null,
                'email_verification_token_created_at' => null,
            ])->saveQuietly();

            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    private function hasValidLatestToken(EmailVerificationRequest $request): bool
    {
        $token = (string) $request->query('token', '');
        $storedToken = (string) $request->user()->email_verification_token;

        return $token !== ''
            && $storedToken !== ''
            && hash_equals($storedToken, hash('sha256', $token));
    }
}

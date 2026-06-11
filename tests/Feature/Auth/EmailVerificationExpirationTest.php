<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('email verification link works before it expires', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

        return true;
    });
});

test('email verification email uses BotHost Pro sender name and five minute expiry copy', function () {
    config([
        'mail.from.address' => 'support@example.com',
        'auth.verification.expire' => 5,
    ]);

    $user = User::factory()->unverified()->create();
    $mail = (new VerifyEmailNotification('test-token'))->toMail($user);

    expect($mail->from)->toBe(['support@example.com', 'BotHost Pro'])
        ->and($mail->subject)->toBe('Verify Your BotHost Pro Email')
        ->and($mail->outroLines)->toContain('This email verification link expires in 5 minutes.');
});

test('email verification link expires after five minutes', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        $this->travel(6)->minutes();

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

        $this->travelBack();

        return true;
    });
});

test('requesting a new email verification link invalidates the previous link', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();
    $firstNotification = Notification::sent($user, VerifyEmailNotification::class)->first();
    $firstUrl = $firstNotification->toMail($user)->actionUrl;

    $user->sendEmailVerificationNotification();
    $secondNotification = Notification::sent($user, VerifyEmailNotification::class)->last();
    $secondUrl = $secondNotification->toMail($user)->actionUrl;

    $this->actingAs($user)
        ->get($firstUrl)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)
        ->get($secondUrl)
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

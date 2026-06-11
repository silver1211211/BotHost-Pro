<?php

use App\Models\User;
use App\Models\PlatformSetting;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('reset password email uses BotHost Pro sender name and five minute expiry copy', function () {
    config([
        'mail.from.address' => 'support@example.com',
        'auth.passwords.users.expire' => 5,
    ]);

    $user = User::factory()->create();
    $mail = (new ResetPasswordNotification('test-token'))->toMail($user);

    expect($mail->from)->toBe(['support@example.com', 'BotHost Pro'])
        ->and($mail->subject)->toBe('Reset Your BotHost Pro Password')
        ->and($mail->outroLines)->toContain('This password reset link expires in 5 minutes.');
});

test('reset password screen can be rendered', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('password reset token can only be used once', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired.']);

        return true;
    });
});

test('requesting a new password reset link invalidates the previous link', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);
    $firstToken = Notification::sent($user, ResetPasswordNotification::class)->first()->token;

    $this->travel(61)->seconds();

    $this->post('/forgot-password', ['email' => $user->email]);
    $secondToken = Notification::sent($user, ResetPasswordNotification::class)->last()->token;

    $this->post('/reset-password', [
        'token' => $firstToken,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired.']);

    $this->post('/reset-password', [
        'token' => $secondToken,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'));

    $this->travelBack();
});

test('password reset token expires after five minutes', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->travel(6)->minutes();

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired.']);

        $this->travelBack();

        return true;
    });
});

test('user can login with new password after reset but not old password', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '1');

    $user = User::factory()->create(['email' => 'reset-login@example.com']);

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        return true;
    });

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $this->assertGuest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'new-password',
    ]);
    $this->assertAuthenticated();
});

test('invalid reset token shows a clean error', function () {
    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired.']);
});

test('password reset routes remain available during maintenance', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');

    $this->get('/forgot-password')->assertOk();
    $this->get('/reset-password/test-token')->assertOk();
});

test('reset link request fails cleanly when mail is disabled', function () {
    Notification::fake();
    PlatformSetting::setValue('mail_enabled', '0');

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertSessionHasErrors(['email' => 'Email sending is currently disabled. Please contact support.']);
    Notification::assertNothingSent();
});

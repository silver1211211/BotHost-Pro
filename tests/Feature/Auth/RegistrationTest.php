<?php

use App\Models\PlatformSetting;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users must accept terms before registering', function () {
    $response = $this->post('/register', [
        'username' => 'termsuser',
        'email' => 'terms@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('terms');
    $this->assertGuest();
});

test('registration screen is replaced when registration is disabled', function () {
    PlatformSetting::setValue('registration_enabled', '0');

    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('Registration Closed')
        ->assertSee('New account registration is currently disabled')
        ->assertDontSee('Create your account');
});

test('direct registration posts are blocked when registration is disabled', function () {
    PlatformSetting::setValue('registration_enabled', '0');

    $response = $this->post('/register', [
        'username' => 'blockeduser',
        'email' => 'blocked@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ]);

    $response->assertSessionHasErrors(['registration' => 'New registration is currently disabled.']);
    $this->assertGuest();
    expect(User::query()->where('email', 'blocked@example.com')->exists())->toBeFalse();
});

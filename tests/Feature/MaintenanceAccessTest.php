<?php

use App\Models\PlatformSetting;
use App\Models\User;

test('maintenance blocks normal users without logging them out', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(503);
    $this->assertAuthenticatedAs($user);
});

test('admin can access user platform pages during maintenance', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');
    PlatformSetting::setValue('admin_maintenance_access', '1');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/dashboard')->assertOk();
    $this->actingAs($admin)->get('/dashboard/bots')->assertOk();
    $this->actingAs($admin)->get('/profile')->assertOk();
});

test('login post remains available during maintenance', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');

    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->get('/dashboard')->assertStatus(503);
});

test('maintenance ip allowlist bypasses maintenance screen but not admin authorization', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');
    PlatformSetting::setValue('maintenance_allowed_ips', '127.0.0.1');

    $this->get('/')->assertOk();
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
});

test('webhook routes are not blocked during maintenance', function () {
    PlatformSetting::setValue('platform_mode', 'maintenance');

    $this->post('/webhooks/custom/1/secret')->assertStatus(404);
    $this->post('/telegram/webhook/1/secret')->assertStatus(404);
});

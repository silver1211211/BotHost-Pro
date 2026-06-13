<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can upload platform and admin logos to public branding storage', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.settings.branding.save'), [
        'platform_logo' => UploadedFile::fake()->image('platform-logo.png', 120, 120),
        'admin_logo' => UploadedFile::fake()->image('admin-logo.png', 120, 120),
    ]);

    $response->assertRedirect(route('admin.settings.index', ['tab' => 'branding']));

    $platformLogoPath = PlatformSetting::getValue('platform_logo_path');
    $adminLogoPath = PlatformSetting::getValue('admin_logo_path');

    expect($platformLogoPath)->toStartWith('branding/platform-logo-')
        ->and($platformLogoPath)->toEndWith('.png')
        ->and($adminLogoPath)->toStartWith('branding/admin-logo-')
        ->and($adminLogoPath)->toEndWith('.png');

    Storage::disk('public')->assertExists($platformLogoPath);
    Storage::disk('public')->assertExists($adminLogoPath);
});

test('admin logo falls back to platform logo when no separate admin logo exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('branding/platform-logo-test.png', 'logo');

    PlatformSetting::setValue('platform_logo_path', 'branding/platform-logo-test.png');
    PlatformSetting::query()->where('key', 'admin_logo_path')->delete();

    expect(Branding::platformLogoUrl())->toContain('/storage/branding/platform-logo-test.png')
        ->and(Branding::adminLogoUrl())->toContain('/storage/branding/platform-logo-test.png');
});

test('branding upload rejects invalid logo files', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.settings.branding.save'), [
        'platform_logo' => UploadedFile::fake()->create('not-a-logo.txt', 4, 'text/plain'),
    ]);

    $response->assertSessionHasErrors('platform_logo');
});

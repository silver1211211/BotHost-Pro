<?php

use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperType;
use App\Models\RuntimeHelperVersion;
use App\Models\User;
use App\Services\RuntimeHelperBundleGenerator;
use App\Services\RuntimeHelperSafetyScanner;

it('admin can create edit and deactivate helper types', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);

    $this->actingAs($admin)
        ->post(route('admin.runtime.helper-types.store'), [
            'name' => 'Webhook Tools',
            'slug' => 'webhook_tools',
            'description' => 'Webhook helpers',
            'sort_order' => 15,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Helper type created.');

    $type = RuntimeHelperType::query()->where('slug', 'webhook_tools')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.runtime.helper-types.update', $type), [
            'name' => 'Webhook Utilities',
            'slug' => 'webhook_tools',
            'description' => 'Updated',
            'sort_order' => 20,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Helper type updated.');

    expect($type->fresh()->name)->toBe('Webhook Utilities')
        ->and($type->fresh()->sort_order)->toBe(20);

    $this->actingAs($admin)
        ->patch(route('admin.runtime.helper-types.toggle', $type))
        ->assertRedirect()
        ->assertSessionHas('status', 'Helper type deactivated.');

    expect($type->fresh()->is_active)->toBeFalse();
});

it('admin cannot create duplicate helper type slugs', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    RuntimeHelperType::query()->create(['name' => 'Telegram Custom', 'slug' => 'telegram_custom', 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.runtime.helper-types.store'), [
            'name' => 'Duplicate',
            'slug' => 'telegram_custom',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('slug');
});

it('helper create and edit pages load helper types from database', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $type = RuntimeHelperType::query()->create(['name' => 'Custom API', 'slug' => 'custom_api', 'is_active' => true]);
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Custom',
        'slug' => 'custom',
        'helper_type' => $type->slug,
        'is_active' => true,
    ]);
    $helper = RuntimeHelper::query()->create([
        'category_id' => $category->id,
        'name' => 'customApiHelper',
        'label' => 'Custom API Helper',
        'helper_type' => $type->slug,
        'code' => 'return true;',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.runtime.helpers.create'))
        ->assertOk()
        ->assertSee('Custom API (custom_api)');

    $this->actingAs($admin)
        ->get(route('admin.runtime.helpers.edit', $helper))
        ->assertOk()
        ->assertSee('Custom API (custom_api)')
        ->assertSee('Custom API Helper');
});

it('runtime helper can save and publish with a custom helper type', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $type = RuntimeHelperType::query()->create(['name' => 'Custom API', 'slug' => 'custom_api', 'is_active' => true]);
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Custom API',
        'slug' => 'custom_api_category',
        'helper_type' => $type->slug,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.runtime.helpers.store'), [
            'category_id' => $category->id,
            'name' => 'customApiPing',
            'label' => 'Custom API Ping',
            'description' => 'Ping helper',
            'helper_type' => $type->slug,
            'code' => 'return { ok: true, type: "custom_api" };',
            'parameters_schema' => '',
            'return_schema' => '',
            'allowed_domains' => '',
            'timeout_ms' => 5000,
            'permission_level' => 0,
            'expose_to_bot_code' => '1',
            'show_in_helper_list' => '1',
            'change_summary' => 'Initial',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $helper = RuntimeHelper::query()->where('name', 'customApiPing')->firstOrFail();
    $version = $helper->versions()->firstOrFail();
    $helper->update([
        'status' => 'active',
        'active_version_id' => $version->id,
    ]);
    $version->update(['status' => 'active']);

    $report = (new RuntimeHelperBundleGenerator(new RuntimeHelperSafetyScanner()))->generateContent();

    expect($helper->fresh()->helper_type)->toBe('custom_api')
        ->and($report['ok'])->toBeTrue()
        ->and($report['content'])->toContain('customApiPing');
});

it('existing legacy helper type values remain visible and usable after migration', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $type = RuntimeHelperType::query()->where('slug', 'storage')->firstOrFail();
    $type->update(['is_active' => false]);
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Storage',
        'slug' => 'storage_test',
        'helper_type' => 'storage',
        'is_active' => true,
    ]);
    $helper = RuntimeHelper::query()->create([
        'category_id' => $category->id,
        'name' => 'storageLegacyHelper',
        'label' => 'Storage Legacy Helper',
        'helper_type' => 'storage',
        'code' => 'return true;',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.runtime.helpers.edit', $helper))
        ->assertOk()
        ->assertSee('Storage (storage) - inactive');
});

it('deleting type used by helpers is blocked safely', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $type = RuntimeHelperType::query()->create(['name' => 'Blocked Type', 'slug' => 'blocked_type', 'is_active' => true]);
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Blocked',
        'slug' => 'blocked',
        'helper_type' => $type->slug,
        'is_active' => true,
    ]);
    RuntimeHelper::query()->create([
        'category_id' => $category->id,
        'name' => 'blockedTypeHelper',
        'label' => 'Blocked Type Helper',
        'helper_type' => $type->slug,
        'code' => 'return true;',
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.runtime.helper-types.destroy', $type))
        ->assertRedirect()
        ->assertSessionHas('error', 'Cannot delete helper type because helpers or categories are using it.');

    expect(RuntimeHelperType::query()->whereKey($type->id)->exists())->toBeTrue();
});

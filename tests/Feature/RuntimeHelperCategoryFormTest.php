<?php

use App\Models\RuntimeHelperCategory;
use App\Models\User;

it('renders helper category create form with visible labels and help text', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.runtime.helper-categories.create'))
        ->assertOk()
        ->assertSee('Category name')
        ->assertSee('Slug / key')
        ->assertSee('Description')
        ->assertSee('Active category')
        ->assertSee('Visible admin name used to group related Runtime Helpers')
        ->assertSee('Stable internal key for this category')
        ->assertSee('Admin-only grouping note')
        ->assertSee('Active categories can be selected');
});

it('renders helper category edit form with existing values and clear field text', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'FaucetPay Helpers',
        'slug' => 'faucetpay_helpers',
        'description' => 'Admin-only FaucetPay grouping note.',
        'helper_type' => 'validation',
        'allowed_domains' => ['api.faucetpay.io'],
        'default_timeout_ms' => 5000,
        'permission_level' => 0,
        'sort_order' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.runtime.helper-categories.edit', $category))
        ->assertOk()
        ->assertSee('FaucetPay Helpers')
        ->assertSee('faucetpay_helpers')
        ->assertSee('api.faucetpay.io')
        ->assertSee('Default timeout (ms)')
        ->assertSee('Permission level')
        ->assertSee('Sort order');
});

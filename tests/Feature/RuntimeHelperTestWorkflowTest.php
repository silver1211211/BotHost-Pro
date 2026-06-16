<?php

use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperTest;
use App\Models\RuntimeHelperVersion;
use App\Models\User;

function runtimeHelperTestFixture(string $code = 'return { ok: true };'): array
{
    $category = RuntimeHelperCategory::query()->create([
        'name' => 'Utility',
        'slug' => 'test-utility-'.uniqid(),
        'helper_type' => 'utility',
        'allowed_domains' => [],
        'is_active' => true,
    ]);

    $helper = RuntimeHelper::query()->create([
        'category_id' => $category->id,
        'name' => 'testHelper'.uniqid(),
        'label' => 'Test Helper',
        'helper_type' => 'utility',
        'code' => $code,
        'status' => 'draft',
        'expose_to_bot_code' => true,
    ]);

    $version = RuntimeHelperVersion::query()->create([
        'helper_id' => $helper->id,
        'version_number' => 1,
        'code' => $code,
        'safety_scan_status' => 'passed',
        'syntax_check_status' => 'passed',
        'test_status' => 'not_run',
        'status' => 'draft',
    ]);

    return [$helper, $version];
}

test('runtime helper test route validates invalid params json', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson(route('admin.runtime.helpers.test'), [
        'helper_type' => 'utility',
        'code' => 'return true;',
        'params' => '{bad json',
        'dry_run' => true,
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('params');
});

test('runtime helper test route returns json result', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson(route('admin.runtime.helpers.test'), [
        'helper_type' => 'utility',
        'code' => 'return { ok: true, name: params.name };',
        'params' => '{"name":"Ada"}',
        'dry_run' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 'passed')
        ->assertJsonPath('actual_output.helper_result.name', 'Ada');
});

test('runtime helper test stores record for existing helper', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [$helper, $version] = runtimeHelperTestFixture('return { ok: true };');

    $response = $this->actingAs($admin)->postJson(route('admin.runtime.helpers.test'), [
        'helper_id' => $helper->id,
        'version_id' => $version->id,
        'name' => $helper->name,
        'helper_type' => 'utility',
        'code' => 'return { ok: true };',
        'params' => '{}',
        'dry_run' => true,
    ]);

    $response->assertOk()->assertJsonPath('status', 'passed');

    expect(RuntimeHelperTest::query()->where('helper_id', $helper->id)->where('version_id', $version->id)->count())->toBe(1)
        ->and($helper->fresh()->last_test_status)->toBe('passed')
        ->and($version->fresh()->test_status)->toBe('passed');
});

<?php

use App\Models\User;

it('admin can suspend and unsuspend users', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.suspend', $user), [
            'suspend_type' => 'timed',
            'days' => 7,
            'message' => 'Policy review',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe('suspended')
        ->and($user->fresh()->suspended_until)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.users.activate', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe('active')
        ->and($user->fresh()->suspended_until)->toBeNull();
});

it('admin can ban and unban users', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $user), ['status' => 'banned'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe('banned');

    $this->actingAs($admin)
        ->post(route('admin.users.activate', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->status)->toBe('active');
});

it('admin user action routes render and do not return server errors', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $user = User::factory()->create(['status' => 'active']);
    $suspendedUser = User::factory()->create(['status' => 'suspended']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee(route('admin.users.suspend', $user))
        ->assertSee(route('admin.users.activate', $suspendedUser))
        ->assertSee(route('admin.users.status', $user))
        ->assertSee(route('admin.users.role', $user))
        ->assertSee(route('admin.users.plan', $user))
        ->assertSee(route('admin.users.destroy', $user));
});

it('admin cannot delete themselves or another admin account', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $otherAdmin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect()
        ->assertSessionHas('error', 'This user cannot be deleted.');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $otherAdmin))
        ->assertRedirect()
        ->assertSessionHas('error', 'This user cannot be deleted.');

    expect(User::withTrashed()->find($admin->id)->trashed())->toBeFalse()
        ->and(User::withTrashed()->find($otherAdmin->id)->trashed())->toBeFalse();
});

it('admin can safely soft delete a normal user', function (): void {
    $admin = User::factory()->create(['role' => 'admin', 'subscription_plan' => 'business']);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'User deleted successfully.');

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
});

it('non admin cannot delete users', function (): void {
    $actor = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($actor)
        ->delete(route('admin.users.destroy', $user))
        ->assertForbidden();

    expect(User::withTrashed()->find($user->id)->trashed())->toBeFalse();
});

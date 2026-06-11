<?php

use App\Mail\BroadcastEmail;
use App\Models\AdminBroadcast;
use App\Models\AdminBroadcastDelivery;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function adminBroadcastPayload(array $overrides = []): array
{
    return array_merge([
        'campaign_name' => 'Launch Update',
        'campaign_type' => 'announcement',
        'title' => 'Important update',
        'message' => 'Hello from BotHost Pro.',
        'message_type' => 'text',
        'priority' => 'normal',
        'channels' => ['in_app'],
        'target_type' => 'all_users',
        'batch_size' => 100,
        'batch_delay_seconds' => 1,
    ], $overrides);
}

function adminBroadcastBot(User $owner, array $overrides = []): Bot
{
    return Bot::create(array_merge([
        'user_id' => $owner->id,
        'name' => 'Demo Bot '.uniqid(),
        'slug' => 'demo-bot-'.uniqid(),
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
        'token_encrypted' => '123456:ABCDEF',
        'token_verified_at' => now(),
    ], $overrides));
}

test('admin can send in app broadcast notifications', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['status' => 'active']);
    User::factory()->create(['status' => 'banned']);

    $response = $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['in_app'],
    ]));

    $response->assertRedirect(route('admin.broadcasts.index'));

    expect(UserNotification::query()->where('user_id', $admin->id)->count())->toBe(1)
        ->and(UserNotification::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(UserNotification::count())->toBe(2);
    expect(AdminBroadcast::first()->sent_count)->toBe(2);
});

test('admin can send email broadcasts using mail settings', function () {
    Mail::fake();
    PlatformSetting::setValue('mail_enabled', '1');
    PlatformSetting::setValue('mail_from_address', 'admin@example.com');
    PlatformSetting::setValue('mail_from_name', 'BotHost Pro');

    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['email'],
    ]))->assertRedirect(route('admin.broadcasts.index'));

    Mail::assertSent(BroadcastEmail::class, 2);
    expect(AdminBroadcastDelivery::where('channel', 'email')->where('status', 'sent')->count())->toBe(2)
        ->and(AdminBroadcast::first()->sent_count)->toBe(2);
});

test('email broadcast fails cleanly when mail is disabled', function () {
    PlatformSetting::setValue('mail_enabled', '0');

    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['status' => 'active']);

    $response = $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['email'],
    ]));

    $response->assertSessionHasErrors('channels');
    expect(AdminBroadcast::count())->toBe(0);
});

test('admin can send telegram broadcast through a verified recycled bot', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 123],
        ]),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $bot = adminBroadcastBot($admin, [
        'status' => 'paused',
        'token_encrypted' => '123456:ABCDEF',
        'token_verified_at' => now(),
    ]);
    $bot->delete();

    BotUser::create([
        'bot_id' => $bot->id,
        'telegram_user_id' => '10001',
        'status' => 'active',
        'first_seen_at' => now(),
        'last_active_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['telegram'],
        'target_bot_id' => $bot->id,
    ]))->assertRedirect(route('admin.broadcasts.index'));

    expect(AdminBroadcastDelivery::where('channel', 'telegram')->where('status', 'sent')->count())->toBe(1)
        ->and(AdminBroadcast::first()->sent_count)->toBe(1);
});

test('telegram broadcast requires an eligible verified bot', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $bot = adminBroadcastBot($admin, [
        'status' => 'paused',
        'token_encrypted' => null,
        'token_verified_at' => null,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['telegram'],
        'target_bot_id' => $bot->id,
    ]));

    $response->assertSessionHasErrors('target_bot_id');
    expect(AdminBroadcast::count())->toBe(0);
});

test('admin can create platform announcement broadcast', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['status' => 'active']);

    $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['platform'],
    ]))->assertRedirect(route('admin.broadcasts.index'));

    expect(PlatformAnnouncement::count())->toBe(1)
        ->and(AdminBroadcastDelivery::where('channel', 'platform')->where('status', 'sent')->count())->toBe(1);

    $this->actingAs($user)->get(route('dashboard'))->assertSee('Important update');
});

test('admin can send all broadcast channels together', function () {
    Mail::fake();
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 456],
        ]),
    ]);
    PlatformSetting::setValue('mail_enabled', '1');

    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['status' => 'active']);
    $bot = adminBroadcastBot($admin, [
        'status' => 'running',
        'token_encrypted' => '123456:ABCDEF',
        'token_verified_at' => now(),
    ]);
    BotUser::create([
        'bot_id' => $bot->id,
        'telegram_user_id' => '10002',
        'status' => 'active',
        'first_seen_at' => now(),
        'last_active_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.broadcasts.store'), adminBroadcastPayload([
        'channels' => ['in_app', 'email', 'telegram', 'platform'],
        'target_bot_id' => $bot->id,
    ]))->assertRedirect(route('admin.broadcasts.index'));

    expect(UserNotification::count())->toBe(2)
        ->and(PlatformAnnouncement::count())->toBe(1)
        ->and(AdminBroadcastDelivery::where('channel', 'telegram')->where('status', 'sent')->count())->toBe(1)
        ->and(AdminBroadcast::first()->sent_count)->toBe(6);
});

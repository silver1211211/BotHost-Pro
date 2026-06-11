<?php

use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Models\BotUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function broadcastTestUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'status' => 'active',
        'subscription_plan' => 'free',
    ], $attributes));
}

function broadcastTestBot(User $user): Bot
{
    return Bot::create([
        'user_id' => $user->id,
        'name' => 'Broadcast Bot',
        'slug' => 'broadcast-bot-'.str()->random(8),
        'token_encrypted' => '123456:AA-secret-token-'.str()->random(8),
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
        'token_verified_at' => now(),
    ]);
}

function broadcastTestBotUser(Bot $bot, string $telegramId, array $attributes = []): BotUser
{
    return BotUser::create(array_merge([
        'bot_id' => $bot->id,
        'telegram_user_id' => $telegramId,
        'telegram_first_name' => 'Test',
        'status' => 'active',
        'first_seen_at' => now()->subDay(),
        'last_active_at' => now(),
    ], $attributes));
}

it('previews target count with plan limit and estimated send time', function (): void {
    config([
        'broadcasts.limits.free' => 3,
        'broadcasts.batch_size' => 2,
        'broadcasts.message_delay_ms' => 100,
        'broadcasts.batch_delay_seconds' => 1,
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);

    foreach (range(1, 5) as $i) {
        broadcastTestBotUser($bot, (string) (1000 + $i), [
            'last_active_at' => now()->subMinutes($i),
        ]);
    }

    $this->actingAs($user)
        ->getJson(route('bots.broadcasts.target-count', [$bot, 'target_type' => 'all_active']))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'count' => 3,
            'eligible_count' => 5,
            'limit_applied' => true,
            'applied_limit' => 3,
            'estimated_seconds' => 2,
            'estimated_human' => 'About 2 seconds',
        ]);
});

it('sends text broadcasts with CTA, parse mode, and disabled previews', function (): void {
    config([
        'broadcasts.batch_size' => 20,
        'broadcasts.message_delay_ms' => 100,
    ]);

    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 321],
        ]),
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    broadcastTestBotUser($bot, '111');

    $this->actingAs($user)
        ->post(route('bots.broadcasts.store', $bot), [
            'message' => '<b>Hello</b> https://example.com',
            'target_type' => 'all_active',
            'cta_text' => 'Open',
            'cta_url' => 'https://example.com',
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect($broadcast->message_type)->toBe('text')
        ->and($broadcast->estimated_seconds)->toBe(1);

    $this->actingAs($user)
        ->post(route('bots.broadcasts.start', [$bot, $broadcast]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast->refresh();

    expect($broadcast->sent_count)->toBe(1)
        ->and($broadcast->failed_count)->toBe(0)
        ->and($broadcast->status)->toBe('completed');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && $request['chat_id'] === '111'
        && $request['text'] === '<b>Hello</b> https://example.com'
        && $request['parse_mode'] === 'HTML'
        && $request['disable_web_page_preview'] === true
        && $request['reply_markup']['inline_keyboard'][0][0]['text'] === 'Open'
        && $request['reply_markup']['inline_keyboard'][0][0]['url'] === 'https://example.com');
});

it('uploads and sends image broadcasts with caption and CTA', function (): void {
    config(['broadcasts.message_delay_ms' => 0]);
    Storage::fake('public');

    Http::fake([
        'https://api.telegram.org/*/sendPhoto' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 654],
        ]),
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    broadcastTestBotUser($bot, '222');

    $this->actingAs($user)
        ->post(route('bots.broadcasts.store', $bot), [
            'message_type' => 'image',
            'message' => 'Image test',
            'image' => UploadedFile::fake()->image('promo.jpg', 640, 360)->size(512),
            'target_type' => 'all_active',
            'cta_text' => 'Open',
            'cta_url' => 'https://example.com',
            'parse_mode' => 'Markdown',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect($broadcast->message_type)->toBe('image')
        ->and($broadcast->image_path)->not->toBeNull()
        ->and($broadcast->image_size)->toBeGreaterThan(0);

    Storage::disk('public')->assertExists($broadcast->image_path);

    $this->actingAs($user)
        ->post(route('bots.broadcasts.start', [$bot, $broadcast]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast->refresh();

    expect($broadcast->sent_count)->toBe(1)
        ->and($broadcast->failed_count)->toBe(0);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto'));
});

it('rejects oversized images and unsafe CTA URLs', function (): void {
    config(['broadcasts.image.max_size_kb' => 1]);
    Storage::fake('public');

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);

    $this->actingAs($user)
        ->from(route('bots.show', $bot))
        ->post(route('bots.broadcasts.store', $bot), [
            'message_type' => 'image',
            'image' => UploadedFile::fake()->image('too-large.jpg')->size(2),
            'target_type' => 'all_active',
        ])
        ->assertRedirect(route('bots.show', $bot))
        ->assertSessionHasErrors('image');

    $this->actingAs($user)
        ->from(route('bots.show', $bot))
        ->post(route('bots.broadcasts.store', $bot), [
            'message' => 'Hello',
            'target_type' => 'all_active',
            'cta_text' => 'Open',
            'cta_url' => 'javascript:alert(1)',
        ])
        ->assertRedirect(route('bots.show', $bot))
        ->assertSessionHasErrors('cta_url');
});

it('test send uses broadcast sending logic without changing counters', function (): void {
    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 987],
        ]),
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    broadcastTestBotUser($bot, '333');

    $broadcast = BotBroadcast::create([
        'bot_id' => $bot->id,
        'user_id' => $user->id,
        'message' => 'Test send only',
        'message_type' => 'text',
        'target_type' => 'all_active',
        'target_count' => 1,
        'sent_count' => 0,
        'failed_count' => 0,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post(route('bots.broadcasts.test-send', [$bot, $broadcast]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast->refresh();

    expect($broadcast->sent_count)->toBe(0)
        ->and($broadcast->failed_count)->toBe(0)
        ->and($broadcast->recipients()->count())->toBe(0);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
        && $request['chat_id'] === '333'
        && $request['text'] === 'Test send only');
});

it('lets admins apply a custom recipient limit and ignores that field for normal users', function (): void {
    config([
        'broadcasts.batch_size' => 20,
        'broadcasts.message_delay_ms' => 0,
    ]);

    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 456],
        ]),
    ]);

    $admin = broadcastTestUser(['role' => 'admin', 'subscription_plan' => 'free']);
    $adminBot = broadcastTestBot($admin);

    foreach (range(1, 3) as $i) {
        broadcastTestBotUser($adminBot, (string) (4000 + $i), [
            'last_active_at' => now()->subMinutes($i),
        ]);
    }

    $this->actingAs($admin)
        ->getJson(route('bots.broadcasts.target-count', [
            $adminBot,
            'target_type' => 'all_active',
            'custom_recipient_limit' => 1,
        ]))
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'count' => 1,
            'eligible_count' => 3,
            'custom_recipient_limit' => 1,
            'custom_limit_applied' => true,
        ]);

    $this->actingAs($admin)
        ->post(route('bots.broadcasts.store', $adminBot), [
            'message' => 'Admin limited broadcast',
            'target_type' => 'all_active',
            'custom_recipient_limit' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $adminBroadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect(data_get($adminBroadcast->metadata, 'custom_recipient_limit'))->toBe(1)
        ->and(data_get($adminBroadcast->metadata, 'custom_limit_applied'))->toBeTrue()
        ->and(data_get($adminBroadcast->metadata, 'set_by_admin_id'))->toBe($admin->id);

    $this->actingAs($admin)
        ->post(route('bots.broadcasts.start', [$adminBot, $adminBroadcast]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($adminBroadcast->fresh()->recipients()->count())->toBe(1);

    $user = broadcastTestUser();
    $userBot = broadcastTestBot($user);

    foreach (range(1, 3) as $i) {
        broadcastTestBotUser($userBot, (string) (5000 + $i), [
            'last_active_at' => now()->subMinutes($i),
        ]);
    }

    $this->actingAs($user)
        ->post(route('bots.broadcasts.store', $userBot), [
            'message' => 'Normal user cannot limit this way',
            'target_type' => 'all_active',
            'custom_recipient_limit' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $userBroadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect($userBroadcast->target_count)->toBe(3)
        ->and(data_get($userBroadcast->metadata, 'custom_recipient_limit'))->toBeNull()
        ->and(data_get($userBroadcast->metadata, 'custom_limit_applied'))->toBeFalse();
});

it('sends immediately from the composer without requiring a draft start step', function (): void {
    config(['broadcasts.message_delay_ms' => 0]);

    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 741],
        ]),
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    broadcastTestBotUser($bot, '777');

    $this->actingAs($user)
        ->post(route('bots.broadcasts.store', $bot), [
            'message' => 'Send this immediately',
            'target_type' => 'all_active',
            'send_now' => '1',
        ])
        ->assertRedirect(route('bots.show', [
            'bot' => $bot,
            'tab' => 'admin',
            'admin_tab' => 'broadcasts',
        ]))
        ->assertSessionHasNoErrors();

    $broadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect($broadcast->status)->toBe('completed')
        ->and($broadcast->sent_count)->toBe(1)
        ->and($broadcast->recipients()->where('telegram_user_id', '777')->exists())->toBeTrue();
});

it('returns bot user status actions to the users admin section', function (): void {
    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    $botUser = broadcastTestBotUser($bot, '123123');

    $this->actingAs($user)
        ->patch(route('bots.users.block', [$bot, $botUser]))
        ->assertRedirect(route('bots.show', [
            'bot' => $bot,
            'tab' => 'admin',
            'admin_tab' => 'users',
        ]));

    expect($botUser->fresh()->status)->toBe('blocked');
});

it('matches uploaded phone recipients to tracked bot users for broadcasts', function (): void {
    config(['broadcasts.message_delay_ms' => 0]);

    Http::fake([
        'https://api.telegram.org/*/sendMessage' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 852],
        ]),
    ]);

    $user = broadcastTestUser();
    $bot = broadcastTestBot($user);
    broadcastTestBotUser($bot, '888', [
        'metadata' => ['phone' => '+234 801 234 5678'],
    ]);
    broadcastTestBotUser($bot, '999', [
        'metadata' => ['phone' => '+234 809 999 9999'],
    ]);

    $this->actingAs($user)
        ->post(route('bots.broadcasts.store', $bot), [
            'message' => 'Uploaded phone broadcast',
            'target_type' => 'specific_users',
            'recipient_file' => UploadedFile::fake()->createWithContent('recipients.csv', "+2348012345678\n"),
            'send_now' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $broadcast = BotBroadcast::query()->latest('id')->firstOrFail();

    expect($broadcast->status)->toBe('completed')
        ->and($broadcast->sent_count)->toBe(1)
        ->and($broadcast->recipients()->pluck('telegram_user_id')->all())->toBe(['888'])
        ->and(data_get($broadcast->metadata, 'specific_uploaded_count'))->toBe(1);
});

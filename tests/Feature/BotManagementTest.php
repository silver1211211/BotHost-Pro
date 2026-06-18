<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_custom_bot_with_encrypted_token(): void
    {
        config(['app.public_url' => 'https://brave-files-clean.loca.lt']);

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123456789,
                    'is_bot' => true,
                    'first_name' => 'Support Desk',
                    'username' => 'support_desk_bot',
                    'can_join_groups' => true,
                    'can_read_all_group_messages' => false,
                    'supports_inline_queries' => false,
                ],
            ]),
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/dashboard/bots', [
            'token' => '123456:AA-secret-token',
            'name' => 'Support Desk',
            'setup_type' => 'custom',
        ]);

        $bot = Bot::query()->firstOrFail();

        $response->assertRedirect(route('bots.show', $bot));
        $this->assertSame($user->id, $bot->user_id);
        $this->assertSame('stopped', $bot->status);
        $this->assertSame('javascript', $bot->language);
        $this->assertSame('custom', $bot->setup_type);
        $this->assertSame('123456:AA-secret-token', $bot->token_encrypted);
        $this->assertNotSame('123456:AA-secret-token', $bot->getRawOriginal('token_encrypted'));
        $this->assertSame('123456:AA-secret-token', Crypt::decryptString($bot->getRawOriginal('token_encrypted')));
        $this->assertSame('123456789', $bot->telegram_bot_id);
        $this->assertSame('support_desk_bot', $bot->telegram_username);
        $this->assertSame('Support Desk', $bot->telegram_first_name);
        $this->assertTrue($bot->telegram_can_join_groups);
        $this->assertFalse($bot->telegram_can_read_all_group_messages);
        $this->assertFalse($bot->telegram_supports_inline_queries);
        $this->assertNotNull($bot->token_verified_at);
        $this->assertSame('active', $bot->webhook_status);
        $this->assertNotNull($bot->webhook_secret);
        $this->assertSame('https://brave-files-clean.loca.lt/telegram/webhook/'.$bot->id.'/'.$bot->webhook_secret, $bot->webhook_url);
        $this->assertNotNull($bot->webhook_set_at);
        $this->assertDatabaseHas('bot_settings', ['bot_id' => $bot->id]);
    }

    public function test_created_bot_marks_webhook_failed_when_public_url_is_local(): void
    {
        config(['app.public_url' => 'http://127.0.0.1:8000']);

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123456789,
                    'is_bot' => true,
                    'first_name' => 'Support Desk',
                    'username' => 'support_desk_bot',
                ],
            ]),
            'api.telegram.org/*/deleteWebhook' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => '123456:AA-secret-token',
            'name' => 'Support Desk',
            'setup_type' => 'custom',
        ])->assertRedirect();

        $bot = Bot::query()->firstOrFail();

        $this->assertSame('failed', $bot->webhook_status);
        $this->assertSame('Telegram requires a public HTTPS webhook URL. Set the public callback URL to your Cloudflare Tunnel, LocalTunnel, or ngrok HTTPS URL.', $bot->webhook_last_error);
        $this->assertNull($bot->webhook_url);
    }

    public function test_invalid_telegram_token_does_not_create_bot(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => false,
                'description' => 'Unauthorized',
            ], 401),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => 'fake-token',
            'name' => 'Support Desk',
            'setup_type' => 'custom',
        ])->assertSessionHasErrors([
            'token' => 'Invalid Telegram bot token. Please check the token from BotFather.',
        ]);

        $this->assertDatabaseCount('bots', 0);
    }

    public function test_recycled_bot_token_cannot_be_used_to_create_another_bot(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $bot = Bot::create([
            'user_id' => $user->id,
            'name' => 'Recycled Bot',
            'slug' => 'recycled-bot',
            'token_encrypted' => '123456:AA-recycled-token',
            'status' => 'stopped',
            'language' => 'javascript',
            'setup_type' => 'custom',
        ]);

        $bot->delete();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => '123456:AA-recycled-token',
            'name' => 'New Bot',
            'setup_type' => 'custom',
        ])->assertSessionHasErrors([
            'token' => 'This bot token is already connected to another workspace.',
        ]);

        Http::assertNothingSent();
        $this->assertSame(1, Bot::withTrashed()->count());
        $this->assertSame(0, Bot::query()->count());
    }

    public function test_recycled_bot_can_be_permanently_deleted_and_token_reused(): void
    {
        config(['app.public_url' => 'http://127.0.0.1:8000']);

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 987654321,
                    'is_bot' => true,
                    'first_name' => 'Reused Bot',
                    'username' => 'reused_bot',
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $bot = Bot::create([
            'user_id' => $user->id,
            'name' => 'Recycled Bot',
            'slug' => 'recycled-bot',
            'token_encrypted' => '123456:AA-reusable-token',
            'status' => 'stopped',
            'language' => 'javascript',
            'setup_type' => 'custom',
        ]);

        DB::table('bot_runtime_data')->insert([
            'bot_id' => $bot->id,
            'key' => 'state',
            'value' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bot->delete();

        $this->actingAs($user)
            ->delete(route('recycle-bin.bots.force-delete', $bot->id))
            ->assertRedirect()
            ->assertSessionHas('status', 'Bot permanently deleted.');

        $this->assertSame(0, Bot::withTrashed()->whereKey($bot->id)->count());
        $this->assertDatabaseMissing('bot_runtime_data', ['bot_id' => $bot->id]);

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => '123456:AA-reusable-token',
            'name' => 'New Bot',
            'setup_type' => 'custom',
        ])->assertRedirect();

        $this->assertSame(1, Bot::query()->count());
        $this->assertSame('New Bot', Bot::query()->first()->name);
    }

    public function test_malformed_telegram_token_does_not_call_telegram(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => 'not a token',
            'name' => 'Support Desk',
            'setup_type' => 'custom',
        ])->assertSessionHasErrors([
            'token' => 'Invalid Telegram bot token. Please check the token from BotFather.',
        ]);

        Http::assertNothingSent();
        $this->assertDatabaseCount('bots', 0);
    }

    public function test_token_pasted_with_whitespace_is_verified_and_stored_trimmed(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 123456789,
                    'is_bot' => true,
                    'first_name' => 'Support Desk',
                    'username' => 'support_desk_bot',
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => "  123456:AA-secret-token-with-whitespace  \n",
            'name' => 'Support Desk',
            'setup_type' => 'custom',
        ])->assertRedirect();

        $bot = Bot::query()->firstOrFail();

        $this->assertSame('123456:AA-secret-token-with-whitespace', $bot->token_encrypted);
        $this->assertSame('123456:AA-secret-token-with-whitespace', Crypt::decryptString($bot->getRawOriginal('token_encrypted')));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bot123456:AA-secret-token-with-whitespace/getMe'));
    }

    public function test_template_setup_is_rejected_until_templates_are_ready(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/bots', [
            'token' => '123456:AA-secret-token',
            'name' => 'Template Bot',
            'setup_type' => 'template',
        ])->assertSessionHasErrors('setup_type');

        $this->assertDatabaseCount('bots', 0);
    }

    public function test_user_cannot_view_another_users_bot(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $bot = $this->createBot($owner);

        $this->actingAs($intruder)->get(route('bots.show', $bot))->assertForbidden();
    }

    public function test_user_can_create_command_for_own_bot(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => '/deposit',
            'response_text' => 'Deposit instructions will be sent here.',
            'status' => 'active',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']));

        $this->assertDatabaseHas('bot_commands', [
            'bot_id' => $bot->id,
            'command_name' => '/deposit',
            'code' => null,
            'response_type' => 'code',
            'status' => 'active',
        ]);
    }

    public function test_command_rejects_extra_spaces_and_duplicates_per_bot(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => 'go  now',
            'response_text' => 'Invalid spacing.',
            'status' => 'active',
        ])->assertSessionHasErrors('command_name');

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => '/start',
            'response_text' => 'Welcome.',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => '/start',
            'response_text' => 'Duplicate.',
            'status' => 'active',
        ])->assertSessionHasErrors('command_name');
    }

    public function test_user_can_update_command_settings_code_and_delete_command(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $command = $this->createCommand($bot, '/start');

        $this->actingAs($user)->put(route('bots.commands.update', [$bot, $command]), [
            'command_name' => '/welcome',
            'status' => 'inactive',
            'is_pinned' => '1',
            'admin_only' => '1',
            'aliases' => '["/hi", " hello "]',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']));

        $command->refresh();
        $this->assertSame('/welcome', $command->command_name);
        $this->assertSame('inactive', $command->status);
        $this->assertTrue($command->is_pinned);
        $this->assertTrue($command->admin_only);
        $this->assertSame(['/hi', 'hello'], $command->aliases);

        $this->actingAs($user)->put(route('bots.commands.code.update', [$bot, $command]), [
            'code' => 'await ctx.reply("Saved");',
        ])->assertRedirect(route('bots.commands.code', [$bot, $command]));

        $this->assertDatabaseHas('bot_commands', [
            'id' => $command->id,
            'code' => 'await ctx.reply("Saved");',
            'response_type' => 'code',
        ]);

        $this->actingAs($user)->delete(route('bots.commands.destroy', [$bot, $command]))
            ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']));

        $this->assertDatabaseMissing('bot_commands', ['id' => $command->id]);
    }

    public function test_user_cannot_access_another_users_command(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $bot = $this->createBot($owner);
        $command = $this->createCommand($bot, '/secret');

        $this->actingAs($intruder)
            ->get(route('bots.commands.code', [$bot, $command]))
            ->assertForbidden();
    }

    public function test_command_must_belong_to_selected_bot(): void
    {
        $user = User::factory()->create();
        $firstBot = $this->createBot($user);
        $secondBot = $this->createBot($user, 'Second Bot', 'second-bot');
        $command = $this->createCommand($secondBot, '/other');

        $this->actingAs($user)
            ->get(route('bots.commands.edit', [$firstBot, $command]))
            ->assertForbidden();
    }

    public function test_deleted_command_is_permanently_removed_and_trigger_can_be_reused(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $command = $this->createCommand($bot, '/start');

        $this->actingAs($user)
            ->delete(route('bots.commands.destroy', [$bot, $command]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Command permanently deleted.');

        $this->assertDatabaseMissing('bot_commands', ['id' => $command->id]);

        $this->actingAs($user)
            ->post(route('bots.commands.store', $bot), [
                'trigger_type' => 'slash',
                'command_name' => '/start',
                'code' => 'await reply("again");',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bot_commands', [
            'bot_id' => $bot->id,
            'command_name' => '/start',
            'deleted_at' => null,
        ]);
    }

    public function test_recycle_bin_page_does_not_show_deleted_commands(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $command = $this->createCommand($bot, '/delete-me');
        $command->delete();

        $this->actingAs($user)
            ->get(route('recycle-bin.index'))
            ->assertOk()
            ->assertSee('openConfirm', false)
            ->assertDontSee('Deleted Commands')
            ->assertDontSee('/delete-me')
            ->assertDontSee('confirm(', false);
    }

    public function test_user_can_update_bot_name_and_token_from_workspace_settings(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 987654,
                    'is_bot' => true,
                    'first_name' => 'Renamed Telegram Bot',
                    'username' => 'renamed_telegram_bot',
                    'can_join_groups' => false,
                    'can_read_all_group_messages' => true,
                    'supports_inline_queries' => true,
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);

        $this->actingAs($user)->patch(route('bots.settings.update', $bot), [
            'name' => 'Renamed Bot',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'settings']));

        $bot->refresh();
        $this->assertSame('Renamed Bot', $bot->name);
        $this->assertSame('renamed-bot', $bot->slug);

        $this->actingAs($user)->patch(route('bots.settings.update', $bot), [
            'token' => '987654:BB-new-secret',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'settings']));

        $bot->refresh();
        $this->assertSame('987654:BB-new-secret', $bot->token_encrypted);
        $this->assertNotSame('987654:BB-new-secret', $bot->getRawOriginal('token_encrypted'));
        $this->assertSame('987654', $bot->telegram_bot_id);
        $this->assertSame('renamed_telegram_bot', $bot->telegram_username);
        $this->assertSame('Renamed Telegram Bot', $bot->telegram_first_name);
        $this->assertFalse($bot->telegram_can_join_groups);
        $this->assertTrue($bot->telegram_can_read_all_group_messages);
        $this->assertTrue($bot->telegram_supports_inline_queries);
        $this->assertNotNull($bot->token_verified_at);
    }

    public function test_token_update_rotates_webhook_secret_and_old_webhook_path_cannot_affect_bot(): void
    {
        config(['app.public_url' => 'https://abc123.ngrok-free.app']);

        Http::fake([
            'api.telegram.org/bot987654:BB-new-secret/getMe' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 987654,
                    'is_bot' => true,
                    'first_name' => 'New Bot',
                    'username' => 'new_bot',
                ],
            ]),
            'api.telegram.org/bot123456:AA-secret-token-owned-bot/deleteWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/bot987654:BB-new-secret/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/bot987654:BB-new-secret/getWebhookInfo' => Http::response(['ok' => true, 'result' => ['url' => '']]),
            'api.telegram.org/bot987654:BB-new-secret/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'old-secret',
            'webhook_status' => 'active',
            'token_verified_at' => now(),
        ]);
        $this->createCommand($bot, '/start', 'New token response');

        $this->actingAs($user)->patch(route('bots.settings.update', $bot), [
            'token' => '987654:BB-new-secret',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'settings']));

        $bot->refresh();
        $this->assertNotSame('old-secret', $bot->webhook_secret);

        $this->postJson(route('telegram.webhook', [$bot, 'old-secret']), [
            'message' => ['text' => '/start', 'chat' => ['id' => 111], 'from' => ['id' => 222]],
        ])->assertForbidden();

        $this->postJson(route('telegram.webhook', [$bot, $bot->webhook_secret]), [
            'message' => ['text' => '/start', 'chat' => ['id' => 111], 'from' => ['id' => 222]],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bot123456:AA-secret-token-owned-bot/deleteWebhook'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'bot987654:BB-new-secret/sendMessage'));
    }

    public function test_runtime_telegram_bridge_uses_latest_saved_token_after_token_update(): void
    {
        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update(['token_encrypted' => '987654:BB-new-secret']);

        config(['services.node_runtime.secret' => 'bridge-secret']);
        Http::fake([
            'api.telegram.org/bot987654:BB-new-secret/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 55,
                    'chat' => ['id' => 111],
                    'date' => 1890000000,
                ],
            ]),
        ]);

        $this->withHeader('X-Runtime-Secret', 'bridge-secret')
            ->postJson('/runtime/telegram', [
                'bot_id' => $bot->id,
                'action' => 'telegram.sendMessage',
                'options' => [
                    'chat_id' => 111,
                    'text' => 'Latest token',
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => ['inline_keyboard' => [[['text' => 'Open', 'callback_data' => '/open']]]],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('queued', false)
            ->assertJsonPath('result.message_id', 55)
            ->assertJsonPath('result.chat.id', 111)
            ->assertJsonPath('result.date', 1890000000);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bot987654:BB-new-secret/sendMessage')
            && $request['parse_mode'] === 'HTML'
            && $request['disable_web_page_preview'] === true
            && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === '/open');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'bot123456:AA-secret-token-owned-bot/sendMessage'));
    }

    public function test_invalid_settings_token_keeps_old_token(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response([
                'ok' => false,
                'description' => 'Unauthorized',
            ], 401),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $oldEncryptedToken = $bot->getRawOriginal('token_encrypted');

        $this->actingAs($user)->patch(route('bots.settings.update', $bot), [
            'token' => 'bad-token',
        ])->assertSessionHasErrors([
            'token' => 'Invalid Telegram bot token. Please check the token from BotFather.',
        ]);

        $bot->refresh();
        $this->assertSame($oldEncryptedToken, $bot->getRawOriginal('token_encrypted'));
        $this->assertSame('123456:AA-secret-token-owned-bot', $bot->token_encrypted);
    }

    public function test_user_can_set_and_remove_telegram_webhook_with_public_url(): void
    {
        config(['app.public_url' => 'https://abc123.ngrok-free.app']);

        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/deleteWebhook' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create(['role' => 'admin']);
        $bot = $this->createBot($user);
        $bot->update(['token_verified_at' => now()]);

        $this->actingAs($user)->post(route('bots.webhook.set', $bot))
            ->assertRedirect();

        $bot->refresh();
        $this->assertSame('active', $bot->webhook_status);
        $this->assertNotNull($bot->webhook_secret);
        $this->assertSame('https://abc123.ngrok-free.app/telegram/webhook/'.$bot->id.'/'.$bot->webhook_secret, $bot->webhook_url);
        $this->assertNotNull($bot->webhook_set_at);

        $this->actingAs($user)->post(route('bots.webhook.delete', $bot))
            ->assertRedirect();

        $bot->refresh();
        $this->assertSame('not_set', $bot->webhook_status);
        $this->assertNull($bot->webhook_last_error);
    }

    public function test_local_public_url_cannot_set_webhook(): void
    {
        config(['app.public_url' => 'http://127.0.0.1:8000']);

        Http::fake();

        $user = User::factory()->create(['role' => 'admin']);
        $bot = $this->createBot($user);
        $bot->update(['token_verified_at' => now()]);

        $this->actingAs($user)->post(route('bots.webhook.set', $bot))
            ->assertSessionHasErrors([
                'webhook' => 'Telegram requires a public HTTPS webhook URL. Set the public callback URL to your Cloudflare Tunnel, LocalTunnel, or ngrok HTTPS URL.',
            ]);

        $bot->refresh();
        $this->assertSame('failed', $bot->webhook_status);
    }

    public function test_non_admin_cannot_manually_manage_webhook(): void
    {
        config(['app.public_url' => 'https://abc123.ngrok-free.app']);

        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update(['token_verified_at' => now()]);

        $this->actingAs($user)->post(route('bots.webhook.set', $bot))
            ->assertForbidden();

        $this->actingAs($user)->post(route('bots.webhook.delete', $bot))
            ->assertForbidden();
    }

    public function test_telegram_webhook_matches_exact_command_and_sends_response_text(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', 'Hello, welcome!');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 111],
                'from' => ['id' => 222, 'username' => 'tester', 'first_name' => 'Test'],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Hello, welcome!');

        $this->assertNotNull($bot->fresh()->last_webhook_update_at);
    }

    public function test_telegram_webhook_requires_secret_and_ignores_unknown_command(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', 'Hello, welcome!');

        $this->postJson(route('telegram.webhook', [$bot, 'bad-secret']), [
            'message' => ['text' => '/start', 'chat' => ['id' => 111]],
        ])->assertForbidden();

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'message' => ['text' => '/unknown', 'chat' => ['id' => 111]],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertNothingSent();
    }

    public function test_telegram_webhook_tracks_bot_user_and_counts_messages_and_commands(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/execute-command' => Http::response([
                'ok' => true,
                'execution_id' => 'execution-1',
                'execution_time_ms' => 12,
                'replies' => [
                    ['type' => 'text', 'text' => 'Tracked'],
                ],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', null, 'await reply("Tracked");');

        foreach (['/start', '/unknown', '/start'] as $messageText) {
            $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
                'message' => [
                    'text' => $messageText,
                    'chat' => ['id' => 111, 'type' => 'private'],
                    'from' => [
                        'id' => 222,
                        'username' => 'tracked_user',
                        'first_name' => 'Test',
                        'last_name' => 'Person',
                        'language_code' => 'en',
                        'is_bot' => false,
                    ],
                ],
            ])->assertOk()->assertJson(['ok' => true]);
        }

        $this->assertDatabaseCount('bot_users', 1);
        $this->assertDatabaseHas('bot_users', [
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'telegram_username' => 'tracked_user',
            'telegram_first_name' => 'Test',
            'telegram_last_name' => 'Person',
            'telegram_language_code' => 'en',
            'status' => 'active',
            'message_count' => 3,
            'command_count' => 2,
        ]);

        $botUserId = $bot->botUsers()->first()->id;
        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'bot_user_id' => $botUserId,
            'telegram_user_id' => '222',
            'message_text' => '/unknown',
            'status' => 'no_match',
        ]);
    }

    public function test_telegram_webhook_executes_command_code_through_node_runtime(): void
    {
        config([
            'services.node_runtime.url' => 'http://127.0.0.1:8787',
            'services.node_runtime.secret' => 'local-dev-secret',
        ]);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response(['ok' => true]),
            'http://127.0.0.1:8787/execute' => Http::response([
                'ok' => true,
                'replies' => [
                    ['type' => 'text', 'text' => 'Hello from JavaScript runtime'],
                ],
            ]),
            'http://127.0.0.1:8787/execute-command' => Http::response([
                'ok' => true,
                'replies' => [
                    ['type' => 'text', 'text' => 'Hello from JavaScript runtime'],
                ],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', null, 'await reply("Hello from JavaScript runtime");');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 111],
                'from' => ['id' => 222, 'username' => 'tester', 'first_name' => 'Test'],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '127.0.0.1:8787/execute')
            && $request->hasHeader('X-Runtime-Secret', 'local-dev-secret')
            && data_get($request->data(), 'telegram.message_text') === '/start'
            && data_get($request->data(), 'telegram.first_name') === 'Test');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Hello from JavaScript runtime');

        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'bot_command_id' => $bot->commands()->first()->id,
            'telegram_user_id' => '222',
            'telegram_username' => 'tester',
            'telegram_first_name' => 'Test',
            'chat_id' => '111',
            'message_text' => '/start',
            'status' => 'success',
            'reply_count' => 1,
        ]);
    }

    public function test_telegram_webhook_uses_response_text_when_runtime_returns_no_replies(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response(['ok' => true]),
            'http://127.0.0.1:8787/execute' => Http::response([
                'ok' => true,
                'replies' => [],
            ]),
            'http://127.0.0.1:8787/execute-command' => Http::response([
                'ok' => true,
                'replies' => [],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', 'Fallback response', 'await replyLaterMaybe();');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 111],
                'from' => ['id' => 222],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Fallback response');
    }

    public function test_telegram_webhook_hides_runtime_errors_from_chat_and_logs_details(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response(['ok' => true]),
            'http://127.0.0.1:8787/execute' => Http::response([
                'ok' => false,
                'execution_id' => 'exec-secret-test',
                'error_type' => 'ReferenceError',
                'error' => 'Test error token=123456:AA-secret-token-owned-bot',
                'error_stack' => "ReferenceError: Test error\n    at bot.js:1 token=123456:AA-secret-token-owned-bot",
            ]),
            'http://127.0.0.1:8787/execute-command' => Http::response([
                'ok' => false,
                'execution_id' => 'exec-secret-test',
                'error_type' => 'ReferenceError',
                'error' => 'Test error token=123456:AA-secret-token-owned-bot',
                'error_stack' => "ReferenceError: Test error\n    at bot.js:1 token=123456:AA-secret-token-owned-bot",
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $user = User::factory()->create();
        $bot = $this->createBot($user);
        $bot->update([
            'status' => 'running',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ]);
        $this->createCommand($bot, '/start', null, 'throw new Error("Test error");');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 111],
                'from' => ['id' => 222],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Command error. Please contact the bot owner.');

        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'bot_command_id' => $bot->commands()->first()->id,
            'command_name' => '/start',
            'message_text' => '/start',
            'status' => 'failed',
            'execution_id' => 'exec-secret-test',
            'public_error_message' => 'Command error. Please contact the bot owner.',
            'internal_error_type' => 'ReferenceError',
            'internal_error_message' => 'Test error token=[redacted]',
            'error_type' => 'ReferenceError',
            'error_message' => 'Test error token=[redacted]',
        ]);

        $this->assertDatabaseMissing('bot_command_logs', [
            'internal_error_message' => 'Test error token=123456:AA-secret-token-owned-bot',
        ]);
    }

    private function createBot(User $user, string $name = 'Owned Bot', string $slug = 'owned-bot'): Bot
    {
        return Bot::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
            'token_encrypted' => '123456:AA-secret-token-'.$slug,
            'status' => 'stopped',
            'language' => 'javascript',
            'setup_type' => 'custom',
        ]);
    }

    private function createCommand(Bot $bot, string $name, ?string $responseText = null, ?string $code = null): BotCommand
    {
        return BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => $name,
            'code' => $code,
            'response_text' => $responseText,
            'response_type' => 'code',
            'status' => 'active',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotUserRuntimeData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DirectMessageHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_text_runs_direct_message_handler_only_after_other_triggers_miss(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '/start', 'Start won', 'slash');
        $this->createCommand($bot, 'Balance', 'Balance won', 'text');
        $this->createCommand($bot, 'Wallet', 'Wallet won', 'slash');
        $this->createCommand($bot, 'verify_join', 'Callback won', 'callback');
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('7'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/start'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('Balance'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('Wallet'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), [
            'callback_query' => [
                'id' => 'callback-1',
                'data' => 'verify_join',
                'from' => ['id' => 222, 'username' => 'tester'],
                'message' => ['message_id' => 44, 'chat' => ['id' => 111, 'type' => 'private']],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Start won');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Balance won');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Wallet won');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Callback won');
    }

    public function test_slash_command_with_bot_username_matches_base_command_and_preserves_args(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot(['telegram_username' => 'MyBot']);
        $this->createCommand($bot, '/start', 'Start won', 'slash');
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/start@MyBot 123'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Start won');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');

        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'message_text' => '/start@MyBot 123',
            'status' => 'fallback_response',
        ]);
    }

    public function test_slash_callback_routes_to_slash_command_and_uses_callback_user(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $command = $this->createCommand($bot, '/cbtest', 'Callback routing works.', 'slash');
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->callbackUpdate('/cbtest arg1 arg2', 333, 111, 999))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/answerCallbackQuery')
            && $request['callback_query_id'] === 'callback-1');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Callback routing works.');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');

        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'bot_command_id' => $command->id,
            'telegram_user_id' => '333',
            'chat_id' => '111',
            'message_text' => '/cbtest arg1 arg2',
            'status' => 'fallback_response',
        ]);
    }

    public function test_slash_callback_with_bot_username_matches_base_command(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot(['telegram_username' => 'MyBot']);
        $this->createCommand($bot, '/cbtest', 'Callback routing works.', 'slash');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->callbackUpdate('/cbtest@MyBot confirm'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Callback routing works.');
    }

    public function test_unmatched_slash_callback_does_not_fall_through_to_direct_message_handler(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->callbackUpdate('/missing'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');

        $this->assertDatabaseHas('bot_command_logs', [
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'message_text' => '/missing',
            'status' => 'no_match',
        ]);
    }

    public function test_direct_message_handler_form_requires_display_name_but_not_trigger_text(): void
    {
        $bot = $this->createRunningBot();
        $user = $bot->user;

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => 'Captcha Handler',
            'trigger_type' => 'direct_message',
            'response_text' => 'Checking captcha',
            'status' => 'active',
        ])->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']));

        $command = $bot->commands()->firstOrFail();

        $this->assertSame('Captcha Handler', $command->display_name);
        $this->assertSame('direct_message', $command->trigger_type);
        $this->assertStringStartsWith(BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX, $command->command_name);

        $this->actingAs($user)->post(route('bots.commands.store', $bot), [
            'command_name' => 'Wallet Input Handler',
            'trigger_type' => 'direct_message',
            'response_text' => 'Checking wallet',
            'status' => 'active',
        ])->assertSessionHasErrors([
            'trigger_type' => 'This bot already has an active Direct Message Handler.',
        ]);
    }

    public function test_direct_message_handler_form_still_requires_display_name(): void
    {
        $bot = $this->createRunningBot();

        $this->actingAs($bot->user)->post(route('bots.commands.store', $bot), [
            'trigger_type' => 'direct_message',
            'response_text' => 'Checking captcha',
            'status' => 'active',
        ])->assertSessionHasErrors('command_name');
    }

    public function test_command_form_only_shows_command_and_direct_message_trigger_types(): void
    {
        $bot = $this->createRunningBot();

        $response = $this->actingAs($bot->user)->get(route('bots.commands.create', $bot));

        $response->assertOk();
        $response->assertSee('Command');
        $response->assertSee('Direct Message Handler');
        $response->assertDontSee('Command Trigger');
        $response->assertDontSee('Slash Command');
        $response->assertDontSee('Text/Menu Button');
        $response->assertDontSee('Callback Data');
    }

    public function test_direct_message_handler_can_run_another_command_without_runtime_error(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 222]],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '/menu', 'Main menu opened', 'slash');

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'captcha',
            'display_name' => 'Captcha Handler',
            'trigger_type' => 'direct_message',
            'code' => "await runCommand('/menu');",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('10'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Main menu opened');

        $this->assertDatabaseMissing('bot_command_logs', [
            'bot_id' => $bot->id,
            'status' => 'failed',
            'error_message' => 'runCommand is not defined',
        ]);
    }

    public function test_runtime_exposes_chat_id_alias_for_existing_user_code(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'alias',
            'display_name' => 'Alias Handler',
            'trigger_type' => 'direct_message',
            'code' => "await sendMessage(chatId, 'Alias works');",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('hello'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Alias works');
    }

    public function test_runtime_exposes_channel_membership_helper_in_fallback_runner(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => '/verify',
            'display_name' => 'Verify',
            'trigger_type' => 'slash',
            'code' => "await reply(typeof checkChannelMember);",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/verify'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('bot_command_logs', [
            'bot_id' => $bot->id,
            'status' => 'failed',
            'error_message' => 'checkChannelMember is not defined',
        ]);
    }

    public function test_node_runtime_payload_includes_telegram_bridge_configuration(): void
    {
        config([
            'app.public_url' => 'https://public.example.test',
            'services.node_runtime.url' => 'http://127.0.0.1:8787',
            'services.node_runtime.secret' => 'runtime-test-secret',
            'services.node_runtime.internal_url' => 'http://bot.test',
        ]);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response(['ok' => true]),
            'http://127.0.0.1:8787/execute' => Http::response([
                'ok' => true,
                'replies' => [
                    ['type' => 'text', 'text' => 'bridge payload ok'],
                ],
            ]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => '/bridge',
            'display_name' => 'Bridge',
            'trigger_type' => 'slash',
            'code' => "await reply('bridge payload ok');",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/bridge'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '127.0.0.1:8787/execute')
            && data_get($request->data(), 'runtime.telegram_bridge_url') === 'http://bot.test/runtime/telegram'
            && data_get($request->data(), 'runtime.telegram_bridge_secret') === 'runtime-test-secret'
            && data_get($request->data(), 'runtime.storage_bridge_url') === 'http://bot.test/runtime/storage'
            && data_get($request->data(), 'runtime.storage_bridge_secret') === 'runtime-test-secret');
    }

    public function test_runtime_request_global_is_available_before_first_reply(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => '/test2',
            'display_name' => 'Test 2',
            'trigger_type' => 'slash',
            'code' => <<<'JS'
const CURRENT_USER_ID = user?.id || update?.message?.from?.id || request?.message?.from?.id || null;
await reply("request user id: " + CURRENT_USER_ID);
JS,
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/test2'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'request user id: 222');

        $this->assertDatabaseMissing('bot_command_logs', [
            'bot_id' => $bot->id,
            'status' => 'failed',
        ]);
    }

    public function test_command_flow_routes_next_text_to_same_command_before_direct_message_handler(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => '/setwallet',
            'display_name' => 'Set Wallet',
            'trigger_type' => 'slash',
            'code' => <<<'JS'
if (commandFlow.active && commandFlow.step === 'email') {
  await clearCommandFlow();
  await reply('Saved ' + message.text);
  return;
}

await askInCommand('Send email', 'email');
JS,
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/setwallet'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('bot_user_runtime_data', [
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'key' => 'awaiting_command_name',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('user@example.com'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Send email');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Saved user@example.com');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');

        $this->assertDatabaseMissing('bot_user_runtime_data', [
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'key' => 'awaiting_command_name',
        ]);
    }

    public function test_command_flow_is_scoped_by_user_and_bot(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $botA = $this->createRunningBot(['slug' => 'bot-a-'.str()->random(8)]);
        $botB = $this->createRunningBot([
            'slug' => 'bot-b-'.str()->random(8),
            'token_encrypted' => '123456:BB-secret-token-direct',
        ]);
        $commandA = $this->createCommand($botA, '/collect', 'Flow won', 'slash');
        $this->createCommand($botA, '__direct_message_handler_test', 'Direct A', 'direct_message');
        $this->createCommand($botB, '/collect', 'Bot B command', 'slash');
        $this->createCommand($botB, '__direct_message_handler_test', 'Direct B', 'direct_message');

        $this->seedCommandFlow($botA, '222', $commandA, 'email');

        $this->postJson(route('telegram.webhook', [$botA, 'secret-value']), $this->messageUpdate('reply from A'))
            ->assertOk();

        $this->postJson(route('telegram.webhook', [$botA, 'secret-value']), $this->messageUpdate('reply from B user', 333, 112))
            ->assertOk();

        $this->postJson(route('telegram.webhook', [$botB, 'secret-value']), $this->messageUpdate('reply in B'))
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Flow won');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct A');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct B');
    }

    public function test_cancel_clears_command_flow_and_existing_flow_flags(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $command = $this->createCommand($bot, '/setwallet', 'Set wallet', 'slash');
        $this->createCommand($bot, '/cancel', 'Cancelled', 'slash');
        $this->seedCommandFlow($bot, '222', $command, 'email');

        foreach (['workflow_state', 'workflow_state_data', 'admin_state', 'awaiting_wallet', 'awaiting_withdraw_amount'] as $key) {
            BotUserRuntimeData::create([
                'bot_id' => $bot->id,
                'telegram_user_id' => '222',
                'key' => $key,
                'value' => 'x',
            ]);
        }

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('/cancel'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        foreach (['awaiting_command_name', 'workflow_state', 'admin_state', 'awaiting_wallet', 'awaiting_withdraw_amount'] as $key) {
            $this->assertDatabaseMissing('bot_user_runtime_data', [
                'bot_id' => $bot->id,
                'telegram_user_id' => '222',
                'key' => $key,
            ]);
        }

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Cancelled');
    }

    public function test_expired_command_flow_clears_and_falls_back_to_direct_message_handler(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $command = $this->createCommand($bot, '/setwallet', 'Set wallet', 'slash');
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');
        $this->seedCommandFlow($bot, '222', $command, 'email', now()->subMinutes(31)->toISOString());

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->messageUpdate('late reply'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');

        $this->assertDatabaseMissing('bot_user_runtime_data', [
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'key' => 'awaiting_command_name',
        ]);
    }

    public function test_photo_message_runs_direct_message_handler(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '__direct_message_handler_test', 'Photo direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->photoUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Photo direct won');
    }

    public function test_document_message_runs_direct_message_handler(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '__direct_message_handler_test', 'Document direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->documentUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Document direct won');
    }

    public function test_direct_message_handler_marker_routes_media_even_with_legacy_trigger_type(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'legacy',
            'display_name' => 'Legacy Direct Handler',
            'trigger_type' => 'text',
            'response_text' => 'Legacy direct won',
            'response_type' => 'text',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->photoUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Legacy direct won');
    }

    public function test_photo_without_command_but_active_workflow_state_routes_to_direct_message_handler(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '/test_file_live', 'File command won', 'slash');
        $this->createCommand($bot, '__direct_message_handler_test', 'Active state direct won', 'direct_message');

        BotUserRuntimeData::create([
            'bot_id' => $bot->id,
            'telegram_user_id' => '222',
            'key' => 'workflow_state',
            'value' => 'awaiting_file',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->photoUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'Active state direct won');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'File command won');
    }

    public function test_direct_message_handler_can_read_largest_photo_file_id(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'photo-file',
            'display_name' => 'Photo File Handler',
            'trigger_type' => 'direct_message',
            'code' => "await reply('photo id: ' + getLargestPhotoFileId());",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->photoUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'photo id: photo_large_file_id');
    }

    public function test_direct_message_handler_can_read_document_file_id(): void
    {
        config(['services.node_runtime.url' => 'http://127.0.0.1:8787']);

        Http::fake([
            'http://127.0.0.1:8787/health' => Http::response([], 500),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();

        BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'document-file',
            'display_name' => 'Document File Handler',
            'trigger_type' => 'direct_message',
            'code' => "await reply('document id: ' + getDocumentFileId());",
            'response_type' => 'code',
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->documentUpdate())
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === 111
            && $request['text'] === 'document id: document_file_id');
    }

    public function test_photo_caption_slash_command_routes_to_command(): void
    {
        Http::fake([
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $bot = $this->createRunningBot();
        $this->createCommand($bot, '/test_file_live', 'File command won', 'slash');
        $this->createCommand($bot, '__direct_message_handler_test', 'Direct won', 'direct_message');

        $this->postJson(route('telegram.webhook', [$bot, 'secret-value']), $this->photoUpdate('/test_file_live'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'File command won');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['text'] === 'Direct won');
    }

    private function createRunningBot(array $overrides = []): Bot
    {
        return Bot::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'name' => 'Direct Handler Bot',
            'slug' => 'direct-handler-bot-'.str()->random(8),
            'token_encrypted' => '123456:AA-secret-token-direct',
            'status' => 'running',
            'language' => 'javascript',
            'setup_type' => 'custom',
            'webhook_secret' => 'secret-value',
            'webhook_status' => 'active',
        ], $overrides));
    }

    private function createCommand(Bot $bot, string $name, string $responseText, string $triggerType): BotCommand
    {
        return BotCommand::create([
            'bot_id' => $bot->id,
            'command_name' => $name,
            'trigger_type' => $triggerType,
            'response_text' => $responseText,
            'response_type' => 'text',
            'status' => 'active',
        ]);
    }

    private function messageUpdate(string $text, int $userId = 222, int $chatId = 111): array
    {
        return [
            'message' => [
                'text' => $text,
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'from' => ['id' => $userId, 'username' => 'tester', 'first_name' => 'Test'],
            ],
        ];
    }

    private function photoUpdate(?string $caption = null, int $userId = 222, int $chatId = 111): array
    {
        $message = [
            'message_id' => 55,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $userId, 'username' => 'tester', 'first_name' => 'Test'],
            'photo' => [
                ['file_id' => 'photo_small_file_id', 'file_unique_id' => 'photo-small', 'width' => 90, 'height' => 90, 'file_size' => 1234],
                ['file_id' => 'photo_large_file_id', 'file_unique_id' => 'photo-large', 'width' => 1280, 'height' => 960, 'file_size' => 98765],
            ],
        ];

        if ($caption !== null) {
            $message['caption'] = $caption;
        }

        return ['message' => $message];
    }

    private function documentUpdate(?string $caption = null, int $userId = 222, int $chatId = 111): array
    {
        $message = [
            'message_id' => 56,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'from' => ['id' => $userId, 'username' => 'tester', 'first_name' => 'Test'],
            'document' => [
                'file_id' => 'document_file_id',
                'file_unique_id' => 'document-unique',
                'file_name' => 'proof.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 43210,
            ],
        ];

        if ($caption !== null) {
            $message['caption'] = $caption;
        }

        return ['message' => $message];
    }

    private function callbackUpdate(string $data, int $userId = 222, int $chatId = 111, int $messageFromId = 999): array
    {
        return [
            'callback_query' => [
                'id' => 'callback-1',
                'data' => $data,
                'from' => ['id' => $userId, 'username' => 'tester', 'first_name' => 'Test'],
                'message' => [
                    'message_id' => 44,
                    'chat' => ['id' => $chatId, 'type' => 'private'],
                    'from' => ['id' => $messageFromId, 'is_bot' => true, 'username' => 'BotHostBot'],
                ],
            ],
        ];
    }

    private function seedCommandFlow(Bot $bot, string $telegramUserId, BotCommand $command, string $step, ?string $startedAt = null): void
    {
        foreach ([
            'awaiting_command_id' => $command->id,
            'awaiting_command_name' => $command->command_name,
            'awaiting_command_step' => $step,
            'awaiting_command_data' => [],
            'awaiting_command_started_at' => $startedAt ?? now()->toISOString(),
        ] as $key => $value) {
            BotUserRuntimeData::create([
                'bot_id' => $bot->id,
                'telegram_user_id' => $telegramUserId,
                'key' => $key,
                'value' => $value,
            ]);
        }
    }
}

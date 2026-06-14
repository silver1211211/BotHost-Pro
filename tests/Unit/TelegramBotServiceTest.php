<?php

namespace Tests\Unit;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotServiceTest extends TestCase
{
    public function test_check_telegram_channel_member_returns_true_for_members(): void
    {
        Http::fake([
            'api.telegram.org/*/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member', 'user' => ['id' => 12345]],
            ]),
        ]);

        $result = app(TelegramBotService::class)->checkTelegramChannelMember('123456:ABCdefGhijKLMnop', '@example', 12345);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['is_member']);
        $this->assertSame('member', $result['status']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/getChatMember')
            && $request['chat_id'] === '@example'
            && $request['user_id'] === 12345);
    }

    public function test_check_telegram_channel_member_returns_true_for_administrators_and_creators(): void
    {
        Http::fakeSequence('api.telegram.org/*/getChatMember')
            ->push(['ok' => true, 'result' => ['status' => 'administrator', 'user' => ['id' => 12345]]])
            ->push(['ok' => true, 'result' => ['status' => 'creator', 'user' => ['id' => 12345]]]);

        foreach (['administrator', 'creator'] as $status) {
            $result = app(TelegramBotService::class)->checkTelegramChannelMember('123456:ABCdefGhijKLMnop', '@example', 12345);

            $this->assertTrue($result['ok']);
            $this->assertTrue($result['is_member']);
            $this->assertSame($status, $result['status']);
        }
    }

    public function test_check_telegram_channel_member_returns_false_for_left_users(): void
    {
        Http::fakeSequence('api.telegram.org/*/getChatMember')
            ->push(['ok' => true, 'result' => ['status' => 'left', 'user' => ['id' => 12345]]])
            ->push(['ok' => true, 'result' => ['status' => 'kicked', 'user' => ['id' => 12345]]]);

        foreach (['left', 'kicked'] as $status) {
            $result = app(TelegramBotService::class)->checkTelegramChannelMember('123456:ABCdefGhijKLMnop', '@example', 12345);

            $this->assertTrue($result['ok']);
            $this->assertFalse($result['is_member']);
            $this->assertSame($status, $result['status']);
        }
    }

    public function test_check_telegram_channel_member_returns_false_for_user_not_found(): void
    {
        Http::fake([
            'api.telegram.org/*/getChatMember' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: user not found',
            ], 400),
        ]);

        $result = app(TelegramBotService::class)->checkTelegramChannelMember('123456:ABCdefGhijKLMnop', '@example', 12345);

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['is_member']);
        $this->assertSame('unknown', $result['status']);
        $this->assertSame('Telegram user was not found in this chat.', $result['message']);
    }

    public function test_check_telegram_channel_member_returns_safe_error_for_telegram_failures(): void
    {
        Http::fake([
            'api.telegram.org/*/getChatMember' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: chat not found',
            ], 400),
        ]);

        $result = app(TelegramBotService::class)->checkTelegramChannelMember('123456:ABCdefGhijKLMnop', '@missing', 12345);

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['is_member']);
        $this->assertSame('unknown', $result['status']);
        $this->assertSame('Telegram channel or group was not found. Check the @username or numeric chat ID.', $result['message']);
        $this->assertStringNotContainsString('123456:ABCdefGhijKLMnop', json_encode($result));
    }
}

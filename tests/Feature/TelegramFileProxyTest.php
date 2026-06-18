<?php

use App\Models\Bot;
use App\Models\TelegramFileReference;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function telegramFileBot(?User $owner = null): Bot
{
    $owner ??= User::factory()->create(['status' => 'active']);

    return Bot::query()->create([
        'user_id' => $owner->id,
        'name' => 'Telegram File Bot',
        'slug' => 'telegram-file-bot-'.uniqid(),
        'token_encrypted' => '123456:FILEHELPERSECRET',
        'status' => 'running',
        'runtime_mode' => 'local',
    ]);
}

test('runtime telegram getFile returns safe proxy url and stores file reference', function () {
    config(['services.node_runtime.secret' => 'runtime-file-secret']);
    $bot = telegramFileBot();

    Http::fake([
        'https://api.telegram.org/bot123456:FILEHELPERSECRET/getFile' => Http::response([
            'ok' => true,
            'result' => [
                'file_id' => 'photo_file_id',
                'file_unique_id' => 'unique-photo',
                'file_path' => 'photos/file_1.jpg',
                'file_size' => 12345,
            ],
        ]),
    ]);

    $response = $this->withHeader('X-Runtime-Secret', 'runtime-file-secret')
        ->postJson(route('runtime.telegram'), [
            'bot_id' => $bot->id,
            'action' => 'telegram.getFile',
            'options' => ['file_id' => 'photo_file_id'],
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('result.file_path', 'photos/file_1.jpg')
        ->assertJsonPath('result.file_url', fn ($url) => is_string($url) && str_contains($url, "/dashboard/bots/{$bot->id}/files/"));

    expect($response->json('result.file_url'))->not->toContain('FILEHELPERSECRET')
        ->and(TelegramFileReference::query()->where('bot_id', $bot->id)->where('file_id', 'photo_file_id')->exists())->toBeTrue();
});

test('runtime telegram getFile rejects invalid file id safely', function () {
    config(['services.node_runtime.secret' => 'runtime-file-secret']);
    $bot = telegramFileBot();

    $this->withHeader('X-Runtime-Secret', 'runtime-file-secret')
        ->postJson(route('runtime.telegram'), [
            'bot_id' => $bot->id,
            'action' => 'telegram.getFile',
            'options' => ['file_id' => '../bad'],
        ])
        ->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('error', 'Telegram file_id is invalid.');
});

test('bot file proxy streams for owner and admin but rejects other users', function () {
    $owner = User::factory()->create(['status' => 'active']);
    $other = User::factory()->create(['status' => 'active']);
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $bot = telegramFileBot($owner);

    $reference = TelegramFileReference::query()->create([
        'bot_id' => $bot->id,
        'file_hash' => 'safe-file-hash',
        'file_id' => 'photo_file_id',
        'file_unique_id' => 'unique-photo',
        'file_path' => 'photos/file_1.jpg',
        'file_size' => 8,
    ]);

    Http::fake([
        'https://api.telegram.org/file/bot123456:FILEHELPERSECRET/photos/file_1.jpg' => Http::response('JPEGDATA', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $this->actingAs($other)
        ->get(route('bots.files.show', [$bot, $reference->file_hash]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('bots.files.show', [$bot, $reference->file_hash]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertSee('JPEGDATA');

    $this->actingAs($admin)
        ->get(route('bots.files.show', [$bot, $reference->file_hash]))
        ->assertOk()
        ->assertSee('JPEGDATA');
});

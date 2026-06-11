<?php

use App\Models\Bot;
use App\Models\BotTransfer;
use App\Models\User;

function transferPolicyUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'status' => 'active',
    ], $attributes));
}

function transferPolicyBot(User $user, array $attributes = []): Bot
{
    return Bot::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Policy Bot',
        'slug' => 'policy-bot-'.str()->random(8),
        'token_encrypted' => '123456:AA-policy-token-'.str()->random(8),
        'status' => 'stopped',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ], $attributes));
}

it('blocks export and transfer when a bot contains marketplace commands', function (): void {
    $sender = transferPolicyUser();
    $receiver = transferPolicyUser(['email' => 'receiver@example.com']);
    $bot = transferPolicyBot($sender);

    $bot->commands()->create([
        'command_name' => '/start',
        'response_text' => 'Protected command',
        'response_type' => 'text',
        'status' => 'active',
        'source' => 'marketplace',
        'license_locked' => true,
    ]);

    $this->actingAs($sender)
        ->get(route('bots.export', $bot))
        ->assertRedirect()
        ->assertSessionHasErrors('export');

    $this->actingAs($sender)
        ->post(route('bots.transfer', $bot), [
            'receiver_email' => $receiver->email,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('transfer');

    expect(BotTransfer::count())->toBe(0);
});

it('allows self coded bots to be exported and transferred', function (): void {
    $sender = transferPolicyUser();
    $receiver = transferPolicyUser(['email' => 'receiver@example.com']);
    $bot = transferPolicyBot($sender);

    $bot->commands()->create([
        'command_name' => '/start',
        'response_text' => 'Self coded command',
        'response_type' => 'text',
        'status' => 'active',
        'source' => null,
        'license_locked' => false,
    ]);

    $this->actingAs($sender)
        ->get(route('bots.export', $bot))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json');

    $this->actingAs($sender)
        ->post(route('bots.transfer', $bot), [
            'receiver_email' => $receiver->email,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $transfer = BotTransfer::first();

    expect($transfer)->not->toBeNull()
        ->and($transfer->receiver_email)->toBe($receiver->email)
        ->and(data_get($transfer->decodedPayload(), 'commands.0.command_name'))->toBe('/start');
});

it('imports received transfers without requiring a bot token', function (): void {
    $sender = transferPolicyUser(['email' => 'sender@example.com']);
    $receiver = transferPolicyUser(['email' => 'receiver@example.com']);
    $sourceBot = transferPolicyBot($sender);

    $transfer = BotTransfer::create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'receiver_email' => $receiver->email,
        'source_bot_id' => $sourceBot->id,
        'bot_name' => 'Transferred Bot',
        'payload' => json_encode([
            'metadata' => ['format' => 'bothost_pro_transfer', 'version' => '1'],
            'version' => '1',
            'bot_name' => 'Transferred Bot',
            'language' => 'javascript',
            'settings' => [],
            'commands' => [[
                'command_name' => '/hello',
                'response_text' => 'Hello',
                'response_type' => 'text',
                'status' => 'active',
            ]],
        ]),
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($receiver)
        ->post(route('transfers.import', $transfer), [
            'import_name' => 'Imported Without Token',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $bot = $receiver->bots()->where('name', 'Imported Without Token')->first();

    expect($bot)->not->toBeNull()
        ->and($bot->token_encrypted)->toBeNull()
        ->and($bot->token_hash)->toBeNull()
        ->and($bot->token_verified_at)->toBeNull()
        ->and($bot->status)->toBe('stopped')
        ->and($bot->commands()->where('command_name', '/hello')->exists())->toBeTrue()
        ->and($transfer->fresh()->status)->toBe('imported');
});

<?php

use App\Models\Bot;
use App\Models\BotTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function botExportImportUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'status' => 'active',
    ], $attributes));
}

function botExportImportBot(User $user): Bot
{
    return Bot::create([
        'user_id' => $user->id,
        'name' => 'Import Target',
        'slug' => 'import-target-'.str()->random(8),
        'token_encrypted' => '123456:AA-import-token-'.str()->random(8),
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ]);
}

function botExportImportPayload(array $commands = null): string
{
    $commands ??= [
        [
            'command_name' => '/start',
            'display_name' => '/start',
            'trigger_type' => 'slash',
            'code' => "await reply('hello');",
            'response_text' => 'Hello',
            'response_type' => 'code',
            'status' => 'active',
        ],
    ];

    return json_encode([
        'metadata' => [
            'format' => 'bothost_pro_bot_export',
            'version' => '1',
            'bot_name' => 'Source Bot',
        ],
        'version' => '1',
        'bot_name' => 'Source Bot',
        'language' => 'javascript',
        'settings' => [
            'timezone' => 'Africa/Lagos',
            'auto_restart' => true,
        ],
        'commands' => $commands,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function botExportImportUpload(string $name, string $content, string $mime = 'application/json'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'bot_export_import_');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, $mime, null, true);
}

it('imports a valid BotHost export JSON into the users own bot without template ownership', function (): void {
    $user = botExportImportUser();
    $bot = botExportImportBot($user);
    $payload = botExportImportPayload([
        [
            'command_name' => '/start',
            'trigger_type' => 'slash',
            'response_text' => 'Start exact',
            'status' => 'active',
        ],
        [
            'command_name' => 'Admin  Menu',
            'trigger_type' => 'text',
            'response_text' => 'Spacing exact',
            'status' => 'inactive',
        ],
        [
            'command_name' => '💰  Balance',
            'trigger_type' => 'text',
            'response_text' => 'Emoji exact',
            'status' => 'active',
        ],
    ]);

    $this->actingAs($user)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.json', $payload),
        ])
        ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'manage']))
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->where('trigger_type', 'slash')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', 'Admin  Menu')->where('status', 'inactive')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', '💰  Balance')->exists())->toBeTrue()
        ->and($bot->setting()->where('timezone', 'Africa/Lagos')->exists())->toBeTrue();
});

it('keeps locked marketplace restrictions separate from Bot Tools JSON import', function (): void {
    $user = botExportImportUser();
    $bot = botExportImportBot($user);
    $template = BotTemplate::create([
        'name' => 'Locked Paid Template',
        'slug' => 'locked-paid-template-'.str()->random(8),
        'status' => 'published',
        'marketplace_status' => 'listed',
        'access_type' => 'paid',
        'price' => '10.00',
        'currency' => 'USD',
        'level' => 'beginner',
        'published_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertRedirect()
        ->assertSessionHasErrors('template');

    $this->actingAs($user)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.json', botExportImportPayload()),
        ])
        ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'manage']))
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->exists())->toBeTrue();
});

it('returns a validation error for invalid JSON and unsupported upload types', function (): void {
    $user = botExportImportUser();
    $bot = botExportImportBot($user);

    $this->actingAs($user)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.json', '{not json', 'application/json'),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['import_file' => 'Please upload a valid BotHost Pro JSON export file.']);

    $this->actingAs($user)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.php', botExportImportPayload(), 'application/x-php'),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['import_file' => 'Please upload a valid BotHost Pro JSON export file.']);

    expect($bot->commands()->count())->toBe(0);
});

it('accepts json uploads sent as application json text plain or octet stream', function (): void {
    $user = botExportImportUser();

    foreach (['application/json', 'text/plain', 'application/octet-stream'] as $mime) {
        $bot = botExportImportBot($user);

        $this->actingAs($user)
            ->post(route('bots.import.current', $bot), [
                'import_file' => botExportImportUpload('backup.json', botExportImportPayload(), $mime),
            ])
            ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'manage']))
            ->assertSessionHasNoErrors();

        expect($bot->commands()->where('command_name', '/start')->exists())->toBeTrue();
    }
});

it('does not allow users to import export JSON into another users bot', function (): void {
    $owner = botExportImportUser();
    $other = botExportImportUser(['email' => 'other-import@example.com']);
    $bot = botExportImportBot($owner);

    $this->actingAs($other)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.json', botExportImportPayload()),
        ])
        ->assertForbidden();
});

it('imports direct message handlers as handlers including legacy markers', function (): void {
    $user = botExportImportUser();
    $bot = botExportImportBot($user);

    $this->actingAs($user)
        ->post(route('bots.import.current', $bot), [
            'import_file' => botExportImportUpload('backup.json', botExportImportPayload([
                [
                    'command_name' => '_*direct_message_handler**',
                    'code' => 'await reply("legacy dm");',
                    'response_type' => 'code',
                    'status' => 'active',
                ],
            ])),
        ])
        ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'manage']))
        ->assertSessionHasNoErrors();

    $handler = $bot->commands()->where('trigger_type', 'direct_message')->first();

    expect($handler)->not->toBeNull()
        ->and($handler->displayName())->toBe('Direct Message Handler')
        ->and($handler->command_name)->not->toBe('_*direct_message_handler**')
        ->and($bot->commands()->where('command_name', '_*direct_message_handler**')->exists())->toBeFalse();
});

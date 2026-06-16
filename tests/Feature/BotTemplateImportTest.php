<?php

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function templateZipUpload(array $commands = null): UploadedFile
{
    $commands ??= [
        [
            'command_name' => '/start',
            'file' => 'commands/start.js',
            'response_text' => 'Welcome!',
            'aliases' => ['/begin'],
            'folder' => 'General',
            'status' => 'active',
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'template_zip_');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('template.json', json_encode([
        'name' => 'Admin Welcome',
        'description' => 'A useful welcome template',
        'runtime' => 'node',
        'language' => 'javascript',
        'commands' => $commands,
    ]));
    $zip->addFromString('commands/start.js', "await reply('Welcome!');");
    $zip->close();

    return new UploadedFile($path, 'template.zip', 'application/zip', null, true);
}

function templateJsonUpload(array $commands = null, string $name = 'referral-bot-export-2026-06-04'): UploadedFile
{
    $commands ??= [
        [
            'command_name' => '/start',
            'display_name' => '/start',
            'trigger_type' => 'slash',
            'code' => "await reply('Welcome from JSON!');",
            'response_text' => 'Welcome from JSON!',
            'aliases' => ['/begin'],
            'folder' => 'General',
            'status' => 'active',
        ],
    ];

    $path = tempnam(sys_get_temp_dir(), 'template_json_');

    file_put_contents($path, json_encode([
        'metadata' => [
            'format' => 'bothost_pro_bot_export',
            'version' => '1',
        ],
        'version' => '1',
        'bot_name' => 'Referral Faucet Bot',
        'language' => 'javascript',
        'commands' => $commands,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return new UploadedFile($path, $name, 'application/json', null, true);
}

function invalidTemplateUpload(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'template_invalid_');

    file_put_contents($path, 'not a template export');

    return new UploadedFile($path, 'notes.txt', 'text/plain', null, true);
}

function templateImportUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'status' => 'active',
    ], $attributes));
}

function templateImportBot(User $user): Bot
{
    return Bot::create([
        'user_id' => $user->id,
        'name' => 'Template Bot',
        'slug' => 'template-bot-'.str()->random(8),
        'token_encrypted' => '123456:AA-secret-token-'.str()->random(8),
        'status' => 'running',
        'language' => 'javascript',
        'setup_type' => 'custom',
    ]);
}

function templateImportPublishedTemplate(): BotTemplate
{
    $template = BotTemplate::create([
        'name' => 'Starter Template',
        'slug' => 'starter-template-'.str()->random(8),
        'description' => 'Starter commands',
        'category' => 'starter',
        'level' => 'beginner',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $template->commands()->createMany([
        [
            'command_name' => '/start',
            'response_text' => 'Welcome from template',
            'status' => 'active',
            'runtime' => 'node',
            'language' => 'javascript',
            'sort_order' => 0,
        ],
        [
            'command_name' => '/help',
            'response_text' => 'Help from template',
            'status' => 'active',
            'runtime' => 'node',
            'language' => 'javascript',
            'sort_order' => 1,
        ],
    ]);

    $template->forceFill(['commands_count' => 2])->save();

    return $template;
}

it('allows admins to create publish and add template commands', function (): void {
    Storage::fake('public');
    $admin = templateImportUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Admin Welcome',
            'template_zip' => templateZipUpload(),
            'description' => 'A useful welcome template',
            'short_description' => 'Useful welcome',
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'is_featured' => '1',
            'tags' => 'welcome, starter',
        ])
        ->assertRedirect();

    $template = BotTemplate::query()->where('slug', 'admin-welcome')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.templates.update', $template), [
            'name' => 'Admin Welcome',
            'description' => 'A useful welcome template for new bot users.',
            'short_description' => 'Useful welcome',
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'published',
            'marketplace_status' => 'listed',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'image' => UploadedFile::fake()->image('template.jpg')->size(200),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->post(route('admin.templates.publish', $template))
        ->assertRedirect();

    expect($template->fresh()->status)->toBe('published')
        ->and($template->fresh()->commands_count)->toBe(1)
        ->and($template->commands()->first()->command_name)->toBe('/start');
});

it('allows admins to create templates from BotHost JSON export files', function (): void {
    $admin = templateImportUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Referral Faucet Bot',
            'template_zip' => templateJsonUpload(),
            'description' => 'A useful referral faucet bot template.',
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'marketplace_status' => 'unlisted',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $template = BotTemplate::query()->where('slug', 'referral-faucet-bot')->firstOrFail();

    expect($template->template_zip_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($template->template_zip_path))->toBeTrue()
        ->and($template->commands_count)->toBe(1)
        ->and($template->commands()->first()->command_name)->toBe('/start')
        ->and($template->commands()->first()->metadata['source'] ?? null)->toBe('json')
        ->and($template->metadata['zip_parse']['source'] ?? null)->toBe('json');
});

it('counts every imported template command record including non slash handlers', function (): void {
    $admin = templateImportUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Mixed Trigger Bot',
            'template_zip' => templateJsonUpload([
                [
                    'command_name' => '/start',
                    'code' => "await reply('Start');",
                    'status' => 'active',
                ],
                [
                    'command_name' => '$balance',
                    'response_text' => 'Your balance is ready.',
                    'status' => 'active',
                ],
                [
                    'trigger_type' => 'direct_message',
                    'response_text' => 'Direct message handler.',
                    'status' => 'active',
                ],
                [
                    'command_name' => 'menu:settings',
                    'response_text' => 'Settings menu.',
                    'status' => 'active',
                ],
            ]),
            'short_description' => 'Mixed triggers',
            'description' => 'A useful mixed-trigger template.',
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'marketplace_status' => 'unlisted',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $template = BotTemplate::query()->where('slug', 'mixed-trigger-bot')->firstOrFail();

    expect($template->commands_count)->toBe(4)
        ->and($template->commands()->pluck('command_name')->all())->toContain('/start', '$balance', 'direct_message', 'menu:settings')
        ->and($template->metadata['zip_parse']['imported'] ?? null)->toBe(4);
});

it('rejects uploaded template files that are not valid ZIP or JSON exports', function (): void {
    $admin = templateImportUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Invalid Template',
            'template_zip' => invalidTemplateUpload(),
            'description' => 'This file is not a valid template.',
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'marketplace_status' => 'unlisted',
        ])
        ->assertSessionHasErrors('template_zip');

    expect(BotTemplate::query()->where('slug', 'invalid-template')->exists())->toBeFalse();
});

it('validates template text limits by visible characters without counting bold markers', function (): void {
    $admin = templateImportUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => '**'.str_repeat('n', 100).'**',
            'template_zip' => templateJsonUpload(),
            'short_description' => '**Referral Bot**',
            'description' => str_repeat('a', 1993)."\n\n**Bold Text**\n".str_repeat('b', 1996),
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'marketplace_status' => 'unlisted',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(BotTemplate::query()->where('name', '**'.str_repeat('n', 100).'**')->firstOrFail()->short_description)
        ->toBe('**Referral Bot**');

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => '**'.str_repeat('x', 101).'**',
            'template_zip' => templateJsonUpload(),
            'short_description' => '**'.str_repeat('a', 201).'**',
            'description' => str_repeat('b', 4001),
            'category' => 'referral_bot',
            'level' => 'beginner',
            'status' => 'draft',
            'access_type' => 'free',
            'price' => 0,
            'currency' => 'USD',
            'marketplace_status' => 'unlisted',
        ])
        ->assertSessionHasErrors([
            'name' => 'Template name may not be greater than 100 visible characters.',
            'short_description' => 'About may not be greater than 200 visible characters.',
            'description' => 'Description may not be greater than 4000 visible characters.',
        ]);
});

it('imports published templates into owned bots with skip and rename conflict strategies', function (): void {
    $user = templateImportUser();
    $bot = templateImportBot($user);
    $template = templateImportPublishedTemplate();

    BotCommand::create([
        'bot_id' => $bot->id,
        'command_name' => '/start',
        'response_text' => 'Existing start',
        'response_type' => 'text',
        'status' => 'active',
    ]);
    $template->purchases()->create([
        'user_id' => $user->id,
        'amount' => 0,
        'currency' => 'USD',
        'status' => 'completed',
        'purchased_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]), [
            'conflict_strategy' => 'skip',
        ])
        ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']))
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start')->count())->toBe(1)
        ->and($bot->commands()->where('command_name', '/help')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', '/help')->first()->source)->toBe('marketplace')
        ->and($bot->commands()->where('command_name', '/help')->first()->license_locked)->toBeTrue()
        ->and($bot->templateImports()->latest('id')->first()->imported_commands_count)->toBe(1)
        ->and($bot->templateImports()->latest('id')->first()->skipped_commands_count)->toBe(1);

    $this->actingAs($user)
        ->post(route('bots.templates.import', [$bot, $template]), [
            'conflict_strategy' => 'rename',
        ])
        ->assertRedirect(route('bots.show', ['bot' => $bot, 'tab' => 'commands']))
        ->assertSessionHasNoErrors();

    expect($bot->commands()->where('command_name', '/start_2')->exists())->toBeTrue()
        ->and($bot->commands()->where('command_name', '/help_2')->exists())->toBeTrue()
        ->and($template->fresh()->import_count)->toBe(2);
});

it('blocks imports for unowned bots and unpublished templates', function (): void {
    $owner = templateImportUser();
    $other = templateImportUser(['email' => 'other@example.com']);
    $bot = templateImportBot($owner);
    $template = templateImportPublishedTemplate();

    $this->actingAs($other)
        ->post(route('bots.templates.import', [$bot, $template]))
        ->assertForbidden();

    $draft = BotTemplate::create([
        'name' => 'Draft Template',
        'slug' => 'draft-template-'.str()->random(8),
        'status' => 'draft',
        'level' => 'beginner',
    ]);

    $this->actingAs($owner)
        ->post(route('bots.templates.import', [$bot, $draft]))
        ->assertNotFound();
});

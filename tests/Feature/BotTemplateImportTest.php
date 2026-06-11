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
            'short_description' => 'Useful welcome commands',
            'category' => 'starter',
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
            'short_description' => 'Useful welcome commands',
            'category' => 'starter',
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

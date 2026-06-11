<?php

use App\Models\Project;
use App\Models\ProjectVariable;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

test('users can create a project workspace from a starter template', function () {
    $user = User::factory()->create();
    $template = Template::create([
        'name' => 'Basic Telegram Bot',
        'slug' => 'basic-telegram-bot-test',
        'category' => 'starter',
        'description' => 'Test template',
        'is_active' => true,
        'files' => [
            'bot.js' => 'console.log("ready");',
            'config.js' => 'module.exports = {};',
            'package.json' => '{"private": true}',
            '.env' => 'BOT_TOKEN=',
        ],
    ]);

    $response = $this->actingAs($user)->post('/projects', [
        'name' => 'Workspace Test',
        'description' => 'Starter project',
        'language' => 'javascript',
        'template_id' => $template->id,
    ]);

    $project = Project::where('name', 'Workspace Test')->firstOrFail();

    $response->assertRedirect(route('projects.show', $project));
    expect($project->files()->count())->toBe(4);
    expect($project->setting()->exists())->toBeTrue();
    Storage::disk('project_workspaces')->assertExists('projects/'.$project->id.'/bot.js');
    expect(storage_path('projects/'.$project->id.'/bot.js'))->toBeFile();

    $this->actingAs($user)->get(route('projects.show', $project))->assertOk();
});

test('secret project variables are encrypted and hidden', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create([
        'name' => 'Secret Test',
        'slug' => 'secret-test',
        'status' => 'stopped',
        'language' => 'javascript',
    ]);

    $this->actingAs($user)->post(route('projects.variables.store', $project), [
        'key' => 'BOT_TOKEN',
        'value' => '123456:secret',
        'is_secret' => '1',
    ])->assertRedirect(route('projects.show', $project));

    $variable = ProjectVariable::firstOrFail();
    $rawValue = DB::table('project_variables')->where('id', $variable->id)->value('value');

    expect($variable->value)->toBe('123456:secret');
    expect($rawValue)->not->toBe('123456:secret');
    expect($variable->displayValue())->toBe('********');
});

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotTemplate;
use App\Models\BotTemplateCommand;
use App\Models\BotCommand;
use App\Services\AuditLogService;
use App\Services\BotTemplateImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateCommandController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function store(Request $request, BotTemplate $template): RedirectResponse
    {
        $data = $this->validatedCommand($request);

        $command = $template->commands()->create([
            ...$data,
            'command_name' => BotTemplateImporter::validateCommandName($data['command_name']),
            'aliases' => $this->aliasesFrom($data['aliases'] ?? null),
            'runtime' => $data['runtime'] ?: 'node',
            'language' => $data['language'] ?: 'javascript',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $template->forceFill(['commands_count' => $template->commands()->count()])->save();

        $this->audit->log('template', 'template_command.created', 'Template command added.', [
            'template_id' => $template->id,
            'command_id' => $command->id,
            'command_name' => $command->command_name,
        ], $request->user(), 'success', $command);

        return back()->with('status', 'Template command added.');
    }

    public function update(Request $request, BotTemplate $template, BotTemplateCommand $command): RedirectResponse
    {
        abort_unless($command->bot_template_id === $template->id, 403);
        $data = $this->validatedCommand($request);

        $command->update([
            ...$data,
            'command_name' => BotTemplateImporter::validateCommandName($data['command_name']),
            'aliases' => $this->aliasesFrom($data['aliases'] ?? null),
            'runtime' => $data['runtime'] ?: 'node',
            'language' => $data['language'] ?: 'javascript',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $template->forceFill(['commands_count' => $template->commands()->count()])->save();

        $this->audit->log('template', 'template_command.updated', 'Template command updated.', [
            'template_id' => $template->id,
            'command_id' => $command->id,
            'command_name' => $command->command_name,
        ], $request->user(), 'success', $command);

        return back()->with('status', 'Template command updated.');
    }

    public function destroy(Request $request, BotTemplate $template, BotTemplateCommand $command): RedirectResponse
    {
        abort_unless($command->bot_template_id === $template->id, 403);
        $commandId = $command->id;
        $commandName = $command->command_name;
        $command->delete();
        $template->forceFill(['commands_count' => $template->commands()->count()])->save();

        $this->audit->log('template', 'template_command.deleted', 'Template command deleted.', [
            'template_id' => $template->id,
            'command_id' => $commandId,
            'command_name' => $commandName,
        ], $request->user(), 'warning', BotTemplateCommand::class, $commandId);

        return back()->with('status', 'Template command deleted.');
    }

    private function validatedCommand(Request $request): array
    {
        return $request->validate([
            'command_name' => ['required', 'string', 'max:64', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! BotTemplateImporter::validateCommandName((string) $value)) {
                    $fail('Command name cannot be empty and must be 64 characters or fewer.');
                }
            }],
            'trigger_type' => ['nullable', Rule::in(BotCommand::TRIGGER_TYPES)],
            'description' => ['nullable', 'string', 'max:500'],
            'code' => ['nullable', 'string'],
            'response_text' => ['nullable', 'string'],
            'aliases' => ['nullable', 'string'],
            'folder' => ['nullable', 'string', 'max:100'],
            'runtime' => ['nullable', 'string', 'max:50'],
            'language' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(BotTemplateCommand::STATUSES)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function aliasesFrom(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : null;
        }

        $aliases = collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $alias) => BotTemplateImporter::validateCommandName($alias))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $aliases === [] ? null : $aliases;
    }
}

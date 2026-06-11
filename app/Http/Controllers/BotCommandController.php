<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Services\AuditLogService;
use App\Services\BotAccessService;
use App\Services\CommandRuntimeCacheService;
use App\Services\PlanAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BotCommandController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
        private readonly CommandRuntimeCacheService $commandCache,
    ) {}

    public function index(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands']);
    }

    public function create(Request $request, Bot $bot): RedirectResponse|View
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        return view('bots.commands.create', compact('bot'));
    }

    public function store(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        if (! $this->planAccess->canCreateCommand($request->user(), $bot)) {
            return redirect()
                ->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'You have reached your plan limit for commands on this bot. Upgrade to add more.']);
        }

        $data = $this->validatedCommand($request, $bot, requireCode: false);
        $commandName = trim($data['command_name']);

        $command = $bot->commands()->create([
            'command_name' => $commandName,
            'display_name' => trim($data['display_name']),
            'trigger_type' => $data['trigger_type'],
            'code' => filled($data['code'] ?? null) ? $data['code'] : null,
            'response_text' => $data['response_text'] ?? null,
            'response_type' => 'code',
            'status' => $data['status'] ?? 'active',
            'is_pinned' => $request->boolean('is_pinned'),
            'admin_only' => $request->boolean('admin_only'),
            'aliases' => $this->aliasesFrom($data['aliases'] ?? null),
            'folder' => $data['folder'] ?? null,
        ]);

        $this->log($request, 'command_created', 'Created command '.$command->displayName().' for '.$bot->name);
        $this->commandCache->clearBot($bot);

        return redirect()
            ->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
            ->with('status', 'Command created successfully.');
    }

    public function edit(Request $request, Bot $bot, BotCommand $command): RedirectResponse|View
    {
        $this->access->authorizeCommand($request, $bot, $command);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        return view('bots.commands.edit', compact('bot', 'command'));
    }

    public function update(Request $request, Bot $bot, BotCommand $command): RedirectResponse
    {
        $this->access->authorizeCommand($request, $bot, $command);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        $data = $this->validatedCommand($request, $bot, $command, requireCode: false);

        $command->update([
            'command_name' => trim($data['command_name']),
            'display_name' => trim($data['display_name']),
            'trigger_type' => $data['trigger_type'],
            'response_text' => $data['response_text'] ?? null,
            'status' => $data['status'] ?? 'active',
            'is_pinned' => $request->boolean('is_pinned'),
            'admin_only' => $request->boolean('admin_only'),
            'aliases' => $this->aliasesFrom($data['aliases'] ?? null),
            'folder' => $data['folder'] ?? null,
            'last_error_at' => null,
        ]);

        $this->log($request, 'command_updated', 'Updated command '.$command->displayName().' for '.$bot->name);
        $this->commandCache->clearBot($bot);

        return redirect()
            ->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
            ->with('status', 'Command updated.');
    }

    public function code(Request $request, Bot $bot, BotCommand $command): RedirectResponse|View|JsonResponse
    {
        $this->access->authorizeCommand($request, $bot, $command);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'code' => $command->code ?? '',
                'command' => [
                    'id' => $command->id,
                    'name' => $command->displayName(),
                    'code_size' => strlen((string) ($command->code ?? '')),
                    'updated_at' => optional($command->updated_at)->toISOString(),
                ],
            ]);
        }

        return view('bots.commands.code', compact('bot', 'command'));
    }

    public function updateCode(Request $request, Bot $bot, BotCommand $command): RedirectResponse|JsonResponse
    {
        $this->access->authorizeCommand($request, $bot, $command);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        $data = $request->validate([
            'code' => ['nullable', 'string'],
        ]);

        $command->update([
            'code' => filled($data['code'] ?? null) ? $data['code'] : null,
            'response_type' => 'code',
            'last_error_at' => null,
        ]);

        $this->log($request, 'command_code_updated', 'Updated command code '.$command->displayName().' for '.$bot->name);
        $this->commandCache->clearBot($bot);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Command code saved.',
                'command' => [
                    'id' => $command->id,
                    'code_size' => strlen((string) ($command->code ?? '')),
                    'updated_at' => optional($command->updated_at)->toISOString(),
                ],
            ]);
        }

        return redirect()
            ->route('bots.commands.code', [$bot, $command])
            ->with('status', 'Command code saved.');
    }

    public function destroy(Request $request, Bot $bot, BotCommand $command): RedirectResponse
    {
        $this->access->authorizeCommand($request, $bot, $command);

        if (! $this->planAccess->userHasFeature($request->user(), 'command_editor')) {
            return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
                ->withErrors(['command' => 'This feature is not available on your current plan.']);
        }

        $name = $command->displayName();
        $command->delete();
        $this->log($request, 'command_deleted', 'Deleted command '.$name.' from '.$bot->name);
        $this->commandCache->clearBot($bot);

        return redirect()
            ->route('bots.show', ['bot' => $bot, 'tab' => 'commands'])
            ->with('status', 'Command deleted.');
    }

    private function validatedCommand(Request $request, Bot $bot, ?BotCommand $command = null, bool $requireCode = true): array
    {
        $requestedTriggerType = (string) $request->input('trigger_type', $command?->effectiveTriggerType() ?? 'slash');
        $uiTriggerType = $requestedTriggerType === 'direct_message' ? 'direct_message' : 'slash';
        $triggerType = $uiTriggerType;

        if ($uiTriggerType === 'slash' && in_array($command?->effectiveTriggerType(), ['text', 'callback'], true)) {
            $triggerType = $command->effectiveTriggerType();
        }

        $visibleCommandName = trim((string) $request->input('command_name'));
        $displayName = $visibleCommandName;
        $commandName = $visibleCommandName;

        if ($uiTriggerType === 'direct_message') {
            $commandName = $command?->effectiveTriggerType() === 'direct_message'
                ? $command->command_name
                : BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.Str::lower(Str::random(10));
        }

        $request->merge([
            'command_name' => $visibleCommandName,
            'trigger_type' => $uiTriggerType,
        ]);

        $validated = $request->validate([
            'trigger_type' => ['required', Rule::in(['slash', 'direct_message'])],
            'command_name' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) use ($bot, $command, $uiTriggerType): void {
                    $value = (string) $value;

                    if ($uiTriggerType === 'direct_message') {
                        return;
                    }

                    if (trim($value) === '') {
                        $fail('Command name cannot be empty.');

                        return;
                    }

                    if (preg_match('/\s{2,}/u', $value)) {
                        $fail('Command name cannot contain multiple consecutive spaces.');
                    }

                    $duplicate = $bot->commands()
                        ->when($command, fn ($query) => $query->whereKeyNot($command->id))
                        ->get(['command_name'])
                        ->contains(fn (BotCommand $existing) => $existing->command_name === $value);

                    if ($duplicate) {
                        $fail('This command already exists for this bot.');
                    }
                },
            ],
            'code' => [$requireCode ? 'required' : 'nullable', 'string'],
            'response_text' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(BotCommand::STATUSES)],
            'is_pinned' => ['nullable', 'boolean'],
            'admin_only' => ['nullable', 'boolean'],
            'aliases' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $value = trim((string) $value);

                if (str_starts_with($value, '[')) {
                    $decoded = json_decode($value, true);

                    if (! is_array($decoded)) {
                        $fail('Aliases must be a valid JSON array.');

                        return;
                    }
                }

                if (count($this->parseAliases($value)) > 10) {
                    $fail('You may add up to 10 aliases.');
                }
            }],
            'folder' => ['nullable', 'string', 'max:100'],
        ], [], [
            'trigger_type' => 'trigger type',
            'command_name' => 'command name',
        ]);

        $validated['display_name'] = $displayName;
        $validated['command_name'] = $commandName;
        $validated['trigger_type'] = $triggerType;

        return $validated + $this->validateSingleDirectMessageHandler($request, $bot, $command);
    }

    private function validateSingleDirectMessageHandler(Request $request, Bot $bot, ?BotCommand $command): array
    {
        if ($request->input('trigger_type') !== 'direct_message' || $request->input('status') !== 'active') {
            return [];
        }

        $exists = $bot->commands()
            ->where('trigger_type', 'direct_message')
            ->where('status', 'active')
            ->when($command, fn ($query) => $query->whereKeyNot($command->id))
            ->exists();

        if ($exists) {
            validator([], [])->after(function ($validator): void {
                $validator->errors()->add('trigger_type', 'This bot already has an active Direct Message Handler.');
            })->validate();
        }

        return [];
    }

    /**
     * @return array<int, string>|null
     */
    private function aliasesFrom(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $aliases = $this->parseAliases($value);

        return $aliases === [] ? null : $aliases;
    }

    /**
     * @return array<int, string>
     */
    private function parseAliases(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        if (str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn (mixed $alias) => is_string($alias) || is_numeric($alias))
                    ->map(fn (mixed $alias) => trim((string) $alias))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $alias) => trim($alias))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function log(Request $request, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $this->audit->log('bot', str_replace('_', '.', $action), $description, [], $request->user());
    }
}

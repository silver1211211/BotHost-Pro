<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotTransfer;
use App\Models\User;
use App\Services\BotAccessService;
use App\Services\AuditLogService;
use App\Services\PlanAccessService;
use App\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BotExportImportController extends Controller
{
    private const IMPORT_FILE_ERROR = 'Please upload a valid BotHost Pro JSON export file.';

    public function __construct(
        private readonly BotAccessService $access,
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
    ) {}

    public function export(Request $request, Bot $bot): Response|RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $allCommands = $bot->commands()->get();
        $protectedCount = $allCommands->filter(fn ($cmd) => $this->isProtectedCommand($cmd))->count();

        if ($protectedCount > 0) {
            return back()->withErrors(['export' => 'This bot contains marketplace/template commands and cannot be exported. Only fully self-coded bots can be exported.']);
        }

        $commands = $allCommands
            ->map(fn ($cmd) => $this->commandPayload($cmd, false))
            ->values()
            ->all();

        $this->audit->log('bot', 'bot_exported', 'Bot exported.', [
            'bot_id' => $bot->id,
            'commands_exported' => count($commands),
            'protected_excluded' => 0,
        ], $request->user(), 'success', Bot::class, $bot->id);

        $payload = json_encode([
            'metadata'    => [
                'format'      => 'bothost_pro_bot_export',
                'version'     => '1',
                'bot_name'    => $bot->name,
                'exported_at' => now()->toIso8601String(),
                'protected_commands_excluded' => 0,
            ],
            'version'     => '1',
            'bot_name'    => $bot->name,
            'language'    => $bot->language,
            'exported_at' => now()->toIso8601String(),
            'settings'    => $this->safeSettingsPayload($bot),
            'commands'    => $commands,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $filename = Str::slug($bot->name).'-export-'.now()->format('Y-m-d').'.json';

        return response($payload, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function clone(Request $request, Bot $bot, TelegramBotService $telegram): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $user = $request->user();

        if (! $this->planAccess->canCreateBot($user)) {
            return back()->withErrors(['clone_token' => 'You have reached your plan limit for bots. Upgrade your plan to create more.']);
        }

        $data = $request->validate([
            'clone_name'  => ['required', 'string', 'max:100'],
            'clone_token' => ['nullable', 'string', 'max:255'],
        ]);

        $botPayload = [
            'name'               => $data['clone_name'],
            'slug'               => $this->uniqueSlug($user->id, $data['clone_name']),
            'token_encrypted'    => null,
            'token_hash'         => null,
            'status'             => 'stopped',
            'language'           => $bot->language ?? 'javascript',
            'setup_type'         => 'custom',
            'cloned_from_bot_id' => $bot->id,
            'source_type'        => 'clone',
            'token_verified_at'  => null,
        ];

        if (filled($data['clone_token'] ?? null)) {
            if (Bot::tokenInUse($data['clone_token'])) {
                return back()
                    ->withErrors(['clone_token' => 'This bot token is already connected to another workspace.'])
                    ->withInput($request->except('clone_token'));
            }

            $telegramResult = $telegram->validateToken($data['clone_token']);
            if (! $telegramResult['valid']) {
                return back()
                    ->withErrors(['clone_token' => $telegramResult['message']])
                    ->withInput($request->except('clone_token'));
            }

            $telegramData = $telegramResult['data'];

            $botPayload = array_merge($botPayload, [
                'token_encrypted'                      => $data['clone_token'],
                'token_hash'                           => Bot::tokenHash($data['clone_token']),
                'telegram_bot_id'                      => isset($telegramData['id']) ? (string) $telegramData['id'] : null,
                'telegram_username'                    => $telegramData['username'] ?? null,
                'telegram_first_name'                  => $telegramData['first_name'] ?? null,
                'telegram_can_join_groups'             => $telegramData['can_join_groups'] ?? null,
                'telegram_can_read_all_group_messages' => $telegramData['can_read_all_group_messages'] ?? null,
                'telegram_supports_inline_queries'     => $telegramData['supports_inline_queries'] ?? null,
                'token_verified_at'                    => now(),
            ]);
        }

        $newBot = $user->bots()->create($botPayload);

        $newBot->setting()->create($this->safeSettingsPayload($bot));

        foreach ($bot->commands()->get() as $cmd) {
            $newBot->commands()->create($this->commandPayload($cmd, true));
        }

        $this->audit->log('bot', 'bot_cloned', 'Bot cloned.', [
            'source_bot_id' => $bot->id,
            'new_bot_id' => $newBot->id,
            'token_provided' => filled($data['clone_token'] ?? null),
        ], $request->user(), 'success', Bot::class, $newBot->id);

        return redirect()->route('bots.index')->with('status', 'Bot cloned successfully.');
    }

    public function transfer(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $data = $request->validate([
            'receiver_email' => ['required', 'email', 'max:255'],
            'transfer_note'  => ['nullable', 'string', 'max:500'],
        ]);

        $allCommands = $bot->commands()->get();

        if ($allCommands->contains(fn ($cmd) => $this->isProtectedCommand($cmd))) {
            return back()->withErrors(['transfer' => 'This bot contains marketplace/template commands and cannot be transferred. Only fully self-coded bots can be transferred.']);
        }

        $commands = $allCommands->map(fn ($cmd) => $this->commandPayload($cmd, true))->values()->all();

        $payload = json_encode([
            'metadata' => [
                'format' => 'bothost_pro_transfer',
                'version' => '1',
            ],
            'version'  => '1',
            'bot_name' => $bot->name,
            'language' => $bot->language,
            'settings' => $this->safeSettingsPayload($bot),
            'commands' => $commands,
        ]);

        $receiverEmail = strtolower($data['receiver_email']);
        $receiver = User::whereRaw('LOWER(email) = ?', [$receiverEmail])->first();

        BotTransfer::create([
            'sender_id'      => $request->user()->id,
            'receiver_id'    => $receiver?->id,
            'receiver_email' => $receiverEmail,
            'source_bot_id'  => $bot->id,
            'bot_name'       => $bot->name,
            'payload'        => $payload,
            'status'         => 'pending',
            'note'           => $data['transfer_note'] ?? null,
            'expires_at'     => now()->addDays(7),
        ]);

        $this->audit->log('bot', 'bot_transferred', 'Bot transfer initiated.', [
            'source_bot_id' => $bot->id,
            'receiver_email' => $receiverEmail,
        ], $request->user(), 'success', Bot::class, $bot->id);

        return back()->with('status', 'Transfer initiated. The receiver can import the bot from their Transfers page.');
    }

    public function importIntoBot(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $request->validate([
            'import_file' => ['required', 'file', 'max:512'],
        ]);

        $data = $this->readExportFile($request->file('import_file'));

        if (! $data) {
            return back()->withErrors(['import_file' => self::IMPORT_FILE_ERROR]);
        }

        try {
            $summary = DB::transaction(function () use ($bot, $data): array {
                $summary = ['created' => 0, 'updated' => 0];

                foreach ($data['commands'] as $cmdData) {
                    $result = $this->importCommandIntoBot($bot, $cmdData);
                    $summary[$result]++;
                }

                if (isset($data['settings']) && is_array($data['settings'])) {
                    $bot->setting()->updateOrCreate(['bot_id' => $bot->id], $this->safeSettingsFromImport($data['settings']));
                }

                return $summary;
            });
        } catch (Throwable $e) {
            Log::warning('Bot Tools command import failed.', [
                'bot_id' => $bot->id,
                'user_id' => $request->user()?->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['import_file' => 'Import failed. Please check the export file and try again.']);
        }

        $this->audit->log('bot', 'bot_imported_into_workspace', 'Commands imported into bot.', [
            'bot_id' => $bot->id,
            'commands_count' => count($data['commands']),
            'commands_created' => $summary['created'],
            'commands_updated' => $summary['updated'],
        ], $request->user(), 'success', Bot::class, $bot->id);

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'manage'])
            ->with('status', sprintf('Imported successfully: %d commands created, %d commands updated.', $summary['created'], $summary['updated']));
    }

    public function import(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $user = $request->user();

        if (! $this->planAccess->canCreateBot($user)) {
            return back()->withErrors(['import_token' => 'You have reached your plan limit for bots. Upgrade your plan to create more.']);
        }

        $request->validate([
            'import_file'  => ['required', 'file', 'max:512'],
            'import_name'  => ['required', 'string', 'max:100'],
            'import_token' => ['required', 'string', 'max:255'],
        ]);

        $data = $this->readExportFile($request->file('import_file'));

        if (! $data) {
            return back()->withErrors(['import_file' => self::IMPORT_FILE_ERROR]);
        }

        if (Bot::tokenInUse($request->input('import_token'))) {
            return back()->withErrors(['import_token' => 'This bot token is already connected to another workspace.']);
        }

        $telegramResult = $telegram->validateToken($request->input('import_token'));
        if (! $telegramResult['valid']) {
            return back()->withErrors(['import_token' => $telegramResult['message']]);
        }

        $telegramData = $telegramResult['data'];
        $importName = $request->input('import_name');

        $bot = $user->bots()->create([
            'name'                                 => $importName,
            'slug'                                 => $this->uniqueSlug($user->id, $importName),
            'token_encrypted'                      => $request->input('import_token'),
            'token_hash'                           => Bot::tokenHash($request->input('import_token')),
            'status'                               => 'stopped',
            'language'                             => $data['language'] ?? 'javascript',
            'setup_type'                           => 'custom',
            'source_type'                          => 'import',
            'telegram_bot_id'                      => isset($telegramData['id']) ? (string) $telegramData['id'] : null,
            'telegram_username'                    => $telegramData['username'] ?? null,
            'telegram_first_name'                  => $telegramData['first_name'] ?? null,
            'telegram_can_join_groups'             => $telegramData['can_join_groups'] ?? null,
            'telegram_can_read_all_group_messages' => $telegramData['can_read_all_group_messages'] ?? null,
            'telegram_supports_inline_queries'     => $telegramData['supports_inline_queries'] ?? null,
            'token_verified_at'                    => now(),
        ]);

        $bot->setting()->create($this->safeSettingsFromImport($data['settings'] ?? []));

        foreach ($data['commands'] as $cmdData) {
            $bot->commands()->create($this->commandImportPayload($bot, $cmdData));
        }

        $this->audit->log('bot', 'bot_imported', 'Bot imported.', [
            'bot_id' => $bot->id,
            'commands_count' => count($data['commands']),
        ], $request->user(), 'success', Bot::class, $bot->id);

        return redirect()->route('bots.show', $bot)->with('status', 'Bot imported successfully.');
    }

    private function commandPayload($cmd, bool $includeSourceFields): array
    {
        $triggerType = $cmd->effectiveTriggerType();
        $payload = [
            'command_name'  => $cmd->command_name,
            'display_name'  => $cmd->displayName(),
            'trigger_type'  => $triggerType,
            'type'          => $triggerType,
            'code'          => $cmd->code,
            'response_text' => $cmd->response_text,
            'response_type' => $cmd->response_type,
            'status'        => $cmd->status,
            'is_pinned'     => $cmd->is_pinned,
            'admin_only'    => $cmd->admin_only,
            'aliases'       => $cmd->aliases,
            'folder'        => $cmd->folder,
        ];

        if ($includeSourceFields) {
            $payload['source'] = $cmd->source;
            $payload['source_template_id'] = $cmd->source_template_id;
            $payload['source_template_purchase_id'] = $cmd->source_template_purchase_id;
            $payload['license_locked'] = $cmd->license_locked;
            $payload['duplicate_count_used'] = $cmd->duplicate_count_used;
        }

        return $payload;
    }

    private function commandImportPayload(Bot $bot, array $cmdData): array
    {
        $triggerType = $this->commandTriggerType($cmdData);
        $commandName = $triggerType === 'direct_message'
            ? $this->directMessageCommandName($bot)
            : $cmdData['command_name'];
        $triggerType ??= str_starts_with((string) $commandName, '/') ? 'slash' : 'text';

        return [
            'command_name'  => $commandName,
            'display_name'  => $triggerType === 'direct_message' ? 'Direct Message Handler' : ($cmdData['display_name'] ?? $cmdData['command_name']),
            'trigger_type'  => $triggerType,
            'code'          => $cmdData['code'] ?? null,
            'response_text' => $cmdData['response_text'] ?? '',
            'response_type' => $cmdData['response_type'] ?? 'text',
            'status'        => in_array($cmdData['status'] ?? null, BotCommand::STATUSES, true) ? $cmdData['status'] : 'active',
            'is_pinned'     => (bool) ($cmdData['is_pinned'] ?? false),
            'admin_only'    => (bool) ($cmdData['admin_only'] ?? false),
            'aliases'       => is_array($cmdData['aliases'] ?? null) ? $cmdData['aliases'] : null,
            'folder'        => is_string($cmdData['folder'] ?? null) ? $cmdData['folder'] : null,
            'source'        => null,
            'source_template_id' => null,
            'source_template_purchase_id' => null,
            'license_locked' => false,
            'duplicate_count_used' => 0,
        ];
    }

    private function importCommandIntoBot(Bot $bot, array $cmdData): string
    {
        if ($this->commandTriggerType($cmdData) === 'direct_message') {
            return $this->importDirectMessageHandler($bot, $cmdData);
        }

        $commandName = (string) $cmdData['command_name'];
        $payload = $this->commandImportPayload($bot, $cmdData);

        $command = BotCommand::withTrashed()
            ->where('bot_id', $bot->id)
            ->where('command_name', $commandName)
            ->first();

        if ($command) {
            if ($command->trashed()) {
                $command->restore();
            }

            $command->forceFill($payload)->save();

            return 'updated';
        }

        BotCommand::query()->create(['bot_id' => $bot->id] + $payload);

        return 'created';
    }

    private function importDirectMessageHandler(Bot $bot, array $cmdData): string
    {
        $command = BotCommand::withTrashed()
            ->where('bot_id', $bot->id)
            ->where(function ($query): void {
                $query->where('trigger_type', 'direct_message')
                    ->orWhere('command_name', 'like', BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.'%')
                    ->orWhere('command_name', '_*direct_message_handler**')
                    ->orWhere('command_name', 'direct_message');
            })
            ->first();

        $payload = $this->commandImportPayload($bot, $cmdData);

        if ($command) {
            if ($command->trashed()) {
                $command->restore();
            }

            if (str_starts_with((string) $command->command_name, BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX)) {
                $payload['command_name'] = $command->command_name;
            }

            $command->forceFill($payload)->save();

            return 'updated';
        }

        BotCommand::query()->create(['bot_id' => $bot->id] + $payload);

        return 'created';
    }

    private function commandTriggerType(array $cmdData): ?string
    {
        $triggerType = $cmdData['trigger_type'] ?? $cmdData['type'] ?? null;

        if (in_array($triggerType, BotCommand::TRIGGER_TYPES, true)) {
            return $triggerType;
        }

        return BotCommand::isDirectMessageMarker($cmdData['command_name'] ?? null) ? 'direct_message' : null;
    }

    private function directMessageCommandName(Bot $bot): string
    {
        $existing = $bot->commands()
            ->where('trigger_type', 'direct_message')
            ->value('command_name');

        return $existing ?: BotCommand::DIRECT_MESSAGE_COMMAND_PREFIX.Str::lower(Str::random(10));
    }

    private function isProtectedCommand($cmd): bool
    {
        return filled($cmd->source_template_id)
            || filled($cmd->source_template_purchase_id)
            || $cmd->license_locked
            || in_array($cmd->source, ['template', 'marketplace', 'pro_template', 'business_template'], true);
    }

    private function safeSettingsPayload(Bot $bot): array
    {
        $setting = $bot->setting;

        if (! $setting) {
            return [];
        }

        return collect($setting->only(['auto_restart', 'ram_limit', 'cpu_limit', 'timezone']))
            ->filter(fn ($value) => $value !== null)
            ->all();
    }

    private function safeSettingsFromImport(array $settings): array
    {
        return collect($settings)
            ->only(['auto_restart', 'ram_limit', 'cpu_limit', 'timezone'])
            ->filter(fn ($value) => $value !== null)
            ->all();
    }

    private function readExportFile(UploadedFile $file): ?array
    {
        if (! $this->isAllowedJsonUpload($file)) {
            return null;
        }

        $contents = file_get_contents($file->getRealPath());
        if (! is_string($contents) || trim($contents) === '') {
            return null;
        }

        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data) || ! $this->isSupportedExportPayload($data)) {
            return null;
        }

        $commands = $this->validatedImportCommands($data['commands']);
        if ($commands === null) {
            return null;
        }

        $data['commands'] = $commands;

        return $data;
    }

    private function isAllowedJsonUpload(UploadedFile $file): bool
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getClientMimeType());

        if ($extension === 'json') {
            return true;
        }

        return in_array($mime, ['application/json', 'text/json', 'text/plain'], true);
    }

    private function isSupportedExportPayload(array $data): bool
    {
        if (! isset($data['commands']) || ! is_array($data['commands'])) {
            return false;
        }

        $format = data_get($data, 'metadata.format');
        if ($format !== null) {
            return $format === 'bothost_pro_bot_export';
        }

        return isset($data['version'], $data['bot_name']);
    }

    private function validatedImportCommands(array $commands): ?array
    {
        $validCommands = [];

        foreach ($commands as $command) {
            if (! is_array($command)) {
                return null;
            }

            $triggerType = $this->commandTriggerType($command);
            $commandName = $command['command_name'] ?? null;

            if ($triggerType !== 'direct_message' && (! is_string($commandName) || $commandName === '')) {
                return null;
            }

            if (isset($command['aliases']) && ! is_array($command['aliases'])) {
                return null;
            }

            foreach (['command_name', 'display_name', 'trigger_type', 'type', 'code', 'response_text', 'response_type', 'status', 'folder'] as $field) {
                if (isset($command[$field]) && ! is_string($command[$field])) {
                    return null;
                }
            }

            $validCommands[] = $command;
        }

        return $validCommands;
    }

    private function uniqueSlug(int $userId, string $name): string
    {
        $base  = Str::slug($name) ?: 'bot';
        $slug  = $base;
        $count = 2;

        while (Bot::query()->where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }
}

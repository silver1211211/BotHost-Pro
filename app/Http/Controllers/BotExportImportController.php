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
use Illuminate\Support\Str;

class BotExportImportController extends Controller
{
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
            'import_file' => ['required', 'file', 'mimes:json', 'max:512'],
        ]);

        $data = $this->readExportFile($request);

        if (! $data) {
            return back()->withErrors(['import_file' => 'Invalid export file format.']);
        }

        foreach ($data['commands'] as $cmdData) {
            if (empty($cmdData['command_name'])) {
                continue;
            }

            $bot->commands()->updateOrCreate(
                ['command_name' => $cmdData['command_name']],
                $this->commandImportPayload($cmdData)
            );
        }

        $this->audit->log('bot', 'bot_imported_into_workspace', 'Commands imported into bot.', [
            'bot_id' => $bot->id,
            'commands_count' => count($data['commands']),
        ], $request->user(), 'success', Bot::class, $bot->id);

        if (isset($data['settings']) && is_array($data['settings'])) {
            $bot->setting()->updateOrCreate(['bot_id' => $bot->id], $this->safeSettingsFromImport($data['settings']));
        }

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'manage'])->with('status', 'Commands imported successfully.');
    }

    public function import(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        $user = $request->user();

        if (! $this->planAccess->canCreateBot($user)) {
            return back()->withErrors(['import_token' => 'You have reached your plan limit for bots. Upgrade your plan to create more.']);
        }

        $request->validate([
            'import_file'  => ['required', 'file', 'mimes:json', 'max:512'],
            'import_name'  => ['required', 'string', 'max:100'],
            'import_token' => ['required', 'string', 'max:255'],
        ]);

        $data = $this->readExportFile($request);

        if (! $data) {
            return back()->withErrors(['import_file' => 'Invalid export file format.']);
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
            if (empty($cmdData['command_name'])) {
                continue;
            }
            $bot->commands()->create($this->commandImportPayload($cmdData));
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

    private function commandImportPayload(array $cmdData): array
    {
        $triggerType = in_array($cmdData['trigger_type'] ?? null, BotCommand::TRIGGER_TYPES, true)
            ? $cmdData['trigger_type']
            : (BotCommand::isDirectMessageMarker($cmdData['command_name'] ?? null) ? 'direct_message' : null);

        return [
            'command_name'  => $cmdData['command_name'],
            'display_name'  => $triggerType === 'direct_message' ? 'Direct Message Handler' : ($cmdData['display_name'] ?? $cmdData['command_name']),
            'trigger_type'  => $triggerType,
            'code'          => $cmdData['code'] ?? null,
            'response_text' => $cmdData['response_text'] ?? '',
            'response_type' => $cmdData['response_type'] ?? 'text',
            'status'        => $cmdData['status'] ?? 'active',
            'is_pinned'     => $cmdData['is_pinned'] ?? false,
            'admin_only'    => $cmdData['admin_only'] ?? false,
            'aliases'       => $cmdData['aliases'] ?? null,
            'folder'        => $cmdData['folder'] ?? null,
            'source'        => $cmdData['source'] ?? null,
            'source_template_id' => $cmdData['source_template_id'] ?? null,
            'source_template_purchase_id' => $cmdData['source_template_purchase_id'] ?? null,
            'license_locked' => $cmdData['license_locked'] ?? false,
            'duplicate_count_used' => $cmdData['duplicate_count_used'] ?? 0,
        ];
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

    private function readExportFile(Request $request): ?array
    {
        $contents = file_get_contents($request->file('import_file')->getRealPath());
        $data = json_decode($contents, true);

        return is_array($data) && isset($data['commands']) && is_array($data['commands'])
            ? $data
            : null;
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

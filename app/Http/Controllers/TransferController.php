<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotTransfer;
use App\Services\AuditLogService;
use App\Services\PlanAccessService;
use App\Services\TelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $sent = BotTransfer::where('sender_id', $user->id)
            ->with(['sourceBot', 'receiver'])
            ->latest()
            ->get();

        $received = BotTransfer::where(function ($q) use ($user) {
            $q->whereRaw('LOWER(receiver_email) = ?', [strtolower($user->email)])
              ->orWhere('receiver_id', $user->id);
        })
            ->with(['sourceBot', 'sender'])
            ->latest()
            ->get();

        $all = $sent->merge($received)->unique('id')->sortByDesc('created_at')->values();
        $pending   = $all->where('status', 'pending')->values();
        $completed = $all->where('status', 'imported')->values();
        $cancelled = $all->where('status', 'cancelled')->values();

        return view('transfers.index', [
            'all'       => $all,
            'sent'      => $sent,
            'received'  => $received,
            'pending'   => $pending,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'counts'    => [
                'all'       => $all->count(),
                'sent'      => $sent->count(),
                'received'  => $received->count(),
                'pending'   => $pending->count(),
                'completed' => $completed->count(),
                'cancelled' => $cancelled->count(),
            ],
        ]);
    }

    public function importTransfer(Request $request, BotTransfer $transfer, TelegramBotService $telegram): RedirectResponse
    {
        $user = $request->user();

        if (strtolower($transfer->receiver_email) !== strtolower($user->email) && $transfer->receiver_id !== $user->id) {
            abort(403);
        }

        if (! $transfer->isPending()) {
            return back()->withErrors(['error' => 'This transfer is no longer pending.']);
        }

        if ($transfer->expires_at && $transfer->expires_at->isPast()) {
            $transfer->update(['status' => 'expired']);
            return back()->withErrors(['error' => 'This transfer has expired.']);
        }

        if (! $this->planAccess->canCreateBot($user)) {
            return back()->withErrors(['import_token' => 'You have reached your plan limit for bots.']);
        }

        $data = $request->validate([
            'import_name'  => ['required', 'string', 'max:100'],
            'import_token' => ['nullable', 'string', 'max:255'],
        ]);

        $telegramData = [];
        $token = $data['import_token'] ?? null;

        if (filled($token)) {
            if (Bot::tokenInUse($token)) {
                return back()->withErrors(['import_token' => 'This bot token is already connected to another workspace.']);
            }

            $telegramResult = $telegram->validateToken($token);
            if (! $telegramResult['valid']) {
                return back()->withErrors(['import_token' => $telegramResult['message']]);
            }

            $telegramData = $telegramResult['data'];
        }

        $payload      = $transfer->decodedPayload();

        $bot = $user->bots()->create([
            'name'                                 => $data['import_name'],
            'slug'                                 => $this->uniqueSlug($user->id, $data['import_name']),
            'token_encrypted'                      => filled($token) ? $token : null,
            'token_hash'                           => filled($token) ? Bot::tokenHash($token) : null,
            'status'                               => 'stopped',
            'language'                             => $payload['language'] ?? 'javascript',
            'setup_type'                           => 'custom',
            'cloned_from_bot_id'                   => $transfer->source_bot_id,
            'source_type'                          => 'transfer_import',
            'telegram_bot_id'                      => isset($telegramData['id']) ? (string) $telegramData['id'] : null,
            'telegram_username'                    => $telegramData['username'] ?? null,
            'telegram_first_name'                  => $telegramData['first_name'] ?? null,
            'telegram_can_join_groups'             => $telegramData['can_join_groups'] ?? null,
            'telegram_can_read_all_group_messages' => $telegramData['can_read_all_group_messages'] ?? null,
            'telegram_supports_inline_queries'     => $telegramData['supports_inline_queries'] ?? null,
            'token_verified_at'                    => filled($token) ? now() : null,
        ]);

        $bot->setting()->create($this->safeSettingsFromPayload($payload['settings'] ?? []));

        foreach ($payload['commands'] ?? [] as $cmdData) {
            if (empty($cmdData['command_name'])) {
                continue;
            }
            $bot->commands()->create([
                'command_name'  => $cmdData['command_name'],
                'display_name'  => $cmdData['display_name'] ?? $cmdData['command_name'],
                'trigger_type'  => in_array($cmdData['trigger_type'] ?? null, BotCommand::TRIGGER_TYPES, true) ? $cmdData['trigger_type'] : null,
                'code'          => $cmdData['code'] ?? null,
                'response_text' => $cmdData['response_text'] ?? null,
                'response_type' => $cmdData['response_type'] ?? null,
                'status'        => $cmdData['status'] ?? 'active',
                'is_pinned'     => $cmdData['is_pinned'] ?? false,
                'admin_only'    => $cmdData['admin_only'] ?? false,
                'aliases'       => $cmdData['aliases'] ?? null,
                'folder'        => $cmdData['folder'] ?? null,
            ]);
        }

        $transfer->update([
            'status'      => 'imported',
            'receiver_id' => $user->id,
            'imported_at' => now(),
        ]);

        $this->audit->log('bot', 'transfer_imported', 'Bot transfer imported.', [
            'transfer_id' => $transfer->id,
            'bot_id' => $bot->id,
        ], $user, 'success', Bot::class, $bot->id);

        return redirect()->route('bots.show', $bot)->with('status', 'Bot transfer imported successfully.');
    }

    public function cancelTransfer(Request $request, BotTransfer $transfer): RedirectResponse
    {
        if ($transfer->sender_id !== $request->user()->id) {
            abort(403);
        }

        if (! $transfer->isPending()) {
            return back()->withErrors(['error' => 'Only pending transfers can be cancelled.']);
        }

        $transfer->update(['status' => 'cancelled']);

        $this->audit->log('bot', 'transfer_cancelled', 'Bot transfer cancelled.', [
            'transfer_id' => $transfer->id,
            'source_bot_id' => $transfer->source_bot_id,
        ], $request->user(), 'success', BotTransfer::class, $transfer->id);

        return back()->with('status', 'Transfer cancelled.');
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

    private function safeSettingsFromPayload(array $settings): array
    {
        return collect($settings)
            ->only(['auto_restart', 'ram_limit', 'cpu_limit', 'timezone'])
            ->filter(fn ($value) => $value !== null)
            ->all();
    }
}

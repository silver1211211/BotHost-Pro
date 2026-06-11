<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotLog;
use App\Models\BotUser;
use App\Services\AuditLogService;
use App\Services\DockerRuntimeService;
use App\Services\RuntimeSettingsService;
use App\Services\TelegramWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\PublicCallbackUrl;

class BotController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $query = Bot::query()
            ->with('user')
            ->withCount(['commands', 'botUsers'])
            ->withCount(['logs as error_count' => fn ($q) => $q->whereIn('type', ['error', 'runtime'])]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('telegram_username', 'like', "%{$search}%")
                  ->orWhere('telegram_bot_id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($setup = $request->input('setup_type')) {
            $query->where('setup_type', $setup);
        }

        if ($verified = $request->input('verified')) {
            if ($verified === 'yes') {
                $query->whereNotNull('token_verified_at');
            } elseif ($verified === 'no') {
                $query->whereNull('token_verified_at');
            }
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest(),
            'most_commands' => $query->orderByDesc('commands_count'),
            'most_users'    => $query->orderByDesc('bot_users_count'),
            'most_errors'   => $query->orderByDesc('error_count'),
            default         => $query->latest(),
        };

        $activeThreshold = now()->subHours(24);

        return view('admin.bots.index', [
            'bots'    => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'setup_type', 'verified', 'sort']),
            'activeThreshold' => $activeThreshold,
        ]);
    }

    public function setAllWebhooks(Request $request, TelegramWebhookService $webhooks): RedirectResponse
    {
        if (! PublicCallbackUrl::isPublicHttps()) {
            return back()->withErrors(['webhook' => 'The public callback URL must be a public HTTPS URL to set webhooks.']);
        }

        $summary = $webhooks->resetAllWebhooks();
        $set = $summary['success'];
        $failed = $summary['failed'];
        $started = $summary['started'];

        $this->audit->log('webhook', 'telegram_webhooks.reset', "Set webhooks for all bots: {$set} succeeded, {$failed} failed, {$started} started.", [
            'success_count' => $set,
            'failed_count' => $failed,
            'started_count' => $started,
        ], $request->user(), $failed > 0 ? 'partial' : 'success');

        return back()->with('status', "Webhooks set: {$set} succeeded, {$failed} failed, {$started} bot(s) started.");
    }

    public function updateStatus(Request $request, Bot $bot, RuntimeSettingsService $settings, DockerRuntimeService $dockerRuntime): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['running', 'paused', 'stopped', 'suspended'])],
        ]);

        $bot->update($data);

        if (($settings->string('runtime_mode', 'local') === 'docker' && $settings->boolean('runtime_docker_enabled', false)) || $bot->runtime_mode === 'docker') {
            if ($data['status'] === 'running') {
                $dockerRuntime->startBotContainer($bot);
            } elseif (! $settings->boolean('runtime_keep_paused_warm', false) || $data['status'] !== 'paused') {
                $dockerRuntime->stopBotContainer($bot);
            }
        }

        $this->audit->log('admin', 'bot.status_changed', "Admin changed bot #{$bot->id} ({$bot->name}) status to {$data['status']}.", [
            'bot_id' => $bot->id,
            'status' => $data['status'],
        ], $request->user(), 'success', $bot);

        return back()->with('success', "Bot status updated to {$data['status']}.");
    }
}

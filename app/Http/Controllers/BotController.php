<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Models\BotCommand;
use App\Models\BotUser;
use App\Models\WebhookDeliveryLog;
use App\Services\BotAccessService;
use App\Services\AuditLogService;
use App\Services\PlanAccessService;
use App\Services\RuntimeSettingsService;
use App\Services\TelegramBotService;
use App\Services\UserStorageService;
use App\Support\PublicCallbackUrl;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BotController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly PlanAccessService $planAccess,
        private readonly UserStorageService $storageService,
        private readonly AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        $user   = $request->user();
        $search = trim((string) $request->query('search', ''));
        $filter = (string) $request->query('filter', 'all');
        $sort   = (string) $request->query('sort', 'newest');

        $validFilters = array_merge(['all'], Bot::STATUSES);
        $validSorts   = ['newest', 'oldest', 'most_commands', 'most_users', 'status'];

        if (! in_array($filter, $validFilters, true)) {
            $filter = 'all';
        }
        if (! in_array($sort, $validSorts, true)) {
            $sort = 'newest';
        }

        // Aggregate counts across ALL user bots (not paginated)
        $botIds            = $user->bots()->pluck('id');
        $totalBots         = $botIds->count();
        $statusCounts      = $totalBots > 0
            ? Bot::whereIn('id', $botIds)
                ->selectRaw('status, count(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray()
            : [];
        $totalCommands     = $totalBots > 0 ? BotCommand::whereIn('bot_id', $botIds)->count() : 0;
        $totalTrackedUsers = $totalBots > 0 ? BotUser::whereIn('bot_id', $botIds)->count() : 0;

        // Plan limit
        $botsLimit    = $this->planAccess->userLimit($user, 'bots_allowed', null);
        $limitReached = $botsLimit !== null && $botsLimit !== 'unlimited' && $totalBots >= (int) $botsLimit;

        // Build paginated query with search / filter / sort
        $query = $user->bots()->withCount([
            'commands',
            'botUsers as users_count',
            'botUsers as active_users_count' => fn (Builder $q) => $q->where('last_active_at', '>=', now()->subHours(24)),
        ]);

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('telegram_username', 'like', "%{$search}%")
                  ->orWhere('telegram_bot_id', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        match ($sort) {
            'oldest'        => $query->oldest(),
            'most_commands' => $query->orderByDesc('commands_count'),
            'most_users'    => $query->orderByDesc('users_count'),
            'status'        => $query->orderBy('status')->latest(),
            default         => $query->latest(),
        };

        return view('bots.index', [
            'bots'              => $query->paginate(12)->appends($request->only(['search', 'filter', 'sort'])),
            'search'            => $search,
            'filter'            => $filter,
            'sort'              => $sort,
            'statusCounts'      => $statusCounts,
            'totalBots'         => $totalBots,
            'totalCommands'     => $totalCommands,
            'totalTrackedUsers' => $totalTrackedUsers,
            'botsLimit'         => $botsLimit,
            'limitReached'      => $limitReached,
        ]);
    }

    public function create(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (! $this->planAccess->userHasFeature($user, 'bot_creation')) {
            return redirect()->route('bots.index')
                ->withErrors(['plan' => 'This feature is not available on your current plan.']);
        }

        $botsUsed     = $user->bots()->count();
        $botsLimit    = $this->planAccess->userLimit($user, 'bots_allowed', 1);
        $limitReached = ! $this->planAccess->canCreateBot($user);

        return view('bots.create', compact('botsUsed', 'botsLimit', 'limitReached'));
    }

    public function store(Request $request, TelegramBotService $telegram): RedirectResponse
    {
        if (! $this->planAccess->userHasFeature($request->user(), 'bot_creation')) {
            return back()->withErrors(['token' => 'This feature is not available on your current plan.']);
        }

        if (! $this->planAccess->canCreateBot($request->user())) {
            return back()->withErrors(['token' => 'You have reached your plan limit for bots. Upgrade your plan to create more.']);
        }

        if ($request->filled('bot_token') && ! $request->filled('token')) {
            $request->merge(['token' => $request->input('bot_token')]);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'name'  => ['required', 'string', 'max:100'],
        ]);

        $tokenHash = Bot::tokenHash($data['token']);

        if (Bot::tokenInUse($data['token'])) {
            return back()
                ->withErrors(['token' => 'This bot token is already connected to another workspace.'])
                ->withInput($request->except('token'));
        }

        $telegramResult = $telegram->validateToken($data['token']);

        if (! $telegramResult['valid']) {
            return back()
                ->withErrors(['token' => $telegramResult['message']])
                ->withInput($request->except('token'));
        }

        $telegramData = $telegramResult['data'];

        $bot = $request->user()->bots()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($request->user()->id, $data['name']),
            'token_encrypted' => $data['token'],
            'token_hash' => $tokenHash,
            'status' => 'stopped',
            'language' => 'javascript',
            'setup_type' => 'custom',
            'telegram_bot_id' => isset($telegramData['id']) ? (string) $telegramData['id'] : null,
            'telegram_username' => $telegramData['username'] ?? null,
            'telegram_first_name' => $telegramData['first_name'] ?? null,
            'telegram_can_join_groups' => $telegramData['can_join_groups'] ?? null,
            'telegram_can_read_all_group_messages' => $telegramData['can_read_all_group_messages'] ?? null,
            'telegram_supports_inline_queries' => $telegramData['supports_inline_queries'] ?? null,
            'token_verified_at' => now(),
        ]);

        $bot->setting()->create();
        $this->log($request, 'created_bot', 'Created bot: '.$bot->name);
        $this->audit->log('bot', 'bot_created', 'Bot created.', ['bot_id' => $bot->id], $request->user(), 'success', Bot::class, $bot->id);
        $this->setWebhookAfterCreate($request, $bot, $telegram, $data['token']);

        return redirect()->route('bots.show', $bot)->with('status', 'Bot created and verified successfully.');
    }

    public function show(Request $request, Bot $bot): View
    {
        $this->access->authorize($request, $bot);
        $showUserCodeErrors = app(RuntimeSettingsService::class)->boolean('show_user_code_errors_to_owners', false);
        $bot->load([
            'setting',
            'commands',
            'logs' => fn ($query) => $query
                ->whereIn('type', ['error', 'runtime'])
                ->when(! $showUserCodeErrors, fn ($query) => $query->where(function ($query): void {
                    $query->whereNull('context->category')
                        ->orWhere('context->category', '!=', 'user_code');
                }))
                ->latest('created_at')
                ->limit(50),
            'commandLogs' => fn ($query) => $query->latest('created_at')->limit(50),
            'commandLogs.command',
        ]);

        // Sort commands alphabetically by display name, stripping leading / and symbols
        $bot->setRelation(
            'commands',
            $bot->commands->sortBy(function ($cmd) {
                $name = $cmd->displayName();
                return preg_replace('/^[^a-zA-Z0-9]+/u', '', strtolower($name));
            })->values()
        );

        $userSearch = trim((string) $request->query('user_search', ''));
        $userStatus = (string) $request->query('user_status', 'all');

        $botUsers = $bot->botUsers()
            ->when($userSearch !== '', function (Builder $query) use ($userSearch): void {
                $query->where(function (Builder $query) use ($userSearch): void {
                    $query->where('telegram_user_id', 'like', "%{$userSearch}%")
                        ->orWhere('telegram_username', 'like', "%{$userSearch}%")
                        ->orWhere('telegram_first_name', 'like', "%{$userSearch}%");
                });
            })
            ->when(in_array($userStatus, ['active', 'blocked', 'paused', 'deleted'], true), fn (Builder $query) => $query->where('status', $userStatus))
            ->latest('last_active_at')
            ->limit(50)
            ->get();

        if (! $bot->setting) {
            $bot->setting()->create();
            $bot->load('setting');
        }

        return view('bots.show', [
            'bot'                    => $bot,
            'activeTab'              => $request->query('tab', 'commands'),
            'botUsers'               => $botUsers,
            'botUserAnalytics'       => $this->botUserAnalytics($bot),
            'botUserLanguages'       => $this->botUserLanguages($bot),
            'botUserFilters'         => [
                'search' => $userSearch,
                'status' => $userStatus,
            ],
            'botBroadcasts'          => $bot->broadcasts()->withCount([
                'recipients as pending_count' => fn ($query) => $query->where('status', 'pending'),
            ])->latest()->limit(25)->get(),
            'broadcastTargetCounts'  => $this->broadcastTargetCounts($bot),
            'hasCustomWebhook'       => $this->planAccess->userHasFeature($request->user(), 'custom_webhooks'),
            'webhookDeliveryLogs'    => $this->webhookDeliveryLogs($bot),
            'telegramWebhookHealth'  => $this->telegramWebhookHealth($bot),
            'showUserCodeErrors'     => $showUserCodeErrors,
        ]);
    }

    public function commands(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'commands']);
    }

    public function settings(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        return redirect()->route('bots.show', ['bot' => $bot, 'tab' => 'settings']);
    }

    public function destroy(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $name = $bot->name;

        $bot->delete();
        $this->log($request, 'bot_recycled', 'Moved bot to recycle bin: '.$name);
        $this->audit->log('recycle', 'bot.moved_to_recycle_bin', 'Bot moved to recycle bin.', [
            'bot_id' => $bot->id,
            'name' => $name,
        ], $request->user(), 'success', Bot::class, $bot->id);

        return redirect()->route('bots.index')->with('status', 'Bot moved to recycle bin.');
    }

    private function uniqueSlug(int $userId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'bot';
        $slug = $base;
        $count = 2;

        while (Bot::withTrashed()
            ->where('user_id', $userId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }

    private function setWebhookAfterCreate(Request $request, Bot $bot, TelegramBotService $telegram, string $token): void
    {
        if (! $this->hasPublicHttpsUrl()) {
            $bot->update([
                'webhook_status' => 'failed',
                'webhook_last_error' => 'Telegram requires a public HTTPS webhook URL. Set the public callback URL to your Cloudflare Tunnel, LocalTunnel, or ngrok HTTPS URL.',
            ]);

            return;
        }

        if (! $bot->webhook_secret) {
            $bot->forceFill(['webhook_secret' => Str::random(48)])->save();
        }

        $url = $bot->webhook_endpoint;
        $result = $telegram->setWebhook($token, $url);

        if (! $result['ok']) {
            $bot->update([
                'webhook_url' => $url,
                'webhook_status' => 'failed',
                'webhook_last_error' => $result['message'] ?? 'Unable to set Telegram webhook.',
            ]);

            return;
        }

        $bot->update([
            'status' => 'running',
            'webhook_url' => $url,
            'webhook_set_at' => now(),
            'webhook_status' => 'active',
            'webhook_last_error' => null,
            'last_webhook_update_at' => now(),
        ]);

        $this->log($request, 'webhook_set', 'Set webhook for bot: '.$bot->name);
    }

    private function hasPublicHttpsUrl(): bool
    {
        return PublicCallbackUrl::isPublicHttps();
    }

    private function telegramWebhookHealth(Bot $bot): array
    {
        $expectedUrl = $bot->webhook_secret ? PublicCallbackUrl::to('/telegram/webhook/'.$bot->id.'/'.$bot->webhook_secret) : null;
        $storedUrl = $bot->webhook_url;

        return [
            'expected_url' => $expectedUrl,
            'stored_url' => $storedUrl,
            'uses_current_public_url' => filled($expectedUrl) && filled($storedUrl) && hash_equals($expectedUrl, $storedUrl),
            'status' => $bot->webhook_status,
            'last_reset_at' => $bot->webhook_set_at,
            'last_checked_at' => $bot->last_webhook_update_at,
            'last_error' => $bot->webhook_last_error,
        ];
    }

    private function webhookDeliveryLogs(Bot $bot): \Illuminate\Support\Collection
    {
        try {
            return WebhookDeliveryLog::where('bot_id', $bot->id)
                ->latest()
                ->limit(20)
                ->get();
        } catch (\Exception) {
            return collect();
        }
    }

    private function botUserAnalytics(Bot $bot): array
    {
        $base = $bot->botUsers();

        return [
            'total_users' => (clone $base)->count(),
            'active_24h' => (clone $base)->where('last_active_at', '>=', now()->subHours(24))->count(),
            'active_48h' => (clone $base)->where('last_active_at', '>=', now()->subHours(48))->count(),
            'active_72h' => (clone $base)->where('last_active_at', '>=', now()->subHours(72))->count(),
            'active_7d' => (clone $base)->where('last_active_at', '>=', now()->subDays(7))->count(),
            'active_30d' => (clone $base)->where('last_active_at', '>=', now()->subDays(30))->count(),
            'new_24h' => (clone $base)->where('first_seen_at', '>=', now()->subHours(24))->count(),
            'new_7d' => (clone $base)->where('first_seen_at', '>=', now()->subDays(7))->count(),
            'new_30d' => (clone $base)->where('first_seen_at', '>=', now()->subDays(30))->count(),
            'blocked_users' => (clone $base)->where('status', 'blocked')->count(),
            'paused_users' => (clone $base)->where('status', 'paused')->count(),
            'deleted_users' => (clone $base)->where('status', 'deleted')->count(),
        ];
    }

    private function botUserLanguages(Bot $bot): array
    {
        $total = max($bot->botUsers()->count(), 1);

        return $bot->botUsers()
            ->selectRaw("coalesce(nullif(telegram_language_code, ''), 'unknown') as language, count(*) as aggregate")
            ->groupBy(DB::raw("coalesce(nullif(telegram_language_code, ''), 'unknown')"))
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'language' => $row->language,
                'count' => (int) $row->aggregate,
                'percentage' => round(((int) $row->aggregate / $total) * 100, 1),
            ])
            ->all();
    }

    private function broadcastTargetCounts(Bot $bot): array
    {
        return [
            'all_active' => $bot->botUsers()->where('status', 'active')->count(),
            'active_24h' => $bot->botUsers()->where('status', 'active')->where('last_active_at', '>=', now()->subHours(24))->count(),
            'active_48h' => $bot->botUsers()->where('status', 'active')->where('last_active_at', '>=', now()->subHours(48))->count(),
            'active_72h' => $bot->botUsers()->where('status', 'active')->where('last_active_at', '>=', now()->subHours(72))->count(),
            'active_7d' => $bot->botUsers()->where('status', 'active')->where('last_active_at', '>=', now()->subDays(7))->count(),
            'active_30d' => $bot->botUsers()->where('status', 'active')->where('last_active_at', '>=', now()->subDays(30))->count(),
            'paused_users' => $bot->botUsers()->where('status', 'paused')->count(),
            'blocked_users' => $bot->botUsers()->where('status', 'blocked')->count(),
            'specific_users' => 0,
        ];
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
    }
}

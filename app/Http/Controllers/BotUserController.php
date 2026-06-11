<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\BotUserRuntimeData;
use App\Services\BotAccessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class BotUserController extends Controller
{
    public function __construct(private readonly BotAccessService $access) {}

    public function index(Request $request, Bot $bot): View
    {
        $this->access->authorize($request, $bot);

        $users = $this->filteredQuery($request, $bot)->paginate(25)->withQueryString();

        return view('bots.show', [
            'bot' => $bot->load(['setting', 'commands', 'logs', 'commandLogs.command']),
            'activeTab' => 'admin',
            'botUsers' => $users->getCollection(),
            'botUsersPaginator' => $users,
            'botUserAnalytics' => $this->analytics($bot),
            'botUserLanguages' => [],
            'botUserFilters' => [
                'search' => trim((string) $request->query('search', '')),
                'status' => (string) $request->query('status', 'all'),
            ],
            'botBroadcasts' => $bot->broadcasts()->latest()->limit(25)->get(),
            'broadcastTargetCounts' => [],
            'adminSubTab' => 'users',
        ]);
    }

    public function updateStatus(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(BotUser::STATUSES)],
        ]);

        return $this->setStatus($request, $bot, $botUser, $data['status'], 'bot_user_status_updated');
    }

    public function block(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        Log::info('[BotHost] bot_user_blocked_denied', [
            'bot_id' => $bot->id,
            'telegram_user_id' => $botUser->telegram_user_id,
            'action' => 'blocked',
            'admin_user_id' => $request->user()?->id,
        ]);

        return $this->setStatus($request, $bot, $botUser, 'blocked', 'bot_user_blocked');
    }

    public function unblock(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        Log::info('[BotHost] bot_user_unblocked', [
            'bot_id' => $bot->id,
            'telegram_user_id' => $botUser->telegram_user_id,
            'action' => 'unblocked',
            'admin_user_id' => $request->user()?->id,
        ]);

        return $this->setStatus($request, $bot, $botUser, 'active', 'bot_user_unblocked');
    }

    public function pause(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        return $this->setStatus($request, $bot, $botUser, 'paused', 'bot_user_paused');
    }

    public function resume(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        return $this->setStatus($request, $bot, $botUser, 'active', 'bot_user_resumed');
    }

    public function markDeleted(Request $request, Bot $bot, BotUser $botUser): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        abort_unless($botUser->bot_id === $bot->id, 403);

        $telegramUserId = $botUser->telegram_user_id;

        // Clear all bot-scoped runtime storage for this user
        BotUserRuntimeData::where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->delete();

        // Hard-delete the record so the user starts fresh on return
        $botUser->delete();

        Log::info('[BotHost] bot_user_deleted_reset', [
            'bot_id' => $bot->id,
            'telegram_user_id' => $telegramUserId,
            'action' => 'reset',
            'admin_user_id' => $request->user()?->id,
        ]);

        $this->log($request, 'bot_user_reset', "Bot {$bot->id} user {$telegramUserId} data reset. If they return, they start fresh.");

        return redirect()->route('bots.show', [
            'bot' => $bot,
            'tab' => 'admin',
            'admin_tab' => 'users',
        ])->with('status', 'User data reset. The user will start fresh on return.');
    }

    private function filteredQuery(Request $request, Bot $bot): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $language = trim((string) $request->query('language', ''));
        $activeRange = (string) $request->query('active_range', 'all');
        $sort = (string) $request->query('sort', 'last_active');

        return $bot->botUsers()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('telegram_user_id', 'like', "%{$search}%")
                        ->orWhere('telegram_username', 'like', "%{$search}%")
                        ->orWhere('telegram_first_name', 'like', "%{$search}%")
                        ->orWhere('telegram_last_name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, BotUser::STATUSES, true), fn (Builder $query) => $query->where('status', $status))
            ->when($language !== '', fn (Builder $query) => $query->where('telegram_language_code', $language))
            ->when($activeRange !== 'all', function (Builder $query) use ($activeRange): void {
                match ($activeRange) {
                    '24h' => $query->where('last_active_at', '>=', now()->subHours(24)),
                    '48h' => $query->where('last_active_at', '>=', now()->subHours(48)),
                    '72h' => $query->where('last_active_at', '>=', now()->subHours(72)),
                    '7d' => $query->where('last_active_at', '>=', now()->subDays(7)),
                    '30d' => $query->where('last_active_at', '>=', now()->subDays(30)),
                    default => null,
                };
            })
            ->when($sort === 'oldest', fn (Builder $query) => $query->oldest('created_at'))
            ->when($sort === 'newest', fn (Builder $query) => $query->latest('created_at'))
            ->when($sort === 'most_messages', fn (Builder $query) => $query->orderByDesc('message_count'))
            ->when($sort === 'most_commands', fn (Builder $query) => $query->orderByDesc('command_count'))
            ->when(! in_array($sort, ['oldest', 'newest', 'most_messages', 'most_commands'], true), fn (Builder $query) => $query->latest('last_active_at'));
    }

    private function setStatus(Request $request, Bot $bot, BotUser $botUser, string $status, string $action): RedirectResponse
    {
        $this->access->authorize($request, $bot);
        abort_unless($botUser->bot_id === $bot->id, 403);

        $botUser->forceFill([
            'status' => $status,
            'blocked_at' => $status === 'blocked' ? now() : null,
        ])->save();

        $this->log($request, $action, "Bot {$bot->id} user {$botUser->telegram_user_id} status set to {$status}.");

        return redirect()->route('bots.show', [
            'bot' => $bot,
            'tab' => 'admin',
            'admin_tab' => 'users',
        ])->with('status', 'Bot user status updated.');
    }

    private function analytics(Bot $bot): array
    {
        return [
            'total_users' => $bot->botUsers()->count(),
            'active_24h' => $bot->botUsers()->where('last_active_at', '>=', now()->subHours(24))->count(),
            'active_48h' => $bot->botUsers()->where('last_active_at', '>=', now()->subHours(48))->count(),
            'active_72h' => $bot->botUsers()->where('last_active_at', '>=', now()->subHours(72))->count(),
            'active_7d' => $bot->botUsers()->where('last_active_at', '>=', now()->subDays(7))->count(),
            'active_30d' => $bot->botUsers()->where('last_active_at', '>=', now()->subDays(30))->count(),
            'new_24h' => $bot->botUsers()->where('first_seen_at', '>=', now()->subHours(24))->count(),
            'new_7d' => $bot->botUsers()->where('first_seen_at', '>=', now()->subDays(7))->count(),
            'new_30d' => $bot->botUsers()->where('first_seen_at', '>=', now()->subDays(30))->count(),
            'blocked_users' => $bot->botUsers()->where('status', 'blocked')->count(),
            'paused_users' => $bot->botUsers()->where('status', 'paused')->count(),
            'deleted_users' => $bot->botUsers()->where('status', 'deleted')->count(),
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

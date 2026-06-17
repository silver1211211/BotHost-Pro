<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\BotCommand;
use App\Services\AuditLogService;
use App\Services\BotRecycleService;
use App\Services\PlanAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecycleBinController extends Controller
{
    public function __construct(
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
        private readonly BotRecycleService $recycle,
    ) {}

    public function index(Request $request): View
    {
        $retentionDays = 30;
        $bots = Bot::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->latest('deleted_at')
            ->paginate(12)
            ->through(function (Bot $bot) use ($retentionDays): Bot {
                $deletedDays = (int) $bot->deleted_at->diffInDays(now());
                $bot->deleted_days = $deletedDays;
                $bot->days_remaining = max(0, $retentionDays - $deletedDays);

                return $bot;
            });

        return view('recycle-bin.index', [
            'bots' => $bots,
            'retentionDays' => $retentionDays,
            'deletedCount' => $bots->total(),
            'expiringCount' => Bot::onlyTrashed()
                ->where('user_id', $request->user()->id)
                ->where('deleted_at', '<=', now()->subDays($retentionDays - 7))
                ->count(),
            'commands' => BotCommand::onlyTrashed()
                ->whereHas('bot', fn ($query) => $query->where('user_id', $request->user()->id))
                ->with('bot')
                ->latest('deleted_at')
                ->paginate(12, ['*'], 'commands_page'),
            'deletedCommandCount' => BotCommand::onlyTrashed()
                ->whereHas('bot', fn ($query) => $query->where('user_id', $request->user()->id))
                ->count(),
        ]);
    }

    public function restore(Request $request, Bot|int $bot): RedirectResponse
    {
        $botId = $bot instanceof Bot ? $bot->id : $bot;
        $trashedBot = Bot::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($botId);

        if (! $this->planAccess->canCreateBot($request->user())) {
            return back()->withErrors(['restore' => 'You have reached your bot limit. Upgrade your plan or delete another bot before restoring.']);
        }

        $trashedBot->restore();

        $this->audit->log('recycle', 'bot.restored', 'Bot restored from recycle bin.', [
            'bot_id' => $trashedBot->id,
            'name' => $trashedBot->name,
        ], $request->user(), 'success', Bot::class, $trashedBot->id);

        return redirect()->route('bots.index')->with('status', 'Bot restored.');
    }

    public function forceDelete(Request $request, Bot|int $bot): RedirectResponse
    {
        $botId = $bot instanceof Bot ? $bot->id : $bot;
        $trashedBot = Bot::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($botId);

        $botId = $trashedBot->id;
        $name = $trashedBot->name;

        $this->recycle->forceDelete($trashedBot);

        $this->audit->log('recycle', 'bot.permanently_deleted', 'Bot permanently deleted from recycle bin.', [
            'bot_id' => $botId,
            'name' => $name,
        ], $request->user(), 'success', Bot::class, $botId);

        return back()->with('status', 'Bot permanently deleted.');
    }

    public function restoreCommand(Request $request, int|string $command): RedirectResponse
    {
        $commandId = (int) $command;
        $trashedCommand = BotCommand::onlyTrashed()
            ->whereHas('bot', fn ($query) => $query->where('user_id', $request->user()->id))
            ->findOrFail($commandId);

        $conflict = BotCommand::query()
            ->where('bot_id', $trashedCommand->bot_id)
            ->where('command_name', $trashedCommand->command_name)
            ->exists();

        if ($conflict) {
            return back()->withErrors(['restore' => 'An active command with this exact name already exists. Delete or rename it before restoring this command.']);
        }

        $trashedCommand->restore();

        $this->audit->log('recycle', 'command.restored', 'Command restored from recycle bin.', [
            'bot_id' => $trashedCommand->bot_id,
            'command_id' => $trashedCommand->id,
            'command_name' => $trashedCommand->command_name,
        ], $request->user(), 'success', BotCommand::class, $trashedCommand->id);

        return back()->with('status', 'Command restored.');
    }

    public function forceDeleteCommand(Request $request, int|string $command): RedirectResponse
    {
        $commandId = (int) $command;
        $trashedCommand = BotCommand::onlyTrashed()
            ->whereHas('bot', fn ($query) => $query->where('user_id', $request->user()->id))
            ->findOrFail($commandId);

        $botId = $trashedCommand->bot_id;
        $commandName = $trashedCommand->command_name;
        $trashedCommand->forceDelete();

        $this->audit->log('recycle', 'command.permanently_deleted', 'Command permanently deleted from recycle bin.', [
            'bot_id' => $botId,
            'command_id' => $commandId,
            'command_name' => $commandName,
        ], $request->user(), 'success', BotCommand::class, $commandId);

        return back()->with('status', 'Command permanently deleted.');
    }
}

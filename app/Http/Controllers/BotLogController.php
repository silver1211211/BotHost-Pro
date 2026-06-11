<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\AuditLogService;
use App\Services\BotAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BotLogController extends Controller
{
    public function __construct(
        private readonly BotAccessService $access,
        private readonly AuditLogService $audit,
    ) {}

    public function clearErrors(Request $request, Bot $bot): RedirectResponse
    {
        $this->access->authorize($request, $bot);

        $bot->logs()
            ->whereIn('type', ['error', 'runtime'])
            ->delete();

        $this->audit->log('bot', 'command_errors.cleared', 'Bot error logs cleared.', [
            'bot_id' => $bot->id,
        ], $request->user(), 'success', $bot);

        return redirect()
            ->route('bots.show', ['bot' => $bot, 'tab' => 'errors'])
            ->with('status', 'Error logs cleared.');
    }
}

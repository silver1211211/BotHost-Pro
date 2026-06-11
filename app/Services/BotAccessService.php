<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotCommand;
use Illuminate\Http\Request;

class BotAccessService
{
    public function authorize(Request $request, Bot $bot): void
    {
        abort_unless($bot->user_id === $request->user()->id, 403);
    }

    public function authorizeCommand(Request $request, Bot $bot, BotCommand $command): void
    {
        $this->authorize($request, $bot);

        abort_unless($command->bot_id === $bot->id, 403);
    }
}

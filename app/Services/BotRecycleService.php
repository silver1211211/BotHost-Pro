<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Services\DockerRuntimeService;
use Throwable;

class BotRecycleService
{
    public function forceDelete(Bot $bot): void
    {
        try {
            app(DockerRuntimeService::class)->removeBotContainer($bot);
        } catch (Throwable) {
            // Runtime cleanup should not block permanent delete.
        }

        $bot->commands()->delete();
        $bot->logs()->delete();
        $bot->commandLogs()->delete();
        $bot->broadcasts()->each(function (BotBroadcast $broadcast): void {
            $broadcast->recipients()->delete();
            $broadcast->delete();
        });
        $bot->botUsers()->delete();
        $bot->setting()->delete();
        $bot->templateImports()->delete();

        try {
            app(UserStorageService::class)->deleteBotStorage($bot);
        } catch (Throwable) {
            // Storage cleanup should not block database cleanup.
        }

        $bot->forceDelete();
    }
}

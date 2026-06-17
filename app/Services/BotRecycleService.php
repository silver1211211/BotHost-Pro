<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotBroadcast;
use App\Services\BotRuntimeCacheService;
use App\Services\CommandRuntimeCacheService;
use App\Services\DockerRuntimeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $bot->commands()->withTrashed()->get()->each->forceDelete();
        $bot->logs()->delete();
        $bot->commandLogs()->delete();
        $bot->broadcasts()->each(function (BotBroadcast $broadcast): void {
            $broadcast->recipients()->delete();
            $broadcast->delete();
        });
        $bot->botUsers()->delete();
        $bot->setting()->delete();
        $bot->templateImports()->delete();
        $this->deleteRows('bot_runtime_data', 'bot_id', $bot->id);
        $this->deleteRows('bot_user_runtime_data', 'bot_id', $bot->id);
        $this->deleteRows('bot_transfers', 'source_bot_id', $bot->id);
        $this->deleteRows('webhook_delivery_logs', 'bot_id', $bot->id);
        $this->nullRows('admin_broadcasts', 'target_bot_id', $bot->id);

        try {
            app(UserStorageService::class)->deleteBotStorage($bot);
        } catch (Throwable) {
            // Storage cleanup should not block database cleanup.
        }

        try {
            app(BotRuntimeCacheService::class)->clearBot($bot);
            app(CommandRuntimeCacheService::class)->clearBot($bot);
        } catch (Throwable) {
            // Cache cleanup should not block permanent delete.
        }

        DB::table($bot->getTable())
            ->where($bot->getKeyName(), $bot->getKey())
            ->delete();
    }

    private function deleteRows(string $table, string $column, int $botId): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->where($column, $botId)->delete();
        }
    }

    private function nullRows(string $table, string $column, int $botId): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->where($column, $botId)->update([$column => null]);
        }
    }
}

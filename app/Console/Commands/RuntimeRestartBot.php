<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeRestartBot extends Command
{
    protected $signature = 'runtime:restart-bot {bot_id}';
    protected $description = 'Restart a Docker runtime container for a bot.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $bot = Bot::withTrashed()->find($this->argument('bot_id'));

        if (! $bot) {
            $this->error('Bot not found.');
            return self::FAILURE;
        }

        $result = $runtime->restartBotContainer($bot);

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Runtime failed to restart.');
            return self::FAILURE;
        }

        $this->info('Runtime restarted.');
        return self::SUCCESS;
    }
}

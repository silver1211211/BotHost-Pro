<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeStopBot extends Command
{
    protected $signature = 'runtime:stop-bot {bot_id}';
    protected $description = 'Stop a Docker runtime container for a bot.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $bot = Bot::withTrashed()->find($this->argument('bot_id'));

        if (! $bot) {
            $this->error('Bot not found.');
            return self::FAILURE;
        }

        $result = $runtime->stopBotContainer($bot);

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Runtime failed to stop.');
            return self::FAILURE;
        }

        $this->info('Runtime stopped.');
        return self::SUCCESS;
    }
}

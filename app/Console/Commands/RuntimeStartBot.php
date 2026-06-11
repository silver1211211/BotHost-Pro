<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeStartBot extends Command
{
    protected $signature = 'runtime:start-bot {bot_id}';
    protected $description = 'Start and warm a Docker runtime container for a bot.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $bot = Bot::withTrashed()->find($this->argument('bot_id'));

        if (! $bot) {
            $this->error('Bot not found.');
            return self::FAILURE;
        }

        $result = $runtime->startBotContainer($bot);

        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Runtime failed to start.');
            return self::FAILURE;
        }

        $this->info('Runtime started: '.$result['container_name'].' on port '.$result['host_port']);
        return self::SUCCESS;
    }
}

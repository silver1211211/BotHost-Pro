<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\DockerRuntimeService;
use Illuminate\Console\Command;

class RuntimeStatus extends Command
{
    protected $signature = 'runtime:status {bot_id}';
    protected $description = 'Show Docker runtime status for a bot.';

    public function handle(DockerRuntimeService $runtime): int
    {
        $bot = Bot::withTrashed()->find($this->argument('bot_id'));

        if (! $bot) {
            $this->error('Bot not found.');
            return self::FAILURE;
        }

        $status = $runtime->getBotContainerStatus($bot);

        $this->table(['Key', 'Value'], collect($status)->map(fn ($value, $key) => [
            $key,
            is_bool($value) ? ($value ? 'yes' : 'no') : (is_array($value) ? json_encode($value) : (string) $value),
        ])->values()->all());

        return self::SUCCESS;
    }
}

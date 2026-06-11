<?php

namespace App\Jobs;

use App\Models\BotBroadcast;
use App\Services\BotBroadcastSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;

class ProcessBotBroadcastBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $broadcastId) {}

    public function handle(BotBroadcastSenderService $sender): void
    {
        $broadcast = BotBroadcast::query()->find($this->broadcastId);

        if (! $broadcast || in_array($broadcast->status, ['cancelled', 'completed', 'failed'], true)) {
            return;
        }

        $sender->processNextBatch($broadcast, $sender->batchSize());
        $broadcast->refresh();

        if (
            Config::get('queue.default') !== 'sync'
            && in_array($broadcast->status, ['queued', 'running', 'sending'], true)
            && $broadcast->recipients()->where('status', 'pending')->exists()
        ) {
            self::dispatch($broadcast->id)->delay(now()->addSecond());
        }
    }
}

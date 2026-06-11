<?php

namespace App\Console\Commands;

use App\Models\BotBroadcast;
use App\Services\BotBroadcastSenderService;
use Illuminate\Console\Command;
use Throwable;

class ProcessBroadcasts extends Command
{
    protected $signature = 'broadcasts:process {--limit=10 : Maximum broadcasts to process this run}';

    protected $description = 'Process queued, due scheduled, and running bot broadcasts.';

    public function handle(BotBroadcastSenderService $sender): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $processedBroadcasts = 0;
        $processedRecipients = 0;
        $failedBroadcasts = 0;

        BotBroadcast::query()
            ->with('bot')
            ->where(function ($query): void {
                $query->whereIn('status', ['queued', 'running', 'sending'])
                    ->orWhere(function ($query): void {
                        $query->where('status', 'scheduled')
                            ->where(function ($query): void {
                                $query->whereNull('scheduled_at')
                                    ->orWhere('scheduled_at', '<=', now());
                            });
                    });
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (BotBroadcast $broadcast) use ($sender, &$processedBroadcasts, &$processedRecipients, &$failedBroadcasts): void {
                try {
                    $summary = $sender->processNextBatch($broadcast, $sender->batchSize());
                    $processedBroadcasts++;
                    $processedRecipients += $summary['processed'];
                } catch (Throwable $exception) {
                    report($exception);
                    $failedBroadcasts++;
                }
            });

        $this->info("Processed {$processedBroadcasts} broadcast(s), {$processedRecipients} recipient(s). {$failedBroadcasts} failed.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Services\AuditLogService;
use App\Services\BotRecycleService;
use Illuminate\Console\Command;
use Throwable;

class PurgeRecycledBots extends Command
{
    protected $signature = 'bots:purge-recycled {--days=30 : Recycle bin retention period in days}';

    protected $description = 'Permanently delete bots that have been in the recycle bin past the retention period.';

    public function handle(BotRecycleService $recycle, AuditLogService $audit): int
    {
        $days = max(1, (int) $this->option('days'));
        $purged = 0;
        $failed = 0;

        Bot::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->orderBy('id')
            ->chunkById(100, function ($bots) use ($recycle, &$purged, &$failed): void {
                foreach ($bots as $bot) {
                    try {
                        $recycle->forceDelete($bot);
                        $purged++;
                    } catch (Throwable $e) {
                        report($e);
                        $failed++;
                    }
                }
            });

        $message = "Purged {$purged} recycled bot(s).";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }

        $this->info($message);

        $audit->log('recycle', 'recycled_bots.purged', $message, [
            'purged_count' => $purged,
            'failed_count' => $failed,
            'retention_days' => $days,
        ], null, $failed > 0 ? 'partial' : 'success');

        return self::SUCCESS;
    }
}

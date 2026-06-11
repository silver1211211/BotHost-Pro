<?php

namespace App\Console\Commands;

use App\Models\BotLog;
use App\Models\User;
use App\Services\PlanAccessService;
use Illuminate\Console\Command;

class PrunePlanRetentionLogs extends Command
{
    protected $signature = 'logs:prune-plan-retention {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete bot logs older than each user\'s plan log retention period.';

    public function handle(PlanAccessService $planAccess): int
    {
        $dryRun = $this->option('dry-run');
        $totalDeleted = 0;

        User::query()->cursor()->each(function (User $user) use ($planAccess, $dryRun, &$totalDeleted): void {
            $retentionDays = $planAccess->userLimit($user, 'logs_retention_days');

            // Skip users with unlimited retention
            if ($retentionDays === null || $retentionDays === 'unlimited') {
                return;
            }

            $days = (int) $retentionDays;

            if ($days <= 0) {
                return;
            }

            $cutoff = now()->subDays($days);
            $botIds = $user->bots()->pluck('id');

            if ($botIds->isEmpty()) {
                return;
            }

            $query = BotLog::query()
                ->whereIn('bot_id', $botIds)
                ->where('created_at', '<', $cutoff);

            $count = $query->count();

            if ($count === 0) {
                return;
            }

            if ($dryRun) {
                $this->line("  [dry-run] User #{$user->id} ({$user->email}): would delete {$count} log(s) older than {$days} days.");
            } else {
                $query->delete();
                $this->line("  User #{$user->id} ({$user->email}): deleted {$count} log(s) older than {$days} days.");
            }

            $totalDeleted += $count;
        });

        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$verb} {$totalDeleted} log record(s) in total.");

        return self::SUCCESS;
    }
}

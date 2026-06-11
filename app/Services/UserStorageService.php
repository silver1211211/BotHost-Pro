<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserStorageService
{
    public function __construct(private readonly PlanAccessService $planAccess) {}

    public function usedBytes(User $user): int
    {
        $total = 0;

        foreach ($user->bots()->pluck('id') as $botId) {
            $path = "broadcasts/{$botId}";

            if (Storage::disk('public')->exists($path)) {
                foreach (Storage::disk('public')->allFiles($path) as $file) {
                    try {
                        $total += Storage::disk('public')->size($file);
                    } catch (\Throwable) {
                        // skip unreadable files
                    }
                }
            }
        }

        return $total;
    }

    public function usedMb(User $user): float
    {
        return round($this->usedBytes($user) / 1048576, 2);
    }

    /** Returns MB limit as int, or 'unlimited' */
    public function limitMb(User $user): int|string
    {
        if ($user->isAdmin()) {
            return 'unlimited';
        }

        $limit = $this->planAccess->userLimit($user, 'storage_mb');

        if ($limit === null || $limit === 'unlimited') {
            return 'unlimited';
        }

        return (int) $limit;
    }

    /** Returns remaining MB as float, or 'unlimited' */
    public function remainingMb(User $user): float|string
    {
        $limit = $this->limitMb($user);

        if ($limit === 'unlimited') {
            return 'unlimited';
        }

        return max(0, round($limit - $this->usedMb($user), 2));
    }

    public function canStore(User $user, int $newBytes): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $limit = $this->limitMb($user);

        if ($limit === 'unlimited') {
            return true;
        }

        $usedBytes = $this->usedBytes($user);

        return ($usedBytes + $newBytes) <= ($limit * 1048576);
    }

    public function deleteBotStorage(Bot $bot): void
    {
        $path = "broadcasts/{$bot->id}";

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->deleteDirectory($path);
        }
    }
}

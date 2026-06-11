<?php

namespace App\Services;

use App\Models\AdminBroadcast;
use App\Models\User;
use App\Models\UserNotification;

class InAppNotificationService
{
    public function send(User $user, AdminBroadcast $broadcast, array $metadata = []): UserNotification
    {
        return UserNotification::create([
            'user_id' => $user->id,
            'admin_broadcast_id' => $broadcast->id,
            'title' => $broadcast->title,
            'message' => $broadcast->message,
            'type' => $broadcast->campaign_type ?: 'broadcast',
            'priority' => $broadcast->priority,
            'status' => 'unread',
            'metadata' => $metadata,
        ]);
    }
}

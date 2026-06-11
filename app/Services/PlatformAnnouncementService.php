<?php

namespace App\Services;

use App\Models\AdminBroadcast;
use App\Models\PlatformAnnouncement;
use App\Models\User;

class PlatformAnnouncementService
{
    public function createAnnouncement(AdminBroadcast $broadcast, ?User $admin = null): PlatformAnnouncement
    {
        return PlatformAnnouncement::create([
            'admin_broadcast_id' => $broadcast->id,
            'created_by' => $admin?->id,
            'title' => $broadcast->title,
            'message' => $broadcast->message,
            'type' => $broadcast->campaign_type ?: 'broadcast',
            'priority' => $broadcast->priority,
            'starts_at' => now(),
            'is_active' => true,
            'dismissible' => true,
            'metadata' => [
                'target_type' => $broadcast->target_type,
                'channels' => $broadcast->channels,
            ],
        ]);
    }
}

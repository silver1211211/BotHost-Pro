<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform_announcement_id', 'user_id', 'dismissed_at'])]
class PlatformAnnouncementRead extends Model
{
    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return view('notifications.index', [
            'notifications' => UserNotification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(20),
        ]);
    }
}

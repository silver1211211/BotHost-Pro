<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BotLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogsController extends Controller
{
    public function index(Request $request): View
    {
        $tab    = $request->query('tab', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = ActivityLog::with('user')->latest('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action',        'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address',  'like', "%{$search}%");
            });
        }

        match ($tab) {
            'user'     => $query->whereNotNull('user_id'),
            'system'   => $query->whereNull('user_id'),
            'security' => $query->where(fn ($q) =>
                $q->where('action', 'like', '%login%')
                  ->orWhere('action', 'like', '%password%')
                  ->orWhere('action', 'like', '%register%')
                  ->orWhere('action', 'like', '%maintenance%')
                  ->orWhere('action', 'like', '%webhook%')
                  ->orWhere('action', 'like', '%email%')
                  ->orWhere('action', 'like', '%suspend%')
            ),
            default => null,
        };

        $botLogs = BotLog::with('bot')
            ->whereIn('type', ['error', 'warning', 'security'])
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('admin.logs.index', [
            'logs'      => $query->paginate(25)->withQueryString(),
            'botLogs'   => $botLogs,
            'totalLogs' => ActivityLog::count(),
            'todayLogs' => ActivityLog::whereDate('created_at', today())->count(),
            'tab'       => $tab,
            'search'    => $search,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotCommandLog;
use App\Models\BotLog;
use App\Models\BotUser;
use App\Models\PaymentInvoice;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            // Users
            'total_users'          => User::count(),
            'active_users'         => $this->safeBotAudience(),
            'admin_users'          => User::where('role', 'admin')->count(),
            'suspended_users'      => User::where('status', 'suspended')->count(),
            'banned_users'         => User::where('status', 'banned')->count(),
            'new_users_today'      => User::whereDate('created_at', today())->count(),

            // Bots
            'total_bots'           => Bot::count(),
            'running_bots'         => Bot::where('status', 'running')->count(),
            'paused_bots'          => Bot::where('status', 'paused')->count(),
            'stopped_bots'         => Bot::where('status', 'stopped')->count(),
            'crashed_bots'         => Bot::where('status', 'crashed')->count(),
            'bots_created_today'   => Bot::whereDate('created_at', today())->count(),

            // Bot Users
            'total_bot_users'      => $this->safeBotUserCount(),
            'active_bot_users_24h' => $this->safeBotUserActive24h(),

            // Commands & Logs
            'commands_created'     => $this->safeCommandCount(),
            'command_executions'   => $this->safeCommandLogCount(),
            'runtime_errors'       => $this->safeRuntimeErrorCount(),

            // Revenue (subscription payments + template/bot purchases via invoices)
            'revenue'              => $this->safeRevenue(),
            'deposits_today'       => $this->safeDepositsToday(),

            // Activity
            'activity_events_today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.dashboard', [
            'stats'         => $stats,
            'recentUsers'   => User::latest()->take(5)->get(),
            'recentBots'    => Bot::with('user')->latest()->take(5)->get(),
            'recentErrors'  => $this->safeRecentErrors(),
            'botStatusDist' => $this->botStatusDistribution(),
            'recentActivity'=> ActivityLog::with('user')->latest('created_at')->take(6)->get(),
        ]);
    }

    private function safeBotAudience(): int
    {
        try {
            return BotUser::whereHas('bot')
                ->where('status', '!=', 'deleted')
                ->count();
        } catch (\Throwable) { return 0; }
    }

    private function safeBotUserCount(): int
    {
        try { return BotUser::count(); } catch (\Throwable) { return 0; }
    }

    private function safeBotUserActive24h(): int
    {
        try {
            return BotUser::where('last_active_at', '>=', now()->subHours(24))->count();
        } catch (\Throwable) { return 0; }
    }

    private function safeCommandCount(): int
    {
        try { return BotCommand::count(); } catch (\Throwable) { return 0; }
    }

    private function safeCommandLogCount(): int
    {
        try { return BotCommandLog::count(); } catch (\Throwable) { return 0; }
    }

    private function safeRuntimeErrorCount(): int
    {
        try {
            return BotLog::whereIn('type', ['error', 'runtime'])->count();
        } catch (\Throwable) { return 0; }
    }

    private function safeRecentErrors(): \Illuminate\Support\Collection
    {
        try {
            return BotLog::with('bot')
                ->whereIn('type', ['error', 'runtime'])
                ->latest()
                ->take(5)
                ->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    private function botStatusDistribution(): array
    {
        $statuses = ['running', 'paused', 'stopped', 'crashed', 'suspended'];
        $dist = [];
        foreach ($statuses as $s) {
            $dist[$s] = Bot::where('status', $s)->count();
        }
        return $dist;
    }

    private function safeRevenue(): float
    {
        try {
            // Paid invoices cover subscription upgrades and template/bot purchases
            $invoiceRevenue = (float) PaymentInvoice::where('status', 'paid')->sum('amount');
            // Direct subscription payments not linked to an invoice (avoid double-counting)
            $directRevenue  = (float) SubscriptionPayment::where('status', 'paid')->whereNull('payment_invoice_id')->sum('amount');
            return $invoiceRevenue + $directRevenue;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function safeDepositsToday(): float
    {
        try {
            $invoiceToday = (float) PaymentInvoice::where('status', 'paid')->whereDate('paid_at', today())->sum('amount');
            $directToday  = (float) SubscriptionPayment::where('status', 'paid')->whereNull('payment_invoice_id')->whereDate('paid_at', today())->sum('amount');
            return $invoiceToday + $directToday;
        } catch (\Throwable) {
            return 0.0;
        }
    }
}

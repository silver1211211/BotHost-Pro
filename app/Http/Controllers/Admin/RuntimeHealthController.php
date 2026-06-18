<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRuntimeActionLog;
use App\Models\Bot;
use App\Services\AdminRuntimeHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RuntimeHealthController extends Controller
{
    public function __construct(private readonly AdminRuntimeHealthService $health) {}

    public function index(): View
    {
        return view('admin.runtime.health.index', [
            'report' => $this->health->healthReport(),
            'bots' => Bot::query()->orderBy('name')->get(['id', 'name', 'runtime_mode', 'container_name']),
            'logs' => AdminRuntimeActionLog::query()->with('admin')->latest()->limit(12)->get(),
        ]);
    }

    public function runHealthCheck(Request $request): RedirectResponse
    {
        $report = $this->health->healthReport();
        $this->health->logHealthCheck($request->user(), $report);

        return back()->with('status', 'Runtime health check completed.');
    }

    public function forceApplyHelpers(Request $request): RedirectResponse
    {
        if ($request->input('confirm_force_apply') !== 'FORCE_APPLY_RUNTIME_HELPERS') {
            return back()->with('error', 'Force Apply Runtime Helpers requires exact confirmation.');
        }

        $result = $this->health->forceApplyHelpers($request->user());

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'Force apply completed.');
    }

    public function forceRuntimeRefresh(Request $request): RedirectResponse
    {
        if ($request->input('confirm_runtime_refresh') !== 'FORCE_RUNTIME_REFRESH') {
            return back()->with('error', 'Force Runtime Refresh requires exact confirmation.');
        }

        $result = $this->health->forceRuntimeRefresh($request->user());

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'Runtime refresh completed.');
    }

    public function recreateBot(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bot_id' => ['required', 'exists:bots,id'],
            'confirm_recreate_bot' => ['required', 'in:RECREATE_SELECTED_BOT_RUNTIME'],
        ]);

        $bot = Bot::query()->findOrFail($data['bot_id']);
        $result = $this->health->recreateBot($request->user(), $bot);

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'Selected bot runtime recreate completed.');
    }

    public function recreateAll(Request $request): RedirectResponse
    {
        if ($request->input('confirm_recreate_all') !== 'RECREATE_ALL_BOT_RUNTIMES') {
            return back()->with('error', 'Force Recreate All Bot Runtimes requires exact confirmation.');
        }

        $result = $this->health->recreateAll($request->user());

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'All bot runtime recreate completed.');
    }

    public function clearCache(Request $request): RedirectResponse
    {
        if ($request->input('confirm_clear_cache') !== 'CLEAR_LARAVEL_CACHE') {
            return back()->with('error', 'Clear Laravel Cache requires exact confirmation.');
        }

        $result = $this->health->clearCache($request->user());

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'Cache action completed.');
    }

    public function restartQueue(Request $request): RedirectResponse
    {
        if ($request->input('confirm_queue_restart') !== 'RESTART_QUEUE_WORKERS') {
            return back()->with('error', 'Restart Queue Workers requires exact confirmation.');
        }

        $result = $this->health->restartQueue($request->user());

        return back()->with(($result['ok'] ?? false) ? 'status' : 'error', $result['summary'] ?? 'Queue action completed.');
    }
}

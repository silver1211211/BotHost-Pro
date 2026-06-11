<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('actor');

        $query
            ->when($request->filled('category'), fn (Builder $q) => $q->where('category', $request->query('category')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->query('status')))
            ->when($request->filled('actor'), function (Builder $q) use ($request): void {
                $actor = trim((string) $request->query('actor'));
                $q->whereHas('actor', function (Builder $actorQuery) use ($actor): void {
                    $actorQuery->where('email', 'like', "%{$actor}%")
                        ->orWhere('name', 'like', "%{$actor}%")
                        ->orWhere('username', 'like', "%{$actor}%");
                });
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->query('date_to')))
            ->when($request->filled('search'), function (Builder $q) use ($request): void {
                $search = trim((string) $request->query('search'));
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('actor', function (Builder $actorQuery) use ($search): void {
                            $actorQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            });

        $request->query('sort') === 'oldest' ? $query->oldest() : $query->latest();

        return view('admin.logs.index', [
            'logs' => $query->paginate(25)->appends($request->query()),
            'filters' => $request->only(['category', 'status', 'search', 'actor', 'date_from', 'date_to', 'sort']),
            'summary' => $this->summary(),
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('actor');

        return response()->json([
            'id' => $auditLog->id,
            'timestamp' => $auditLog->created_at?->toIso8601String(),
            'actor' => $auditLog->actor?->email ?? 'System',
            'actor_name' => $auditLog->actor?->name,
            'action' => $auditLog->action,
            'category' => $auditLog->category,
            'status' => $auditLog->status,
            'description' => $auditLog->description,
            'ip_address' => $auditLog->ip_address,
            'user_agent' => $auditLog->user_agent,
            'target_type' => $auditLog->target_type,
            'target_id' => $auditLog->target_id,
            'metadata' => $this->audit->safeMetadata($auditLog->metadata),
        ]);
    }

    private function summary(): array
    {
        return [
            'admin_users' => User::where('role', 'admin')->count(),
            'failed_login_24h' => AuditLog::where('category', 'security')->where('status', 'failed')->where('action', 'like', 'login.%')->where('created_at', '>=', now()->subDay())->count(),
            'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
            'security_events' => AuditLog::where('category', 'security')->count(),
            'maintenance_mode' => PlatformSetting::getValue('platform_mode', 'live') === 'maintenance',
            'registration_enabled' => filter_var(PlatformSetting::getValue('registration_enabled', PlatformSetting::getValue('allow_registration', '1')), FILTER_VALIDATE_BOOLEAN),
            'last_webhook_reset' => AuditLog::whereIn('action', ['telegram_webhooks.reset', 'telegram_webhooks_reset_completed'])->latest()->first(),
        ];
    }
}

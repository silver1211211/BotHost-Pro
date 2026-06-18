<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount('bots')
            ->withSum(['paymentInvoices as total_spent' => fn ($q) => $q->where('status', 'completed')], 'amount');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($plan = $request->input('plan')) {
            $query->where('subscription_plan', $plan);
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'    => $query->oldest(),
            'most_bots' => $query->orderByDesc('bots_count'),
            default     => $query->latest(),
        };

        return view('admin.users.index', [
            'users'   => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'role', 'status', 'plan', 'sort']),
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'banned'])],
        ]);

        if ($user->isAdmin() && $request->user()->is($user)) {
            return back()->with('error', 'You cannot change your own status.');
        }

        $update = ['status' => $data['status']];

        if ($data['status'] === 'active') {
            $update += [
                'suspended_until'      => null,
                'suspension_message'   => null,
                'suspension_cta_label' => null,
                'suspension_cta_url'   => null,
            ];
        }

        $user->update($update);
        $this->audit->log('admin', 'user.status_changed', "Changed {$user->email} status to {$data['status']}.", [
            'status' => $data['status'],
            'user_id' => $user->id,
        ], $request->user(), 'success', $user);

        return back()->with('success', "User status updated to {$data['status']}.");
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin() && $request->user()->is($user)) {
            return back()->with('error', 'You cannot suspend yourself.');
        }

        $data = $request->validate([
            'suspend_type'    => ['required', Rule::in(['timed', 'support'])],
            'days'            => ['required_if:suspend_type,timed', 'nullable', 'integer', 'min:1', 'max:3650'],
            'message'         => ['nullable', 'string', 'max:500'],
            'cta_label'       => ['nullable', 'string', 'max:60'],
            'cta_url'         => ['nullable', 'url', 'max:500'],
        ]);

        $suspendedUntil = null;
        if ($data['suspend_type'] === 'timed' && filled($data['days'])) {
            $suspendedUntil = now()->addDays((int) $data['days']);
        }

        $user->update([
            'status'               => 'suspended',
            'suspended_until'      => $suspendedUntil,
            'suspension_message'   => filled($data['message']) ? $data['message'] : null,
            'suspension_cta_label' => filled($data['cta_label'] ?? null) ? $data['cta_label'] : null,
            'suspension_cta_url'   => filled($data['cta_url'] ?? null) ? $data['cta_url'] : null,
        ]);

        $detail = $suspendedUntil ? "until {$suspendedUntil->toDateString()}" : 'until support contact';
        $this->audit->log('admin', 'user.suspended', "Suspended {$user->email} {$detail}.", [
            'suspend_type' => $data['suspend_type'],
            'suspended_until' => $suspendedUntil?->toDateTimeString(),
            'user_id' => $user->id,
        ], $request->user(), 'warning', $user);

        return back()->with('success', "User suspended {$detail}.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $user->update([
            'status'               => 'active',
            'suspended_until'      => null,
            'suspension_message'   => null,
            'suspension_cta_label' => null,
            'suspension_cta_url'   => null,
        ]);

        $this->audit->log('admin', 'user.unsuspended', "Activated {$user->email}.", [
            'user_id' => $user->id,
        ], $request->user(), 'success', $user);

        return back()->with('success', "User account activated.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($data['role'] === 'user' && $this->isLastAdmin($user)) {
            return back()->with('error', 'Cannot remove admin — this is the only admin account.');
        }

        $updates = ['role' => $data['role']];

        if ($data['role'] === 'admin') {
            $updates['subscription_plan'] = 'business';
        }

        $user->update($updates);
        $this->audit->log('admin', 'user.role_changed', "Changed {$user->email} role to {$data['role']}.", [
            'role' => $data['role'],
            'user_id' => $user->id,
        ], $request->user(), 'warning', $user);

        $msg = $data['role'] === 'admin'
            ? "User promoted to Admin. Plan set to Business."
            : "Admin role removed from {$user->email}.";

        return back()->with('success', $msg);
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan' => ['required', Rule::in(['free', 'pro', 'business'])],
        ]);

        if ($user->isAdmin() && $data['subscription_plan'] !== 'business') {
            return back()->with('error', 'Admin accounts must stay on the Business plan.');
        }

        $user->update($data);
        $this->audit->log('admin', 'user.plan_changed', "Changed {$user->email} plan to {$data['subscription_plan']}.", [
            'subscription_plan' => $data['subscription_plan'],
            'user_id' => $user->id,
        ], $request->user(), 'success', $user);

        return back()->with('success', "Plan updated to ".ucfirst($data['subscription_plan']).".");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) || $user->isAdmin()) {
            return back()->with('error', 'This user cannot be deleted.');
        }

        try {
            $metadata = [
                'user_id' => $user->id,
                'email' => $user->email,
                'bots_count' => $user->bots()->count(),
                'templates_count' => $user->botTemplates()->count(),
            ];

            $user->delete();

            $this->audit->log('admin', 'user.deleted', "Deleted {$metadata['email']}.", $metadata, $request->user(), 'warning');
        } catch (\Throwable) {
            return back()->with('error', 'This user cannot be deleted.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->isAdmin() && User::where('role', 'admin')->count() <= 1;
    }

}

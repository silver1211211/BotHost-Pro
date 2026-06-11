<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogService;
use App\Services\PlanAccessService;
use App\Services\UserStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserStorageService $storage,
        private readonly PlanAccessService $planAccess,
        private readonly AuditLogService $audit,
    ) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $planSlug = strtolower((string) ($user->subscription_plan ?: 'free'));

        $bots = $user->bots()->withCount(['commands', 'botUsers'])->latest()->get();

        return view('profile.edit', [
            'user'               => $user,
            'storageUsedMb'      => $this->storage->usedMb($user),
            'storageLimitMb'     => $this->storage->limitMb($user),
            'storageRemainingMb' => $this->storage->remainingMb($user),
            'planFeatures'       => $this->planAccess->featuresForPlan($planSlug),
            'planLimits'         => $this->planAccess->limitsForPlan($planSlug),
            'subscriptionPlan'   => SubscriptionPlan::query()->where('slug', $planSlug)->first(),
            'botStats'           => [
                'total'    => $bots->count(),
                'active'   => $bots->where('status', 'running')->count(),
                'commands' => $bots->sum('commands_count'),
                'users'    => $bots->sum('bot_users_count'),
                'recent'   => $bots->take(6),
            ],
            'recentActivity' => ActivityLog::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->limit(15)
                ->get(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $this->audit->log('security', 'profile.updated', 'User profile updated.', [
            'email_changed' => $request->user()->wasChanged('email'),
        ], $request->user());

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $this->audit->log('security', 'account.deleted', 'User account deleted.', [
            'email' => $user->email,
        ], $user, 'warning', $user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

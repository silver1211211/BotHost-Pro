<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = User::query()
            ->where('username', $credentials['username'])
            ->where('role', 'admin')
            ->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            $this->audit->log('security', 'login.failed', 'Admin login failed.', [
                'email' => $credentials['username'],
                'guard' => 'admin',
            ], null, 'failed');

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        if (! $admin->isActive()) {
            $this->audit->log('security', 'login.failed', 'Inactive admin login blocked.', [
                'email' => $credentials['username'],
                'guard' => 'admin',
                'reason' => 'inactive',
            ], $admin, 'failed');

            throw ValidationException::withMessages([
                'username' => 'This admin account is not active.',
            ]);
        }

        Auth::login($admin, $request->boolean('remember'));

        $request->session()->regenerate();

        $this->audit->log('security', 'login.success', 'Admin logged in.', [
            'email' => $admin->email,
            'guard' => 'admin',
        ], $admin);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $admin = $request->user();

        $this->audit->log('security', 'logout', 'Admin logged out.', [
            'email' => $admin?->email,
            'guard' => 'admin',
        ], $admin);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

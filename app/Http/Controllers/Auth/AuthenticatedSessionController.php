<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserLog;
use App\Support\PermissionRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return $this->renderLoginView('general');
    }

    public function staff(): View
    {
        return $this->renderLoginView('staff');
    }


    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $portal = $request->string('portal')->toString();

        if (! $this->userMatchesPortal($request->user(), $portal)) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $this->portalErrorMessage($portal),
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()->hasRole('Staff')) {
            $this->startStaffLog($request);
        }

        return redirect()->intended(route(PermissionRedirect::routeNameFor($request->user()), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->closeStaffLog($request, 'logout');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function index()
    {
        return view('auth.index');
    }

    private function renderLoginView(string $portal): View
    {
        $config = [
            'general' => [
                'title' => 'Admin Login',
                'heading' => 'Admin Login',
                'submitLabel' => 'Login'
            ],
            'staff' => [
                'title' => 'Staff Login',
                'heading' => 'Staff Login',
                'submitLabel' => 'Login as Staff'
            ],
        ];

        abort_unless(isset($config[$portal]), 404);

        return view('auth.login', [
            'portal' => $portal,
            ...$config[$portal],
        ]);
    }

    private function userMatchesPortal($user, string $portal): bool
    {
        return match ($portal) {
            'staff' => $user->hasRole('Staff'),
            default => $user->hasRole('Super Admin'),
        };
    }

    private function portalErrorMessage(string $portal): string
    {
        return match ($portal) {
            'staff' => 'Only staff accounts can login from the staff login page.',
            default => 'Only super admin accounts can login from the admin login page.',
        };
    }

    private function startStaffLog(Request $request): void
    {
        $user = $request->user()->loadMissing('staffProfile');

        UserLog::expireStaleOpenLogs($user->id);

        $user->userLogs()
            ->open()
            ->update([
                'logout_at' => now(),
                'logout_reason' => 'new_login',
            ]);

        $log = $user->userLogs()->create([
            'designation_id' => $user->staffProfile?->designation_id,
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $request->session()->put('user_log_id', $log->id);
    }

    private function closeStaffLog(Request $request, string $reason): void
    {
        $user = $request->user();

        if (! $user?->hasRole('Staff')) {
            return;
        }

        $logId = $request->session()->get('user_log_id');
        $query = $user->userLogs()->open();

        if ($logId) {
            $query->whereKey($logId);
        }

        $query->update([
            'last_activity_at' => now(),
            'logout_at' => now(),
            'logout_reason' => $reason,
        ]);
    }
}

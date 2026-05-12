<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
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
            default => ! $user->hasRole('Staff'),
        };
    }

    private function portalErrorMessage(string $portal): string
    {
        return match ($portal) {
            'staff' => 'This account is not allowed on the staff login page.',
            default => 'Staff accounts must use the staff login page.',
        };
    }
}

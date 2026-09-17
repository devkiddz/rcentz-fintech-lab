<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = auth()->user();

        if (! $user->is_admin && $user->account_status === 'suspended' && $user->status_until && now()->gte($user->status_until)) {
            $user->update(['account_status' => 'active', 'status_reason' => null, 'status_until' => null]);
        }

        if (! $user->is_admin && ! $user->isAccountActive()) {
            $message = match ($user->account_status) {
                'banned' => 'This account has been banned from platform access.',
                'suspended' => 'This account is currently suspended.',
                default => 'This account is currently blocked.',
            };
            Auth::guard('web')->logout();
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => $message]);
        }

        $request->session()->regenerate();

        // Redirect admin users to admin dashboard, regular users to user dashboard
        if (auth()->user()->is_admin) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

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
}

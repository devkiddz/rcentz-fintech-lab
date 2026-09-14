<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthenticatedSessionController extends Controller
{
    /**
     * Display the dedicated administrator login screen.
     */
    public function create(): View
    {
        return view('auth.admin-login');
    }

    /**
     * Authenticate an administrator without allowing regular users through
     * the administrator entry point.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'These credentials do not have administrator access.'])
                ->onlyInput('email');
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}

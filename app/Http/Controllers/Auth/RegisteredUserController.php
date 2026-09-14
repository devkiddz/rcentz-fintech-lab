<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country' => ['nullable', 'string', 'max:2'],
            'currency' => ['nullable', 'string', 'max:3'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country' => $request->country,
            'currency' => $request->currency ?? 'USD',
        ];

        // If email verification is disabled, mark email as verified
        if (!is_email_verification_enabled()) {
            $userData['email_verified_at'] = now();
        }

        $user = User::create($userData);

        // Only fire Registered event if email verification is enabled
        if (is_email_verification_enabled()) {
            event(new Registered($user));
        }

        // Send welcome email
        Mail::to($user->email)->send(new WelcomeEmail($user));

        Auth::login($user);

        // Redirect based on email verification setting
        if (is_email_verification_enabled()) {
            return redirect(route('verification.notice'));
        } else {
            return redirect(route('dashboard'));
        }
    }
}

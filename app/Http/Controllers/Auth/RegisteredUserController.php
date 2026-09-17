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
            'age_confirmed' => ['accepted'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'in:USD,EUR,GBP,NGN,JPY,AUD,CAD,CHF,CNY,INR,ZAR,SGD'],
            'date_of_birth' => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'employment_class' => ['nullable', 'string', 'in:student,employed,self_employed,business_owner,professional,freelancer,unemployed,retired,other'],
            'education_level' => ['nullable', 'string', 'in:secondary,diploma,undergraduate,bachelor,postgraduate,masters,doctorate,professional,other'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country' => $request->country,
            'currency' => $request->currency ?? 'USD',
            'date_of_birth' => $request->date_of_birth,
            'employment_class' => $request->employment_class,
            'education_level' => $request->education_level,
            'account_status' => 'active',
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

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google OAuth provider.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google OAuth provider.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                // User exists, log them in
                Auth::login($existingUser);
                
                // Check if user has a wallet, if not redirect to wallet creation
                if (!$existingUser->wallet) {
                    return redirect()->route('wallet.index')->with('info', 'Please complete your wallet setup to continue.');
                }
                
                return redirect()->intended(route('dashboard'));
            } else {
                // Create new user
                DB::beginTransaction();
                
                try {
                    $newUser = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'email_verified_at' => now(),
                        'password' => Hash::make(Str::random(24)),
                        'is_admin' => false,
                    ]);
                    
                    // Wallet will be automatically created by UserObserver
                    DB::commit();
                    
                    // Log in the new user
                    Auth::login($newUser);
                    
                    return redirect()->route('dashboard')->with('success', 'Welcome! Your account has been created successfully.');
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error creating new user', [
                        'error' => $e->getMessage(),
                        'google_email' => $googleUser->getEmail()
                    ]);
                    throw $e;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'google_email' => $googleUser->getEmail() ?? 'unknown'
            ]);
            return redirect()->route('login')->with('error', 'Google authentication failed. Please try again.');
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasWallet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Only create wallet for non-admin users who don't have one
            if (!$user->isAdmin() && !$user->wallet) {
                $user->wallet()->create([
                    'balance' => 0.00,
                    'currency' => 'USD',
                ]);
            }
        }

        return $next($request);
    }
}

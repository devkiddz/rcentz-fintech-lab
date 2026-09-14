<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KYCMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if KYC is enabled in settings
        if (!is_kyc_enabled()) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has KYC
        $kyc = $user->kyc;

        if (!$kyc) {
            return redirect()->route('profile.kyc')
                ->with('error', 'KYC verification is required to access trading and investment features.');
        }

        // Check if KYC is approved
        if (!$kyc->isApproved()) {
            $message = 'Your KYC verification is ';
            
            if ($kyc->isPending()) {
                $message .= 'pending approval. Please wait for verification.';
            } elseif ($kyc->isRejected()) {
                $message .= 'rejected. Please update your information and resubmit.';
            } else {
                $message .= 'not approved. Please complete your verification.';
            }

            return redirect()->route('profile.kyc')
                ->with('error', $message);
        }

        return $next($request);
    }
}

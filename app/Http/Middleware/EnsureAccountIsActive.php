<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_admin) {
            return $next($request);
        }

        if ($user->account_status === 'suspended' && $user->status_until && now()->gte($user->status_until)) {
            $user->update([
                'account_status' => 'active',
                'status_reason' => null,
                'status_until' => null,
            ]);

            return $next($request);
        }

        if (($user->account_status ?? 'active') !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => match ($user->account_status) {
                    'blocked' => 'This account is currently blocked. Contact support for assistance.',
                    'banned' => 'This account has been banned from platform access.',
                    'suspended' => 'This account is suspended'.($user->status_until ? ' until '.$user->status_until->format('M j, Y H:i') : '').'.',
                    default => 'This account is not currently permitted to sign in.',
                },
            ]);
        }

        return $next($request);
    }
}

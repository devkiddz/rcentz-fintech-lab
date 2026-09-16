<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountOwnershipBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->isAdmin()) {
            $request->attributes->set('account_audit_mode', true);

            // Admin mutation authority exists on /admin/* routes where the
            // target customer is explicit. Customer account URLs never guess
            // which customer an admin intended to mutate.
            if (! in_array($request->method(), ['GET','HEAD','OPTIONS'], true)) {
                return redirect()
                    ->route('admin.investments.control.index')
                    ->with('warning', 'Use Admin Investment Control to mutate a customer-owned investment account.');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProtectProductionDemoMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! $user->isProductionDemo()
            || ! (bool) config('release.production_demo.read_only', true)
            || $request->isMethodSafe()
        ) {
            return $next($request);
        }

        abort(
            403,
            'This protected production demo account is read-only. '
            .'Use a normal customer account for financial or account mutations.'
        );
    }
}

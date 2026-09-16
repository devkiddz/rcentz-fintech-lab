<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerSurfaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $next($request);
        }

        // Admins are first-class auditors of the customer experience.
        // Safe reads are allowed so an admin can inspect exactly what customers see.
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Customer-facing mutation endpoints must not become an accidental
        // second admin control plane. Administrative edits/actions belong to
        // explicit /admin routes where they are audited and permissioned.
        return redirect()
            ->route('admin.dashboard')
            ->with('warning', 'Customer page opened in audit mode. Use the Admin control plane for edits or financial actions.');
    }
}

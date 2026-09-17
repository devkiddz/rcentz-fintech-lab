<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/install.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'wallet' => \App\Http\Middleware\EnsureUserHasWallet::class,
            'kyc' => \App\Http\Middleware\KYCMiddleware::class,
            'customer.access' => \App\Http\Middleware\CustomerSurfaceAccess::class,
            'account.owner' => \App\Http\Middleware\AccountOwnershipBoundary::class,
            'account.active' => \App\Http\Middleware\EnsureAccountIsActive::class,
            'can.impersonate' => \App\Http\Middleware\EnsureUserCanImpersonate::class,
        ]);

        // Send guests to the correct authentication surface. Admin URLs should
        // never fall back to the customer login screen.
        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('admin', 'admin/*')
                ? route('adminlogin.login')
                : route('login');
        });

        // Keep already-authenticated users out of guest-only login screens.
        $middleware->redirectUsersTo(function (Request $request): string {
            return $request->user()?->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard');
        });

        $middleware->validateCsrfTokens(except: [
            // Add routes here if you need to exclude them from CSRF protection.
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

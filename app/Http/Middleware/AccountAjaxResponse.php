<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;

/** Response adapter only: authorization, validation and financial execution stay in existing routes. */
class AccountAjaxResponse
{
    public const ROUTES = [
        'account.investments.subscribe', 'account.investments.redeem',
        'account.investments.watchlist.store', 'account.investments.watchlist.update', 'account.investments.watchlist.destroy',
        'ai-bots.subscribe', 'ai-bots.update', 'ai-bots.toggle', 'ai-bots.run', 'ai-bots.cancel',
        'copy-trading.apply.store', 'copy-trading.provider.strategy.store', 'copy-trading.provider.strategy.toggle',
        'copy-trading.follow', 'copy-trading.relationships.update', 'copy-trading.status',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!$request->expectsJson() || $request->header('X-Account-Action') !== '1'
            || $request->isMethod('GET') || !in_array($request->route()?->getName(), self::ROUTES, true)) {
            return $next($request);
        }

        // A previous page's flash message must never confirm a different submission.
        $request->session()->forget(['success', 'error', 'warning']);
        $response = $next($request);
        if (!$response instanceof RedirectResponse) {
            return $response;
        }
        $success = $request->session()->pull('success');
        $error = $request->session()->pull('error') ?? $request->session()->pull('warning');
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => (string) $error], 422)->header('Cache-Control', 'no-store');
        }
        if ($success === null) {
            return response()->json(['success' => false, 'unconfirmed' => true,
                'message' => 'The action result is unconfirmed. Check your activity before retrying.'], 409)->header('Cache-Control', 'no-store');
        }
        $target = $response->getTargetUrl();
        $safeTarget = str_starts_with($target, '/') && !str_starts_with($target, '//');
        $safeTarget = $safeTarget || (parse_url($target, PHP_URL_HOST) === $request->getHost()
            && parse_url($target, PHP_URL_SCHEME) === $request->getScheme()
            && (parse_url($target, PHP_URL_PORT) ?? ($request->isSecure() ? 443 : 80)) === $request->getPort());
        return response()->json([
            'success' => true, 'message' => (string) $success,
            'next_idempotency_key' => (string) Str::uuid(),
            // A link, not an automatic navigation: remain in the current workspace.
            'receipt_url' => $safeTarget ? $target : null,
        ])->header('Cache-Control', 'no-store');
    }
}

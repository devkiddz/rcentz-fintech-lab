<?php

$routes = __DIR__.'/../routes/web.php';
$text = file_get_contents($routes);

// Add Account Operations routes before Admin Stock Management.
$anchor = "       // Admin Stock Management";
$routesBlock = <<<'PHPBLOCK'
       // Admin Account Operations
       Route::get('/users/{user}/account-operations', [\App\Http\Controllers\Admin\AccountOperationsController::class, 'show'])
            ->name('users.account-operations');
       Route::post('/users/{user}/account-operations', [\App\Http\Controllers\Admin\AccountOperationsController::class, 'store'])
            ->name('users.account-operations.store');

PHPBLOCK;

if (! str_contains($text, "users.account-operations.store")) {
    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Admin Stock Management anchor not found.');
    }
    $text = str_replace($anchor, $routesBlock.$anchor, $text, $count);
    if ($count !== 1) throw new RuntimeException("Unexpected account route insertion count: {$count}");
}

// Existing V4.8 strategy route remains. Add direct-admin and trade-for-user POST routes before stock show.
$stockShow = "        Route::get('/{stock}', [AdminStockController::class, 'show'])->name('show');";
$extraStockRoutes = <<<'PHPBLOCK'
        Route::post('/{stock}/trade/direct', [AdminStockController::class, 'executeAdminTrade'])->name('trade.direct');
        Route::post('/{stock}/trade/user', [AdminStockController::class, 'executeUserTrade'])->name('trade.user');
PHPBLOCK;

if (! str_contains($text, "->name('trade.direct');")) {
    if (! str_contains($text, $stockShow)) {
        throw new RuntimeException('Admin stock show route anchor not found.');
    }
    $text = str_replace($stockShow, $extraStockRoutes.$stockShow, $text, $count);
    if ($count !== 1) throw new RuntimeException("Unexpected stock route insertion count: {$count}");
}

file_put_contents($routes, $text);

// Add Account Operations button to admin user header without replacing the old page.
$userView = __DIR__.'/../resources/views/admin/users/show.blade.php';
if (file_exists($userView)) {
    $u = file_get_contents($userView);
    $editAnchor = <<<'BLADE'
                <a href="{{ route('admin.users.edit', $user) }}" 
BLADE;

    if (! str_contains($u, "admin.users.account-operations")) {
        $button = <<<'BLADE'
                <a href="{{ route('admin.users.account-operations', $user) }}"
                   class="inline-flex items-center px-4 py-2 bg-muted text-foreground text-sm font-medium rounded-lg hover:bg-muted/80 transition-all duration-200">
                    Account Operations
                </a>
BLADE;
        if (str_contains($u, $editAnchor)) {
            $u = str_replace($editAnchor, $button."\n".$editAnchor, $u, $count);
            if ($count !== 1) throw new RuntimeException("Unexpected user-view insertion count: {$count}");
            file_put_contents($userView, $u);
        }
    }
}

// Customer financial history: show imported effective date while preserving actual recorded date.
$historyView = __DIR__.'/../resources/views/account/history.blade.php';
if (file_exists($historyView)) {
    $h = file_get_contents($historyView);
    $old = <<<'BLADE'
<p class="mt-1 text-xs text-muted-foreground">{{ $activity->created_at->format('M d, Y · h:i:s A') }}</p>
BLADE;
    $new = <<<'BLADE'
<p class="mt-1 text-xs text-muted-foreground">{{ $activity->created_at->format('M d, Y · h:i:s A') }}</p>@if(data_get($activity->metadata,'effective_at'))<p class="mt-1 text-[10px] text-muted-foreground">Effective {{ \Carbon\Carbon::parse(data_get($activity->metadata,'effective_at'))->format('M d, Y · h:i A') }} · recorded {{ $activity->created_at->format('M d, Y') }}</p>@endif
BLADE;
    if (! str_contains($h, 'Effective {{ \Carbon\Carbon::parse')) {
        $h = str_replace($old,$new,$h,$count);
        if ($count === 1) file_put_contents($historyView,$h);
    }
}

echo "V4.9 routes and admin/customer entry points wired.\n";

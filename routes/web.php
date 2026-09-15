<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\InvestmentPlanController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\CopyTradingController;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\StockTradePlanController;
use App\Http\Controllers\Admin\CopyTradingController as AdminCopyTradingController;
use App\Http\Controllers\Admin\TradingBotController as AdminTradingBotController;
use App\Http\Controllers\InvestmentDashboardController;
use App\Http\Controllers\KYCController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\FinancialHistoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\EmailController as AdminEmailController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\InvestmentPlanController as AdminInvestmentPlanController;
use App\Http\Controllers\Admin\InvestmentHoldingController as AdminInvestmentHoldingController;
use App\Http\Controllers\Admin\InvestmentTransactionController as AdminInvestmentTransactionController;
use App\Http\Controllers\Admin\InvestmentNavController as AdminInvestmentNavController;
use App\Http\Controllers\Admin\AutomaticNavUpdateController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\StockHoldingController as AdminStockHoldingController;
use App\Http\Controllers\Admin\StockTransactionController as AdminStockTransactionController;
use App\Http\Controllers\Admin\WalletTransactionController as AdminWalletTransactionController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\KYCController as AdminKYCController;
use Illuminate\Support\Facades\Route;

// Impersonation Routes
Route::get('/impersonate/{id}', [\Lab404\Impersonate\Controllers\ImpersonateController::class, 'take'])
    ->middleware(['auth', 'can.impersonate'])
    ->name('impersonate');
Route::get('/impersonate-leave', [\Lab404\Impersonate\Controllers\ImpersonateController::class, 'leave'])
    ->middleware(['auth'])
    ->name('impersonate.leave');

// Frontend Routes
Route::get('/', [FrontendController::class, 'index'])->name('home');

// CSRF Token Refresh Route
Route::get('/refresh-csrf', function() {
    return response()->json(['token' => csrf_token()]);
})->middleware('web');
Route::get('/cars', [FrontendController::class, 'browse'])->name('cars.browse');
Route::get('/cars/{id}', [FrontendController::class, 'show'])->name('cars.show');

// Static Pages
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::match(['GET', 'POST'], '/contact', [FrontendController::class, 'contact'])->name('contact');
Route::get('/help-center', [FrontendController::class, 'helpCenter'])->name('help-center');
Route::get('/terms', [FrontendController::class, 'terms'])->name('terms');
Route::get('/privacy', [FrontendController::class, 'privacy'])->name('privacy');

// Authenticated User Routes
Route::middleware(['auth', 'verified', 'wallet', 'block.admin'])->group(function () {
    // Checkout Routes
    Route::get('/checkout/{car_id}', [CheckoutController::class, 'checkoutForm'])->name('checkout.form');
    Route::post('/checkout/{car_id}', [CheckoutController::class, 'processCheckout'])->name('checkout.process');
    
    // Cryptocurrency Payment Routes
    Route::get('/checkout/crypto-payment/{purchase_id}', [CheckoutController::class, 'cryptoPayment'])->name('checkout.crypto-payment');
    Route::post('/checkout/crypto-payment/{purchase_id}/confirm', [CheckoutController::class, 'confirmCryptoPayment'])->name('checkout.crypto-payment.confirm');
    
    // User Dashboard Routes
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/history', [UserDashboardController::class, 'history'])->name('dashboard.history');
    Route::get('/dashboard/invoice/{purchase}', [UserDashboardController::class, 'downloadInvoice'])->name('dashboard.invoice');
    
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // KYC routes
    Route::get('/profile/kyc', [KYCController::class, 'index'])->name('profile.kyc');
    Route::post('/profile/kyc', [KYCController::class, 'store'])->name('profile.kyc.store');
    Route::patch('/profile/kyc/{kyc}', [KYCController::class, 'update'])->name('profile.kyc.update');
    Route::get('/profile/kyc/status', [KYCController::class, 'show'])->name('profile.kyc.status');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/api', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Support
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    
    // Financial history / audit trail
    Route::get('/account/history', [FinancialHistoryController::class, 'index'])->name('account.history');

    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::post('/wallet/deposit', [WalletController::class, 'processDeposit'])->name('wallet.process-deposit');
    // Crypto deposit confirmation step
    Route::get('/wallet/deposit/crypto/{transaction}', [WalletController::class, 'cryptoPayment'])->name('wallet.crypto-payment');
    Route::post('/wallet/deposit/crypto/{transaction}/confirm', [WalletController::class, 'confirmCryptoPayment'])->name('wallet.crypto-payment.confirm');
    Route::get('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
    Route::post('/wallet/withdraw', [WalletController::class, 'processWithdrawal'])->name('wallet.process-withdrawal');
    Route::get('/wallet/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');
    Route::post('/wallet/transfer', [WalletController::class, 'processTransfer'])->name('wallet.process-transfer');
    Route::get('/wallet/connections', [WalletController::class, 'connections'])->name('wallet.connections');
    Route::post('/wallet/connections', [WalletController::class, 'storeConnection'])->name('wallet.connections.store');
    Route::patch('/wallet/connections/{linkedWallet}/primary', [WalletController::class, 'setPrimaryConnection'])->name('wallet.connections.primary');
    Route::delete('/wallet/connections/{linkedWallet}', [WalletController::class, 'destroyConnection'])->name('wallet.connections.destroy');
    Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
    
    // Investment Plan Routes
    Route::get('/investments', [InvestmentPlanController::class, 'index'])->name('investments.index');
    Route::get('/investments/search', [InvestmentPlanController::class, 'search'])->name('investments.search');
    Route::get('/investments/categories', [InvestmentPlanController::class, 'categories'])->name('investments.categories');
    Route::get('/investments/featured', [InvestmentPlanController::class, 'featured'])->name('investments.featured');
    Route::get('/investments/type/{type}', [InvestmentPlanController::class, 'byType'])->name('investments.by-type');
    Route::get('/investments/category/{category}', [InvestmentPlanController::class, 'byCategory'])->name('investments.by-category');
    Route::get('/investments/{plan}', [InvestmentPlanController::class, 'show'])->name('investments.show');
    
    // Investment Trading Routes
    Route::get('/investments/{plan}/buy', [InvestmentController::class, 'buy'])->name('investments.buy');
    Route::post('/investments/{plan}/buy', [InvestmentController::class, 'executeBuy'])->name('investments.execute-buy');
    Route::get('/investments/{plan}/sell', [InvestmentController::class, 'sell'])->name('investments.sell');
    Route::post('/investments/{plan}/sell', [InvestmentController::class, 'executeSell'])->name('investments.execute-sell');
    
    // Investment Portfolio Routes
    Route::get('/portfolio', [InvestmentController::class, 'portfolio'])->name('portfolio.index');
    Route::get('/portfolio/holdings', [InvestmentController::class, 'holdings'])->name('portfolio.holdings');
    Route::get('/portfolio/transactions', [InvestmentController::class, 'transactions'])->name('portfolio.transactions');
    Route::get('/portfolio/analytics', [InvestmentController::class, 'analytics'])->name('portfolio.analytics');
    Route::get('/watchlist', [InvestmentController::class, 'watchlist'])->name('watchlist.index');
    Route::post('/watchlist/{plan}', [InvestmentController::class, 'addToWatchlist'])->name('watchlist.add');
    Route::delete('/watchlist/{plan}', [InvestmentController::class, 'removeFromWatchlist'])->name('watchlist.remove');
    
    // Stock Routes
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/search', [StockController::class, 'search'])->name('stocks.search');
    Route::get('/stocks/sector/{sector}', [StockController::class, 'bySector'])->name('stocks.by-sector');
    Route::get('/stocks/industry/{industry}', [StockController::class, 'byIndustry'])->name('stocks.by-industry');
    Route::get('/stocks/gainers', [StockController::class, 'gainers'])->name('stocks.gainers');
    Route::get('/stocks/losers', [StockController::class, 'losers'])->name('stocks.losers');
    Route::get('/stocks/most-active', [StockController::class, 'mostActive'])->name('stocks.most-active');
    Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
    
    // Stock Trading Routes
    Route::middleware(['auth', 'kyc'])->group(function () {
        Route::get('/trading/{stock}/buy', [TradingController::class, 'buy'])->name('trading.buy');
        Route::post('/trading/{stock}/buy', [TradingController::class, 'executeBuy'])->name('trading.execute-buy');
        Route::get('/trading/{stock}/sell', [TradingController::class, 'sell'])->name('trading.sell');
        Route::post('/trading/{stock}/sell', [TradingController::class, 'executeSell'])->name('trading.execute-sell');
        
        // Stock Portfolio Routes
        Route::get('/trading/portfolio', [TradingController::class, 'portfolio'])->name('trading.portfolio');
        Route::get('/trading/transactions', [TradingController::class, 'transactions'])->name('trading.transactions');
        Route::get('/trading/watchlist', [TradingController::class, 'watchlist'])->name('trading.watchlist');
        Route::post('/trading/watchlist/{stock}', [TradingController::class, 'addToWatchlist'])->name('trading.watchlist.add');
        Route::patch('/trading/watchlist/update/{stock}', [TradingController::class, 'updateWatchlist'])->name('trading.watchlist.update');
        Route::delete('/trading/watchlist/{stock}', [TradingController::class, 'removeFromWatchlist'])->name('trading.watchlist.remove');
        Route::delete('/trading/plans/{plan}', [StockTradePlanController::class, 'cancel'])->name('trading.plans.cancel');
    // Legacy /trading entry points are redirects only; V2 domains own the handlers.
    });
    
    // Legacy Trading Intelligence V1 entry points: keep old bookmarks safe.
    Route::redirect('/trading/copy', '/copy-trading')->name('trading.copy.index');
    Route::redirect('/trading/bots', '/ai-bots')->name('trading.bots.index');

    // Copy Trading product domain
    Route::middleware(['auth','kyc'])->prefix('copy-trading')->name('copy-trading.')->group(function () {
        Route::get('/', [CopyTradingController::class, 'marketplace'])->name('marketplace');
        Route::get('/my-copies', [CopyTradingController::class, 'myCopies'])->name('my-copies');
        Route::get('/executions', [CopyTradingController::class, 'executions'])->name('executions');
        Route::get('/executions/{execution}', [CopyTradingController::class, 'executionShow'])->name('executions.show');
        Route::get('/relationships/{relationship}/edit', [CopyTradingController::class, 'editRelationship'])->name('relationships.edit');
        Route::patch('/relationships/{relationship}/settings', [CopyTradingController::class, 'updateRelationship'])->name('relationships.update');
        Route::get('/provider/apply', [CopyTradingController::class, 'applyForm'])->name('apply');
        Route::post('/provider/apply', [CopyTradingController::class, 'apply'])->name('apply.store');
        Route::get('/provider/dashboard', [CopyTradingController::class, 'providerDashboard'])->name('provider.dashboard');
        Route::post('/provider/strategies', [CopyTradingController::class, 'storeStrategy'])->name('provider.strategy.store');
        Route::patch('/provider/strategies/{strategy}/toggle', [CopyTradingController::class, 'toggleStrategy'])->name('provider.strategy.toggle');
        Route::post('/strategies/{strategy}/copy', [CopyTradingController::class, 'follow'])->name('follow');
        Route::patch('/relationships/{relationship}', [CopyTradingController::class, 'status'])->name('status');
    });

    // AI Trading Bots product domain
    Route::middleware(['auth','kyc'])->prefix('ai-bots')->name('ai-bots.')->group(function () {
        Route::get('/', [TradingBotController::class, 'marketplace'])->name('marketplace');
        Route::get('/my-bots', [TradingBotController::class, 'myBots'])->name('my-bots');
        Route::get('/subscriptions', [TradingBotController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/performance', [TradingBotController::class, 'performance'])->name('performance');
        Route::get('/executions/{execution}', [TradingBotController::class, 'executionShow'])->name('executions.show');
        Route::get('/subscription/{subscription}/configure', [TradingBotController::class, 'configure'])->name('configure');
        Route::get('/{product}', [TradingBotController::class, 'show'])->name('show');
        Route::post('/{product}/subscribe', [TradingBotController::class, 'subscribe'])->name('subscribe');
        Route::patch('/subscription/{subscription}', [TradingBotController::class, 'update'])->name('update');
        Route::post('/subscription/{subscription}/toggle', [TradingBotController::class, 'toggle'])->name('toggle');
        Route::post('/subscription/{subscription}/run', [TradingBotController::class, 'run'])->name('run');
        Route::delete('/subscription/{subscription}', [TradingBotController::class, 'cancel'])->name('cancel');
    });

    // Investment Dashboard Routes
    Route::middleware(['auth', 'kyc'])->group(function () {
        Route::get('/investment', [InvestmentDashboardController::class, 'index'])->name('investment.dashboard');
        Route::get('/investment/analytics', [InvestmentDashboardController::class, 'analytics'])->name('investment.analytics');
        Route::get('/investment/transactions', [InvestmentDashboardController::class, 'transactions'])->name('investment.transactions');
    });
    
    // Automatic Investment Routes
    Route::middleware(['auth', 'kyc'])->group(function () {
        Route::get('/automatic-investments', [InvestmentDashboardController::class, 'automaticPlans'])->name('automatic-investments.index');
        Route::post('/automatic-investments', [InvestmentDashboardController::class, 'createAutomaticPlan'])->name('automatic-investments.create');
        Route::get('/automatic-investments/{plan}/edit', [InvestmentDashboardController::class, 'editAutomaticPlan'])->name('automatic-investments.edit');
        Route::patch('/automatic-investments/{plan}', [InvestmentDashboardController::class, 'updateAutomaticPlan'])->name('automatic-investments.update');
        Route::post('/automatic-investments/{plan}/toggle', [InvestmentDashboardController::class, 'toggleAutomaticPlan'])->name('automatic-investments.toggle');
        Route::delete('/automatic-investments/{plan}', [InvestmentDashboardController::class, 'deleteAutomaticPlan'])->name('automatic-investments.delete');
    });
});



// Admin Routes
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin Dashboard
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // Admin Car Management
        Route::resource('cars', AdminCarController::class);
        
        // Admin User Management
        Route::resource('users', AdminUserController::class);
        Route::post('users/{user}/fund-wallet', [AdminUserController::class, 'fundWallet'])->name('users.fund-wallet');

        // Admin About Page
        Route::get('about', function() {
            return view('admin.about');
        })->name('about');
        Route::post('users/{user}/deduct-wallet', [AdminUserController::class, 'deductWallet'])->name('users.deduct-wallet');
        
        // Admin Payment Method Management
        Route::resource('payment_methods', AdminPaymentMethodController::class);
        
        // Admin Purchase Management
        Route::get('purchases', [AdminPurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/{purchase}', [AdminPurchaseController::class, 'show'])->name('purchases.show');
        Route::patch('purchases/{purchase}/status', [AdminPurchaseController::class, 'updateStatus'])->name('purchases.update-status');
        Route::delete('purchases/{purchase}', [AdminPurchaseController::class, 'destroy'])->name('purchases.destroy');
        
        // Admin Email Management
        Route::get('emails', [AdminEmailController::class, 'index'])->name('emails.index');
        Route::get('emails/templates/create', [AdminEmailController::class, 'createTemplate'])->name('emails.templates.create');
        Route::post('emails/templates', [AdminEmailController::class, 'storeTemplate'])->name('emails.templates.store');
        Route::get('emails/templates/{template}/edit', [AdminEmailController::class, 'editTemplate'])->name('emails.templates.edit');
        Route::patch('emails/templates/{template}', [AdminEmailController::class, 'updateTemplate'])->name('emails.templates.update');
        Route::delete('emails/templates/{template}', [AdminEmailController::class, 'destroyTemplate'])->name('emails.templates.destroy');
        Route::get('emails/compose', [AdminEmailController::class, 'compose'])->name('emails.compose');
        Route::post('emails/send', [AdminEmailController::class, 'send'])->name('emails.send');
        Route::post('emails/preview', [AdminEmailController::class, 'preview'])->name('emails.preview');

        // Admin Investment Management
        Route::prefix('investments')->name('investments.')->group(function () {
            // Investment Plans
            Route::resource('plans', AdminInvestmentPlanController::class);
            
            // Investment Holdings
            Route::get('holdings', [AdminInvestmentHoldingController::class, 'index'])->name('holdings.index');
            Route::get('holdings/{holding}', [AdminInvestmentHoldingController::class, 'show'])->name('holdings.show');
            
                       // Investment Transactions
           Route::get('transactions', [AdminInvestmentTransactionController::class, 'index'])->name('transactions.index');
           Route::get('transactions/{transaction}', [AdminInvestmentTransactionController::class, 'show'])->name('transactions.show');
           
           // Investment NAV Updates
           Route::get('nav-updates', [AdminInvestmentNavController::class, 'index'])->name('nav-updates.index');
           Route::post('nav-updates/bulk-update', [AdminInvestmentNavController::class, 'bulkUpdate'])->name('nav-updates.bulk-update');
           Route::post('nav-updates/{plan}', [AdminInvestmentNavController::class, 'update'])->name('nav-updates.update');
           Route::get('nav-updates/{plan}', [AdminInvestmentNavController::class, 'show'])->name('nav-updates.show');
            
            // Automatic NAV Updates
            Route::prefix('automatic-nav-updates')->name('automatic-nav-updates.')->group(function () {
                Route::get('/', [AutomaticNavUpdateController::class, 'index'])->name('index');
                Route::get('/create', [AutomaticNavUpdateController::class, 'create'])->name('create');
                Route::post('/', [AutomaticNavUpdateController::class, 'store'])->name('store');
                Route::get('/{automaticNavUpdate}', [AutomaticNavUpdateController::class, 'show'])->name('show');
                Route::get('/{automaticNavUpdate}/edit', [AutomaticNavUpdateController::class, 'edit'])->name('edit');
                Route::put('/{automaticNavUpdate}', [AutomaticNavUpdateController::class, 'update'])->name('update');
                Route::delete('/{automaticNavUpdate}', [AutomaticNavUpdateController::class, 'destroy'])->name('destroy');
                Route::patch('/{automaticNavUpdate}/toggle', [AutomaticNavUpdateController::class, 'toggle'])->name('toggle');
                Route::post('/preview', [AutomaticNavUpdateController::class, 'preview'])->name('preview');
            });
       });

       // Admin Account Operations
       Route::get('/users/{user}/account-operations', [\App\Http\Controllers\Admin\AccountOperationsController::class, 'show'])
            ->name('users.account-operations');
       Route::post('/users/{user}/account-operations', [\App\Http\Controllers\Admin\AccountOperationsController::class, 'store'])
            ->name('users.account-operations.store');
       // Admin Stock Management
       Route::prefix('stocks')->name('stocks.')->group(function () {
        // Stocks
        Route::get('/', [AdminStockController::class, 'index'])->name('index');
        
        // Stock Holdings (move these up)
        Route::get('holdings', [AdminStockHoldingController::class, 'index'])->name('holdings.index');
        Route::get('holdings/{holding}', [AdminStockHoldingController::class, 'show'])->name('holdings.show');
        
        // Stock Transactions (move these up)
        Route::get('transactions', [AdminStockTransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/{transaction}', [AdminStockTransactionController::class, 'show'])->name('transactions.show');
        
        // Admin Strategy Trading Desk
        Route::get('/{stock}/trade', [AdminStockController::class, 'trade'])->name('trade');
        Route::post('/{stock}/trade', [AdminStockController::class, 'executeStrategyTrade'])->name('trade.execute');

        // Individual stock route (move this down)
        Route::post('/{stock}/trade/direct', [AdminStockController::class, 'executeAdminTrade'])->name('trade.direct');
        Route::post('/{stock}/trade/user', [AdminStockController::class, 'executeUserTrade'])->name('trade.user');        Route::get('/{stock}', [AdminStockController::class, 'show'])->name('show');
    });

    // Admin Wallet Transaction Management
    Route::prefix('wallet-transactions')->name('wallet-transactions.')->group(function () {
        Route::get('/', [AdminWalletTransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [AdminWalletTransactionController::class, 'show'])->name('show');
        Route::post('/{transaction}/approve', [AdminWalletTransactionController::class, 'approve'])->name('approve');
        Route::post('/{transaction}/reject', [AdminWalletTransactionController::class, 'reject'])->name('reject');
        Route::delete('/{transaction}', [AdminWalletTransactionController::class, 'destroy'])->name('destroy');
    });

    // Admin Copy Trading
    Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
        Route::get('/applications', [AdminCopyTradingController::class, 'applications'])->name('applications');
        Route::post('/applications/{application}/approve', [AdminCopyTradingController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/reject', [AdminCopyTradingController::class, 'reject'])->name('applications.reject');
        Route::get('/providers', [AdminCopyTradingController::class, 'providers'])->name('providers');
        Route::get('/providers/{provider}', [AdminCopyTradingController::class, 'providerShow'])->name('providers.show');
        Route::patch('/providers/{provider}/toggle', [AdminCopyTradingController::class, 'toggleProvider'])->name('providers.toggle');
        Route::get('/strategies', [AdminCopyTradingController::class, 'strategies'])->name('strategies');
        Route::get('/strategies/{strategy}', [AdminCopyTradingController::class, 'strategyShow'])->name('strategies.show');
        Route::patch('/strategies/{strategy}/retire', [AdminCopyTradingController::class, 'retireStrategy'])->name('strategies.retire');
        Route::delete('/strategies/{strategy}', [AdminCopyTradingController::class, 'destroyStrategy'])->name('strategies.destroy');
        Route::get('/strategies/{strategy}/edit', [AdminCopyTradingController::class, 'editStrategy'])->name('strategies.edit');
        Route::patch('/strategies/{strategy}', [AdminCopyTradingController::class, 'updateStrategy'])->name('strategies.update');
        Route::patch('/strategies/{strategy}/toggle', [AdminCopyTradingController::class, 'toggleStrategy'])->name('strategies.toggle');
    });

    // Admin AI Trading Bots
    Route::prefix('ai-bots')->name('ai-bots.')->group(function () {
        Route::get('/', [AdminTradingBotController::class, 'index'])->name('index');
        Route::get('/create', [AdminTradingBotController::class, 'create'])->name('create');
        Route::post('/', [AdminTradingBotController::class, 'store'])->name('store');
        Route::get('/subscriptions', [AdminTradingBotController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/executions', [AdminTradingBotController::class, 'executions'])->name('executions');
        Route::get('/executions/{execution}', [AdminTradingBotController::class, 'executionShow'])->name('executions.show');
        Route::get('/{botProduct}/edit', [AdminTradingBotController::class, 'edit'])->name('edit');
        Route::patch('/{botProduct}', [AdminTradingBotController::class, 'update'])->name('update');
        Route::patch('/{botProduct}/toggle', [AdminTradingBotController::class, 'toggle'])->name('toggle');
        Route::delete('/{botProduct}', [AdminTradingBotController::class, 'destroy'])->name('destroy');
    });

    // Admin Settings Management
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminSettingController::class, 'index'])->name('index');
        Route::post('/', [AdminSettingController::class, 'update'])->name('update');
        Route::post('/fix-storage', [AdminSettingController::class, 'fixStorage'])->name('fix-storage');
        Route::post('/storage-link', [AdminSettingController::class, 'storageLink'])->name('storage-link');
        Route::post('/clear-cache', [AdminSettingController::class, 'clearCache'])->name('clear-cache');
        Route::post('/clear-config', [AdminSettingController::class, 'clearConfig'])->name('clear-config');
        Route::post('/clear-views', [AdminSettingController::class, 'clearViews'])->name('clear-views');
        Route::post('/clear-routes', [AdminSettingController::class, 'clearRoutes'])->name('clear-routes');
        Route::post('/optimize-clear', [AdminSettingController::class, 'optimizeClear'])->name('optimize-clear');
        Route::post('/test-mail', [AdminSettingController::class, 'testMail'])->name('test-mail');
        Route::post('/reset-defaults', [AdminSettingController::class, 'resetToDefaults'])->name('reset-defaults');
    });
        
        // Admin Profile Management
        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('password.update');
        Route::patch('profile/preferences', [AdminProfileController::class, 'updatePreferences'])->name('preferences.update');
        Route::delete('profile', [AdminProfileController::class, 'destroy'])->name('profile.destroy');
        
        // Admin KYC Management
        Route::get('kyc', [AdminKYCController::class, 'index'])->name('kyc.index');
        Route::get('kyc/{kyc}', [AdminKYCController::class, 'show'])->name('kyc.show');
        Route::post('kyc/{kyc}/approve', [AdminKYCController::class, 'approve'])->name('kyc.approve');
        Route::post('kyc/{kyc}/reject', [AdminKYCController::class, 'reject'])->name('kyc.reject');
        Route::delete('kyc/{kyc}', [AdminKYCController::class, 'destroy'])->name('kyc.destroy');
        Route::get('kyc/status/{status}', [AdminKYCController::class, 'byStatus'])->name('kyc.by-status');
    });

require __DIR__.'/auth.php';

// Cron setup documentation is restricted to administrators.
Route::get('/cron-setup', function () {
    return view('cron-setup');
})->middleware(['auth', 'admin'])->name('cron.setup');

// Cron Job Routes - Use token for security
use App\Http\Controllers\CronController;

Route::prefix('cron')->name('cron.')->group(function () {
    Route::get('/run-all', [CronController::class, 'runAll'])->name('run-all');
    Route::get('/update-stock-quotes', [CronController::class, 'updateStockQuotes'])->name('update-stock-quotes');
    Route::get('/fetch-stock-history', [CronController::class, 'fetchStockHistory'])->name('fetch-stock-history');
    Route::get('/process-stock-news', [CronController::class, 'processStockNews'])->name('process-stock-news');
    Route::get('/process-nav-updates', [CronController::class, 'processNavUpdates'])->name('process-nav-updates');
    Route::get('/cleanup-old-data', [CronController::class, 'cleanupOldData'])->name('cleanup-old-data');
    Route::get('/status', [CronController::class, 'status'])->name('status');
});




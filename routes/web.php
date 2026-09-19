<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\InvestmentPlanController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\PrivateInvestmentMarketController;
use App\Http\Controllers\PrivateInvestmentOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\CopyTradingController;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\StockTradePlanController;
use App\Http\Controllers\Admin\CopyTradingController as AdminCopyTradingController;
use App\Http\Controllers\Admin\TradingBotController as AdminTradingBotController;
use App\Http\Controllers\InvestmentDashboardController;
use App\Http\Controllers\PrivateInvestmentAccountController;
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
use App\Http\Controllers\Admin\PrivateInvestmentAccountAdminController;
use App\Http\Controllers\Admin\PrivateInvestmentAdminController;
use App\Http\Controllers\Admin\PrivateInvestmentAccountOperationController;
use App\Http\Controllers\Admin\InvestmentHoldingController as AdminInvestmentHoldingController;
use App\Http\Controllers\Admin\InvestmentTransactionController as AdminInvestmentTransactionController;
use App\Http\Controllers\Admin\InvestmentNavController as AdminInvestmentNavController;
use App\Http\Controllers\Admin\AutomaticNavUpdateController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\StockHoldingController as AdminStockHoldingController;
use App\Http\Controllers\Admin\StockTransactionController as AdminStockTransactionController;
use App\Http\Controllers\Admin\WalletTransactionController as AdminWalletTransactionController;
use App\Http\Controllers\CustomerPreferenceController;
use App\Http\Controllers\Admin\AccountAccessController;
use App\Http\Controllers\AccountAlertController;
use App\Http\Controllers\Admin\AccountAlertAdminController;
use App\Http\Controllers\Admin\WithdrawalTokenRequestController;
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

// V5.14.3 lightweight runtime heartbeat for automatic market/chart updates.
Route::get('/market-runtime', [\App\Http\Controllers\MarketRuntimeController::class, 'snapshot'])
    ->middleware('auth')
    ->name('market.runtime');
Route::get('/cars', [FrontendController::class, 'browse'])->name('cars.browse');
Route::get('/cars/{id}', [FrontendController::class, 'show'])->name('cars.show');

// Static Pages
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::match(['GET', 'POST'], '/contact', [FrontendController::class, 'contact'])->name('contact');
Route::get('/help-center', [FrontendController::class, 'helpCenter'])->name('help-center');
Route::get('/terms', [FrontendController::class, 'terms'])->name('terms');
Route::get('/privacy', [FrontendController::class, 'privacy'])->name('privacy');

// Authenticated User Routes
Route::middleware(['auth', 'verified', 'wallet', 'customer.access', 'account.active'])->group(function () {
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
    
    // Membership workspace. Customer surface is the account's current membership overview.
    Route::prefix('memberships')->name('memberships.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MembershipController::class, 'index'])->name('index');
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // KYC routes
    Route::get('/profile/kyc', [KYCController::class, 'index'])->name('profile.kyc');
    Route::post('/profile/kyc', [KYCController::class, 'store'])->name('profile.kyc.store');
    Route::patch('/profile/kyc/{kyc}', [KYCController::class, 'update'])->name('profile.kyc.update');
    Route::get('/profile/kyc/status', [KYCController::class, 'show'])->name('profile.kyc.status');

    // Read-only MarketInstrument registry. Trading and investment products remain separate domains.
    Route::prefix('instruments')->name('instruments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MarketInstrumentController::class, 'index'])->name('index');
        Route::get('/stocks', [\App\Http\Controllers\MarketInstrumentController::class, 'stocks'])->name('stocks');
        Route::get('/stocks/{stock:symbol}', [\App\Http\Controllers\StockController::class, 'show'])->name('stocks.show');
        Route::get('/forex', [\App\Http\Controllers\MarketInstrumentController::class, 'forex'])->name('forex');
        Route::get('/forex/{symbol}', [\App\Http\Controllers\MarketInstrumentController::class, 'showForex'])->where('symbol', '[A-Za-z0-9.\\-]+')->name('forex.show');
        Route::get('/crypto', [\App\Http\Controllers\MarketInstrumentController::class, 'crypto'])->name('crypto');
        Route::get('/crypto/{symbol}', [\App\Http\Controllers\MarketInstrumentController::class, 'showCrypto'])->where('symbol', '[A-Za-z0-9.\\-]+')->name('crypto.show');
        Route::get('/{instrument}', [\App\Http\Controllers\MarketInstrumentController::class, 'show'])->name('show');
    });

    // Unified liquid-market brokerage surface. All customer financial execution
    // enters through BrokerOrder before asset-specific execution is allowed.
    Route::middleware(['kyc'])->prefix('broker')->name('broker.')->group(function () {
        Route::get('/portfolio', [\App\Http\Controllers\BrokerController::class, 'portfolio'])->name('portfolio');
        Route::get('/orders', [\App\Http\Controllers\BrokerController::class, 'orders'])->name('orders');
        Route::get('/orders/{publicId}', [\App\Http\Controllers\BrokerController::class, 'showOrder'])->name('orders.show');
        Route::get('/activity', [\App\Http\Controllers\BrokerController::class, 'activity'])->name('activity');
        Route::get('/positions', [\App\Http\Controllers\BrokerController::class, 'positions'])->name('positions');
        Route::patch('/positions/{position}/risk', [\App\Http\Controllers\BrokerController::class, 'updatePositionRisk'])->name('positions.risk');
        Route::post('/positions/{position}/close', [\App\Http\Controllers\BrokerController::class, 'closePosition'])->name('positions.close');
        Route::get('/{assetClass}/{symbol}', [\App\Http\Controllers\BrokerController::class, 'workstation'])
            ->where('assetClass', 'stock|forex|crypto')
            ->where('symbol', '[A-Za-z0-9.\\-]+')
            ->name('workstation');
        Route::post('/{assetClass}/{symbol}/orders', [\App\Http\Controllers\BrokerController::class, 'submitOrder'])
            ->where('assetClass', 'stock|forex|crypto')
            ->where('symbol', '[A-Za-z0-9.\\-]+')
            ->name('orders.submit');
    });

    // Customer Signal workspace. SignalDelivery is the access authority.
    Route::prefix('signals')->name('signals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SignalController::class, 'index'])->name('index');
        Route::get('/history', [\App\Http\Controllers\SignalController::class, 'history'])->name('history');
        Route::get('/{signal}', [\App\Http\Controllers\SignalController::class, 'show'])->name('show');
    });

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

    // V5.29.A2.5 canonical Money workspace.
    Route::get('/money', [WalletController::class, 'index'])->name('money.index');
    Route::get('/money/add', [WalletController::class, 'deposit'])->name('money.add');
    Route::post('/money/add', [WalletController::class, 'processDeposit'])->name('money.add.submit');
    Route::get('/money/add/crypto/{transaction}', [WalletController::class, 'cryptoPayment'])->name('money.add.crypto');
    Route::post('/money/add/crypto/{transaction}/confirm', [WalletController::class, 'confirmCryptoPayment'])->name('money.add.crypto.confirm');
    Route::get('/money/withdraw', [WalletController::class, 'withdraw'])->name('money.withdraw');
    Route::post('/money/withdraw/request', [WalletController::class, 'requestWithdrawalToken'])->name('money.withdraw.request');
    Route::get('/money/withdraw/{tokenRequest}/verify', [WalletController::class, 'showWithdrawalVerification'])->name('money.withdraw.verify');
    Route::post('/money/withdraw/{tokenRequest}/verify', [WalletController::class, 'verifyWithdrawalToken'])->name('money.withdraw.verify.submit');
    Route::get('/money/send', [WalletController::class, 'transfer'])->name('money.send');
    Route::post('/money/send', [WalletController::class, 'processTransfer'])->name('money.send.submit');
    Route::get('/money/activity', [WalletController::class, 'transactions'])->name('money.activity');
    Route::get('/money/connections', [WalletController::class, 'connections'])->name('money.connections');
    Route::post('/money/connections', [WalletController::class, 'storeConnection'])->name('money.connections.store');
    Route::patch('/money/connections/{linkedWallet}/primary', [WalletController::class, 'setPrimaryConnection'])->name('money.connections.primary');
    Route::delete('/money/connections/{linkedWallet}', [WalletController::class, 'destroyConnection'])->name('money.connections.destroy');

    // Legacy /wallet endpoints remain available for compatibility.
    // Wallet Routes
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::post('/wallet/deposit', [WalletController::class, 'processDeposit'])->name('wallet.process-deposit');
    // Crypto deposit confirmation step
    Route::get('/wallet/deposit/crypto/{transaction}', [WalletController::class, 'cryptoPayment'])->name('wallet.crypto-payment');
    Route::post('/wallet/deposit/crypto/{transaction}/confirm', [WalletController::class, 'confirmCryptoPayment'])->name('wallet.crypto-payment.confirm');
    Route::get('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
    Route::post('/wallet/withdraw', [WalletController::class, 'processWithdrawal'])->name('wallet.process-withdrawal');
    Route::post('/wallet/withdrawal-token', [WalletController::class, 'requestWithdrawalToken'])->name('wallet.withdrawal.token.request');
    Route::get('/wallet/withdrawal-token/{tokenRequest}', [WalletController::class, 'showWithdrawalVerification'])->name('wallet.withdrawal.token.form');
    Route::post('/wallet/withdrawal-token/{tokenRequest}', [WalletController::class, 'verifyWithdrawalToken'])->name('wallet.withdrawal.token.submit');
    Route::patch('/account/preferences/currency', [CustomerPreferenceController::class, 'updateCurrency'])->name('account.preferences.currency');
    Route::post('/account-alerts/{alert}/read', [AccountAlertController::class, 'read'])->name('account-alerts.read');
    Route::post('/account-alerts/{alert}/dismiss', [AccountAlertController::class, 'dismiss'])->name('account-alerts.dismiss');
    Route::get('/wallet/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');
    Route::post('/wallet/transfer', [WalletController::class, 'processTransfer'])->name('wallet.process-transfer');
    Route::get('/wallet/connections', [WalletController::class, 'connections'])->name('wallet.connections');
    Route::post('/wallet/connections', [WalletController::class, 'storeConnection'])->name('wallet.connections.store');
    Route::patch('/wallet/connections/{linkedWallet}/primary', [WalletController::class, 'setPrimaryConnection'])->name('wallet.connections.primary');
    Route::delete('/wallet/connections/{linkedWallet}', [WalletController::class, 'destroyConnection'])->name('wallet.connections.destroy');
    Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
    
    // V5.25.2 canonical Private Investment Market read surfaces.
    // Every listing below uses PrivateInvestmentInstrument pricing/history only.
    Route::get('/investments', [PrivateInvestmentMarketController::class, 'index'])->name('investments.index');
    Route::get('/investments/stocks', [PrivateInvestmentMarketController::class, 'stocks'])->name('investments.stocks');
    Route::get('/investments/forex', [PrivateInvestmentMarketController::class, 'forex'])->name('investments.forex');
    Route::get('/investments/crypto', [PrivateInvestmentMarketController::class, 'crypto'])->name('investments.crypto');
    Route::get('/investments/real-estate', [PrivateInvestmentMarketController::class, 'realEstate'])->name('investments.real-estate');
    Route::get('/investments/bonds', [PrivateInvestmentMarketController::class, 'bonds'])->name('investments.bonds');
    Route::get('/account/investments', [PrivateInvestmentMarketController::class, 'account'])->middleware('account.owner')->name('account.investments');
    Route::get('/account/investments/portfolio', [PrivateInvestmentMarketController::class, 'portfolio'])->middleware('account.owner')->name('account.investments.portfolio');
    Route::get('/account/investments/performance', [PrivateInvestmentMarketController::class, 'performance'])->middleware('account.owner')->name('account.investments.performance');
    Route::get('/account/investments/transactions', [PrivateInvestmentAccountController::class, 'transactions'])
        ->middleware('account.owner')
        ->name('account.investments.transactions');
    Route::get('/account/investments/watchlist', [PrivateInvestmentAccountController::class, 'watchlist'])
        ->middleware('account.owner')
        ->name('account.investments.watchlist');
    Route::post('/account/investments/{instrument}/subscribe', [PrivateInvestmentOrderController::class, 'subscribe'])
        ->middleware('account.owner')
        ->name('account.investments.subscribe');
    Route::post('/account/investments/{instrument}/redeem', [PrivateInvestmentOrderController::class, 'redeem'])
        ->middleware('account.owner')
        ->name('account.investments.redeem');
    Route::post('/account/investments/watchlist/{instrument}', [PrivateInvestmentAccountController::class, 'storeWatchlist'])
        ->middleware('account.owner')
        ->name('account.investments.watchlist.store');
    Route::patch('/account/investments/watchlist/{instrument}', [PrivateInvestmentAccountController::class, 'updateWatchlist'])
        ->middleware('account.owner')
        ->name('account.investments.watchlist.update');
    Route::delete('/account/investments/watchlist/{instrument}', [PrivateInvestmentAccountController::class, 'destroyWatchlist'])
        ->middleware('account.owner')
        ->name('account.investments.watchlist.destroy');

    // Compatibility redirects: owned state belongs under /account, not the discovery market.
    Route::get('/investments/portfolio', fn () => redirect()->route('account.investments.portfolio'))->name('investments.portfolio');
    Route::get('/investments/performance', fn () => redirect()->route('account.investments.performance'))->name('investments.performance');
    Route::get('/investments/search', [PrivateInvestmentMarketController::class, 'search'])->name('investments.search');

    // Compatibility redirects for old category URLs/bookmarks.
    Route::get('/investments/categories', fn () => redirect()->route('investments.index'))->name('investments.categories');
    Route::get('/investments/featured', fn () => redirect()->route('investments.index', ['featured'=>1]))->name('investments.featured');
    Route::get('/investments/type/{type}', [PrivateInvestmentMarketController::class, 'legacyType'])->name('investments.by-type');
    Route::get('/investments/category/{category}', [PrivateInvestmentMarketController::class, 'legacyCategory'])->name('investments.by-category');

    Route::get('/investments/{instrument:slug}', [PrivateInvestmentMarketController::class, 'show'])->name('investments.show');
    
    // V5.26.4 legacy investment compatibility routes.
    // Legacy InvestmentPlan financial mutations are disabled while the Private
    // Investment subscription/redemption engine becomes canonical.
    Route::get('/investments/{plan}/buy', function () {
        return redirect()->route('investments.index')
            ->with('warning', 'This legacy investment purchase link has moved to the Private Investment market.');
    })->name('investments.buy');
    Route::post('/investments/{plan}/buy', function () {
        return redirect()->route('investments.index')
            ->with('warning', 'Legacy InvestmentPlan purchases are disabled. No financial mutation was performed.');
    })->name('investments.execute-buy');
    Route::get('/investments/{plan}/sell', function () {
        return redirect()->route('account.investments')
            ->with('warning', 'This legacy investment redemption link has moved to your Investment Account.');
    })->name('investments.sell');
    Route::post('/investments/{plan}/sell', function () {
        return redirect()->route('account.investments')
            ->with('warning', 'Legacy InvestmentPlan redemptions are disabled. No financial mutation was performed.');
    })->name('investments.execute-sell');

    Route::get('/portfolio', fn () => redirect()->route('account.investments'))->name('portfolio.index');
    Route::get('/portfolio/holdings', fn () => redirect()->route('account.investments.portfolio'))->name('portfolio.holdings');
    Route::get('/portfolio/transactions', fn () => redirect()->route('account.investments.transactions'))->name('portfolio.transactions');
    Route::get('/portfolio/analytics', fn () => redirect()->route('account.investments.performance'))->name('portfolio.analytics');

    Route::get('/watchlist', fn () => redirect()->route('account.investments.watchlist'))->name('watchlist.index');
    Route::post('/watchlist/{plan}', function () {
        return redirect()->route('account.investments.watchlist')
            ->with('warning', 'The old InvestmentPlan watchlist has been replaced by the Private Investment watchlist.');
    })->name('watchlist.add');
    Route::delete('/watchlist/{plan}', function () {
        return redirect()->route('account.investments.watchlist')
            ->with('warning', 'The old InvestmentPlan watchlist has been replaced by the Private Investment watchlist.');
    })->name('watchlist.remove');

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
        Route::get('/trading/portfolio', fn () => redirect()->route('broker.portfolio'))->name('trading.portfolio');
        Route::get('/trading/transactions', fn () => redirect()->route('broker.activity'))->name('trading.transactions');
        Route::get('/trading/watchlist', [TradingController::class, 'watchlist'])->name('trading.watchlist');
        Route::post('/trading/watchlist/{stock}', [TradingController::class, 'addToWatchlist'])->name('trading.watchlist.add');
        Route::patch('/trading/watchlist/update/{stock}', [TradingController::class, 'updateWatchlist'])->name('trading.watchlist.update');
        Route::delete('/trading/watchlist/{stock}', [TradingController::class, 'removeFromWatchlist'])->name('trading.watchlist.remove');
        Route::delete('/trading/plans/{plan}', [StockTradePlanController::class, 'cancel'])->name('trading.plans.cancel');
        Route::get('/trading/positions', fn () => redirect()->route('broker.positions'))->name('trading.positions.index');
        Route::patch('/trading/positions/{position}/risk', [\App\Http\Controllers\TradePositionController::class, 'updateRisk'])->name('trading.positions.risk');
        Route::post('/trading/positions/{position}/close', [\App\Http\Controllers\TradePositionController::class, 'close'])->name('trading.positions.close');
        Route::post('/trading/positions/{position}/partial-close', [\App\Http\Controllers\TradePositionController::class, 'partialClose'])->name('trading.positions.partial-close');
        Route::post('/trading/positions/{position}/reenter', [\App\Http\Controllers\TradePositionController::class, 'reenter'])->name('trading.positions.reenter');
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
        

        // Read-only generic MarketInstrument registry for admin inspection.
        Route::prefix('instruments')->name('instruments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'index'])->name('index');
            Route::get('/stocks', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'stocks'])->name('stocks');
            Route::get('/stocks/{stock:symbol}', [\App\Http\Controllers\Admin\StockController::class, 'show'])->name('stocks.show');
            Route::get('/forex', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'forex'])->name('forex');
            Route::get('/forex/{symbol}', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'showForex'])->where('symbol', '[A-Za-z0-9.\\-]+')->name('forex.show');
            Route::get('/crypto', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'crypto'])->name('crypto');
            Route::get('/crypto/{symbol}', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'showCrypto'])->where('symbol', '[A-Za-z0-9.\\-]+')->name('crypto.show');
            Route::get('/{instrument}', [\App\Http\Controllers\Admin\MarketInstrumentController::class, 'show'])->name('show');
        });

        // Admin Trading Command
        Route::get('trading', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'index'])->name('trading.index');
        Route::get('trading/manual', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'manual'])->name('trading.manual');
        Route::get('trading/positions', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'positions'])->name('trading.positions');
        Route::get('trading/history', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'history'])->name('trading.history');
        Route::get('trading/marketplace', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'index'])->name('trading.marketplace');
        Route::post('trading/marketplace/mode', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'updateMode'])->name('trading.marketplace.mode');
        Route::post('trading/marketplace/drive', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'updateDrive'])->name('trading.marketplace.drive');
        Route::post('trading/marketplace/tick', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'tick'])->name('trading.marketplace.tick');
        Route::post('trading/marketplace/instruments', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'storeInstrument'])->name('trading.marketplace.instruments.store');
        Route::patch('trading/marketplace/instruments/{instrument}/price', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'resetInstrumentPrice'])->name('trading.marketplace.instruments.price');
        Route::patch('trading/marketplace/instruments/{instrument}/toggle', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'toggleInstrument'])->name('trading.marketplace.instruments.toggle');
        Route::get('trading/positions/{position}', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'show'])->name('trading.positions.show');
        // Admin Car Management
        Route::resource('cars', AdminCarController::class);
        
        // Admin User Management
        Route::resource('users', AdminUserController::class);
        Route::post('users/{user}/fund-wallet', [AdminUserController::class, 'fundWallet'])->name('users.fund-wallet');
        Route::patch('users/{user}/access', [AccountAccessController::class, 'update'])->name('users.access.update');
        Route::post('users/{user}/verify-email', [AccountAccessController::class, 'verifyEmail'])->name('users.verify-email');
        Route::get('users/{user}/alerts', [AccountAlertAdminController::class, 'index'])->name('users.alerts.index');
        Route::post('users/{user}/alerts', [AccountAlertAdminController::class, 'store'])->name('users.alerts.store');

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
            // V5.26 canonical Private Investment control plane.
            Route::get('/', [PrivateInvestmentAdminController::class, 'index'])->name('control.index');
            Route::post('/instruments', [PrivateInvestmentAdminController::class, 'storeInstrument'])->name('control.instruments.store');
            Route::get('/instruments/{instrument}', [PrivateInvestmentAdminController::class, 'show'])->name('control.show');
            Route::patch('/instruments/{instrument}', [PrivateInvestmentAdminController::class, 'updateInstrument'])->name('control.instruments.update');
            Route::patch('/instruments/{instrument}/toggle', [PrivateInvestmentAdminController::class, 'toggle'])->name('control.instruments.toggle');
            Route::post('/instruments/{instrument}/assets', [PrivateInvestmentAdminController::class, 'storeAsset'])->name('control.assets.store');
            Route::delete('/instruments/{instrument}/assets/{asset}', [PrivateInvestmentAdminController::class, 'destroyAsset'])->name('control.assets.destroy');
            Route::post('/instruments/{instrument}/valuation-events', [PrivateInvestmentAdminController::class, 'applyValuation'])->name('control.valuation.apply');
            Route::post('/instruments/{instrument}/lifecycle-events', [PrivateInvestmentAdminController::class, 'applyLifecycleEvent'])->name('control.lifecycle.apply');
            Route::get('/instruments/{instrument}/lifecycle-events/history', [PrivateInvestmentAdminController::class, 'lifecycleHistory'])->name('control.lifecycle.history');
            Route::delete('/instruments/{instrument}/valuation-events/test-history', [PrivateInvestmentAdminController::class, 'resetTestValuations'])->name('control.valuation.reset-test');
            Route::patch('/presentation', [PrivateInvestmentAdminController::class, 'updatePresentation'])->name('control.presentation.update');
            Route::post('/instruments/{instrument}/account-operations/subscribe', [PrivateInvestmentAccountOperationController::class, 'subscribe'])->name('account-operations.subscribe');
            Route::post('/instruments/{instrument}/account-operations/redeem', [PrivateInvestmentAccountOperationController::class, 'redeem'])->name('account-operations.redeem');
            Route::post('/accounts/{user}/watchlist/{instrument}', [PrivateInvestmentAccountAdminController::class, 'storeWatchlist'])->name('accounts.watchlist.store');
            Route::patch('/accounts/{user}/watchlist/{instrument}', [PrivateInvestmentAccountAdminController::class, 'updateWatchlist'])->name('accounts.watchlist.update');
            Route::delete('/accounts/{user}/watchlist/{instrument}', [PrivateInvestmentAccountAdminController::class, 'destroyWatchlist'])->name('accounts.watchlist.destroy');

            // Legacy NAV InvestmentPlan control remains available during migration.
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
    Route::prefix('withdrawal-token-requests')->name('withdrawal-token-requests.')->group(function () {
        Route::get('/', [WithdrawalTokenRequestController::class, 'index'])->name('index');
        Route::post('/{tokenRequest}/generate', [WithdrawalTokenRequestController::class, 'generate'])->name('generate');
        Route::delete('/{tokenRequest}/token', [WithdrawalTokenRequestController::class, 'destroyToken'])->name('destroy-token');
    });

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

    // Signal Engine Admin Control
    Route::prefix('signals')->name('signals.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SignalController::class, 'index'])->name('index');
        Route::get('/candidates', [\App\Http\Controllers\Admin\SignalController::class, 'candidates'])->name('candidates');
        Route::get('/live', [\App\Http\Controllers\Admin\SignalController::class, 'live'])->name('live');
        Route::get('/recipients', [\App\Http\Controllers\Admin\SignalController::class, 'recipients'])->name('recipients');
        Route::get('/history', [\App\Http\Controllers\Admin\SignalController::class, 'history'])->name('history');
        Route::get('/activity', [\App\Http\Controllers\Admin\SignalController::class, 'activity'])->name('activity');
        Route::post('/', [\App\Http\Controllers\Admin\SignalController::class, 'store'])->name('store');
        Route::get('/{signal}', [\App\Http\Controllers\Admin\SignalController::class, 'show'])->name('show');
        Route::patch('/{signal}', [\App\Http\Controllers\Admin\SignalController::class, 'update'])->name('update');
        Route::post('/{signal}/reanalyze', [\App\Http\Controllers\Admin\SignalController::class, 'reanalyze'])->name('reanalyze');
        Route::post('/{signal}/publish', [\App\Http\Controllers\Admin\SignalController::class, 'publish'])->name('publish');
        Route::post('/{signal}/distribute', [\App\Http\Controllers\Admin\SignalController::class, 'distribute'])->name('distribute');
        Route::post('/{signal}/distribute/complimentary', [\App\Http\Controllers\Admin\SignalController::class, 'complimentary'])->name('complimentary');
        Route::post('/{signal}/cancel', [\App\Http\Controllers\Admin\SignalController::class, 'cancel'])->name('cancel');
        Route::post('/{signal}/close', [\App\Http\Controllers\Admin\SignalController::class, 'close'])->name('close');
    });

    // Membership Engine Admin Control
    Route::prefix('memberships')->name('memberships.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MembershipController::class, 'index'])->name('index');
        Route::post('/types', [\App\Http\Controllers\Admin\MembershipController::class, 'storeType'])->name('types.store');
        Route::patch('/types/{type:slug}', [\App\Http\Controllers\Admin\MembershipController::class, 'updateType'])->name('types.update');
        Route::patch('/types/{type:slug}/toggle', [\App\Http\Controllers\Admin\MembershipController::class, 'toggleType'])->name('types.toggle');
        Route::delete('/types/{type:slug}', [\App\Http\Controllers\Admin\MembershipController::class, 'destroyType'])->name('types.destroy');

        Route::get('/{type:slug}', [\App\Http\Controllers\Admin\MembershipController::class, 'show'])->name('show');
        Route::post('/{type:slug}/plans', [\App\Http\Controllers\Admin\MembershipController::class, 'storePlan'])->name('plans.store');
        Route::patch('/{type:slug}/plans/{plan}', [\App\Http\Controllers\Admin\MembershipController::class, 'updatePlan'])->name('plans.update');
        Route::patch('/{type:slug}/plans/{plan}/toggle', [\App\Http\Controllers\Admin\MembershipController::class, 'togglePlan'])->name('plans.toggle');
        Route::delete('/{type:slug}/plans/{plan}', [\App\Http\Controllers\Admin\MembershipController::class, 'destroyPlan'])->name('plans.destroy');
        Route::post('/{type:slug}/plans/{plan}/entitlements', [\App\Http\Controllers\Admin\MembershipController::class, 'storeEntitlement'])->name('entitlements.store');
        Route::patch('/{type:slug}/plans/{plan}/entitlements/{entitlement}', [\App\Http\Controllers\Admin\MembershipController::class, 'updateEntitlement'])->name('entitlements.update');
        Route::delete('/{type:slug}/plans/{plan}/entitlements/{entitlement}', [\App\Http\Controllers\Admin\MembershipController::class, 'destroyEntitlement'])->name('entitlements.destroy');
        Route::get('/{type:slug}/memberships', [\App\Http\Controllers\Admin\MembershipController::class, 'registry'])->name('registry');
        Route::post('/{type:slug}/memberships', [\App\Http\Controllers\Admin\MembershipController::class, 'storeMembership'])->name('registry.store');
        Route::patch('/{type:slug}/memberships/{membership}/activate', [\App\Http\Controllers\Admin\MembershipController::class, 'activateMembership'])->name('registry.activate');
        Route::patch('/{type:slug}/memberships/{membership}/cancel', [\App\Http\Controllers\Admin\MembershipController::class, 'cancelMembership'])->name('registry.cancel');
        Route::patch('/{type:slug}/memberships/{membership}/expire', [\App\Http\Controllers\Admin\MembershipController::class, 'expireMembership'])->name('registry.expire');
    });

    // Admin Settings Control Plane
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminSettingController::class, 'index'])->name('index');

        // Core settings domains
        Route::patch('/general', [AdminSettingController::class, 'updateGeneral'])->name('general.update');
        Route::patch('/appearance', [AdminSettingController::class, 'updateAppearance'])->name('appearance.update');
        Route::patch('/security', [AdminSettingController::class, 'updateSecurity'])->name('security.update');

        // Specialized settings domains keep their own storage/service boundaries.
        Route::post('/market/source', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'updateMode'])->name('market.source');
        Route::post('/market/movement', [\App\Http\Controllers\Admin\MarketEnvironmentController::class, 'updateDrive'])->name('market.movement');
        Route::patch('/mail', [AdminSettingController::class, 'updateMail'])->name('mail.update');
        Route::post('/mail/test', [AdminSettingController::class, 'testMail'])->name('mail.test');

        // System actions
        Route::post('/system/cache/clear', [AdminSettingController::class, 'clearCache'])->name('system.cache.clear');
        Route::post('/system/config/clear', [AdminSettingController::class, 'clearConfig'])->name('system.config.clear');
        Route::post('/system/views/clear', [AdminSettingController::class, 'clearViews'])->name('system.views.clear');
        Route::post('/system/routes/clear', [AdminSettingController::class, 'clearRoutes'])->name('system.routes.clear');
        Route::post('/system/optimize/clear', [AdminSettingController::class, 'optimizeClear'])->name('system.optimize.clear');
        Route::post('/system/storage/link', [AdminSettingController::class, 'storageLink'])->name('system.storage.link');
        Route::post('/system/storage/repair', [AdminSettingController::class, 'fixStorage'])->name('system.storage.repair');
        Route::post('/system/defaults/reset', [AdminSettingController::class, 'resetToDefaults'])->name('system.defaults.reset');

        // Compatibility aliases for the previous settings surface.
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




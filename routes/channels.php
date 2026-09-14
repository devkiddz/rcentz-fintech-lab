<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Stock channels - public channels for stock updates
Broadcast::channel('stock.{symbol}', function ($user, $symbol) {
    // Allow anyone to listen to stock updates
    return true;
});

// Stock quotes channel - for general stock quote updates
Broadcast::channel('stock.quotes', function ($user) {
    // Allow anyone to listen to stock quotes
    return true;
});

// Market overview channel - for market status and overview updates
Broadcast::channel('market.overview', function ($user) {
    // Allow anyone to listen to market overview updates
    return true;
});

// Market status channel - for market status updates
Broadcast::channel('market.status', function ($user) {
    // Allow anyone to listen to market status updates
    return true;
});

// Portfolio updates channel - for user-specific portfolio updates
Broadcast::channel('portfolio.updates', function ($user) {
    // Allow authenticated users to listen to portfolio updates
    return auth()->check();
});

// User-specific portfolio channel
Broadcast::channel('portfolio.{userId}', function ($user, $userId) {
    // Allow users to listen to their own portfolio updates
    return (int) $user->id === (int) $userId;
});

// Trading channel - for trading-related updates
Broadcast::channel('trading.{userId}', function ($user, $userId) {
    // Allow users to listen to their own trading updates
    return (int) $user->id === (int) $userId;
});

// Watchlist channel - for watchlist updates
Broadcast::channel('watchlist.{userId}', function ($user, $userId) {
    // Allow users to listen to their own watchlist updates
    return (int) $user->id === (int) $userId;
});

// Admin channels - for admin-only updates
Broadcast::channel('admin.market', function ($user) {
    // Allow admin users to listen to admin market updates
    return $user && $user->hasRole('admin');
});

Broadcast::channel('admin.system', function ($user) {
    // Allow admin users to listen to system updates
    return $user && $user->hasRole('admin');
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API route for stock chart data
Route::get('/stocks/{symbol}/chart-data', function ($symbol, Request $request) {
    $period = $request->get('period', '1m');
    $controller = new \App\Http\Controllers\TradingController();
    $chartData = $controller->getStockChartData($symbol, $period);
    
    return response()->json([
        'success' => true,
        'chartData' => $chartData
    ]);
})->name('api.stocks.chart-data');

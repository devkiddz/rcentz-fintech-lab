<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ControlledMarketInstrument;
use App\Models\MarketEnvironment;
use App\Models\Stock;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Services\ControlledMarketEngine;
use App\Services\LiveMarketHealthService;
use App\Services\MarketPriceRouter;
use App\Services\StockDataService;
use Illuminate\Http\Request;

class MarketEnvironmentController extends Controller
{
    public function index(
        MarketPriceRouter $prices,
        LiveMarketHealthService $health,
        StockDataService $stockData
    ) {
        $environment = MarketEnvironment::current();
        $instruments = ControlledMarketInstrument::query()
            ->with('stock')
            ->orderBy('symbol')
            ->paginate(30);

        $liveHealth = $health->snapshot($stockData);
        $exposure = [
            'live_positions' => TradePosition::query()->where('marketplace', 'live')->whereIn('status', ['open', 'exit_queued'])->where('open_quantity', '>', 0)->count(),
            'controlled_positions' => TradePosition::query()->where('marketplace', 'controlled')->whereIn('status', ['open', 'exit_queued'])->where('open_quantity', '>', 0)->count(),
            'live_holdings' => StockHolding::query()->where('marketplace', 'live')->where('quantity', '>', 0)->count(),
            'controlled_holdings' => StockHolding::query()->where('marketplace', 'controlled')->where('quantity', '>', 0)->count(),
        ];

        return view('admin.trading.marketplace', compact(
            'environment',
            'instruments',
            'liveHealth',
            'exposure'
        ));
    }

    public function updateMode(Request $request, MarketPriceRouter $prices)
    {
        $data = $request->validate([
            'active_marketplace' => 'required|in:live,controlled',
        ]);

        $requested = $data['active_marketplace'];

        // V5.12: holdings and living contracts are marketplace-scoped, so the
        // operator can change the active desk without re-pricing existing exposure.
        $prices->setActiveMarketplace($requested, auth()->id());

        return back()->with('success', 'Price source updated. New browsing and executions now use the selected source. Existing exposure remains bound to its opening source.');
    }

    public function updateDrive(Request $request)
    {
        $data = $request->validate([
            'controlled_drive_mode' => 'required|in:up,down,range',
            'controlled_drive_strength' => 'required|numeric|min:0.1|max:3',
            'controlled_tick_seconds' => 'required|integer|in:5,10,15,30,60,120,300',
        ]);

        MarketEnvironment::current()->update([
            'controlled_drive_mode' => $data['controlled_drive_mode'],
            'controlled_drive_strength' => $data['controlled_drive_strength'],
            'controlled_tick_seconds' => $data['controlled_tick_seconds'],
            'updated_by_user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Market drive updated. Automatic movement interval: '.$data['controlled_tick_seconds'].' seconds.');
    }

    public function tick(ControlledMarketEngine $engine)
    {
        $result = $engine->tickAll();

        return back()->with('success', 'Market tick completed: '.$result['updated'].' updated, '.$result['failed'].' failed.');
    }

    public function storeInstrument(Request $request, ControlledMarketEngine $engine)
    {
        $data = $request->validate([
            'symbol' => ['required','string','max:20','regex:/^[A-Za-z0-9.\-]+$/'],
            'label' => 'required|string|max:120',
            'current_price' => 'required|numeric|min:0.000001',
        ]);

        $symbol = strtoupper($data['symbol']);
        $price = (float) $data['current_price'];

        // A newly-created internal instrument is not automatically a real
        // external-feed symbol. Existing public stocks keep their feed eligibility.
        $stock = Stock::query()->firstOrNew(['symbol' => $symbol]);

        if (! $stock->exists) {
            $stock->fill([
                'company_name' => $data['label'],
                'current_price' => $price,
                'previous_close' => $price,
                'change_amount' => 0,
                'change_percentage' => 0,
                'high' => $price,
                'low' => $price,
                'open' => $price,
                'is_active' => true,
                'is_featured' => false,
                'external_feed_enabled' => false,
                'last_updated' => now(),
            ]);
            $stock->save();
        }

        if (ControlledMarketInstrument::query()->where('stock_id', $stock->id)->exists()) {
            return back()->withErrors(['symbol' => 'This instrument already exists.'])->withInput();
        }

        $engine->registerInstrument($stock, $data['label'], $price);

        return back()->with('success', $symbol.' added.');
    }

    public function resetInstrumentPrice(
        Request $request,
        ControlledMarketInstrument $instrument,
        ControlledMarketEngine $engine
    ) {
        $data = $request->validate([
            'current_price' => 'required|numeric|min:0.000001',
        ]);

        $engine->resetPrice($instrument, (float) $data['current_price']);

        return back()->with('success', $instrument->symbol.' price reset.');
    }

    public function toggleInstrument(ControlledMarketInstrument $instrument)
    {
        $instrument->update(['is_active' => ! $instrument->is_active]);

        return back()->with('success', $instrument->symbol.' is now '.($instrument->is_active ? 'active' : 'paused').'.');
    }
}

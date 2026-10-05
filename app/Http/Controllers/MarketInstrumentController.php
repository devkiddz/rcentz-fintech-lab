<?php

namespace App\Http\Controllers;

use App\Models\MarketInstrument;
use App\Services\MarketInstrumentAnalysisService;
use App\Services\MarketInstrumentCatalogService;
use App\Services\MarketPriceRouter;

class MarketInstrumentController extends Controller
{
    public function index(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, null, 'Market Instruments', 'Stocks, Forex, Commodities and Crypto registered with the market intelligence layer.'); }
    public function stocks(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'stock', 'Stock Instruments', 'Listed stocks connected to the shared MarketInstrument runtime.'); }
    public function forex(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'forex', 'Forex Instruments', 'Registered currency pairs connected to the shared market runtime.'); }
    public function crypto(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'crypto', 'Crypto Instruments', '24/7 crypto markets connected to the shared MarketInstrument runtime when their real feed is ready.'); }
    public function commodities(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'commodity', 'Commodity Instruments', 'Commodity market identities connected to the shared MarketInstrument data and analysis runtime.'); }

    /**
     * Compatibility endpoint for the former /instruments/{id} detail URL.
     * Canonical market detail URLs now carry asset class + symbol.
     */
    public function show(MarketInstrument $instrument)
    {
        abort_unless($instrument->is_active, 404);
        return redirect()->route('broker.workstation', ['assetClass'=>$instrument->asset_class,'symbol'=>$instrument->symbol]);
    }

    public function showStock(\App\Models\Stock $stock)
    {
        $instrument=$stock->marketInstrument ?: MarketInstrument::where('stock_id',$stock->id)->firstOrFail();
        abort_unless($instrument->is_active,404);
        return redirect()->route('broker.workstation',['assetClass'=>'stock','symbol'=>$instrument->symbol]);
    }

    public function showForex(
        string $symbol,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices
    ) {
        return $this->showAsset('forex', $symbol, $analysisService, $prices);
    }

    public function showCrypto(
        string $symbol,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices
    ) {
        return $this->showAsset('crypto', $symbol, $analysisService, $prices);
    }

    public function showCommodity(
        string $symbol,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices
    ) {
        return $this->showAsset('commodity', $symbol, $analysisService, $prices);
    }

    private function showAsset(
        string $assetClass,
        string $symbol,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices
    ) {
        $instrument = MarketInstrument::query()
            ->where('asset_class', strtolower($assetClass))
            ->where('symbol', strtoupper(trim($symbol)))
            ->where('is_active', true)
            ->firstOrFail();

        return redirect()->route('broker.workstation', ['assetClass'=>$instrument->asset_class,'symbol'=>$instrument->symbol]);
    }

    private function safeAnalysis(
        MarketInstrument $instrument,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices,
        string $marketplace
    ): array {
        try {
            return $analysisService->forInstrument($instrument, $marketplace);
        } catch (\Throwable $e) {
            try {
                $price = $prices->price($instrument, $marketplace);
            } catch (\Throwable) {
                $price = 0.0;
            }

            return [
                'market_instrument_id' => $instrument->id,
                'asset_class' => $instrument->asset_class,
                'symbol' => $instrument->symbol,
                'display_symbol' => $instrument->display_symbol,
                'label' => $instrument->name,
                'marketplace' => $marketplace,
                'price_precision' => (int) $instrument->price_precision,
                'quote_asset' => $instrument->quote_asset,
                'source' => 'adapter_pending',
                'series' => [],
                'timeframes' => [],
                'current_price' => $price,
                'previous_close' => $price,
                'has_chart' => false,
                'trend' => 'Unavailable',
                'momentum_percent' => 0,
                'momentum_label' => 'Unavailable',
                'support' => null,
                'resistance' => null,
                'sma20' => null,
                'sma50' => null,
                'sma200' => null,
                'risk_reward' => 'Unavailable',
                'analysis_error' => $e->getMessage(),
            ];
        }
    }

    private function render(MarketInstrumentCatalogService $catalog, ?string $scope, string $title, string $description)
    {
        return view('market-instruments.index', [
            ...$catalog->build($scope, true),
            'scope' => $scope,
            'title' => $title,
            'description' => $description,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketInstrument;
use App\Services\MarketInstrumentAnalysisService;
use App\Services\MarketInstrumentCatalogService;
use App\Services\MarketPriceRouter;

class MarketInstrumentController extends Controller
{
    public function index(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, null, 'Instrument Registry', 'Parent market identities feeding the shared price, chart and Signal intelligence runtime.'); }
    public function stocks(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'stock', 'Stock Instruments', 'Listed stocks connected to the shared MarketInstrument runtime while rich Stock detail and execution remain available per asset.'); }
    public function forex(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'forex', 'Forex Instruments', 'Registered FX pairs connected to the shared market runtime.'); }
    public function crypto(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'crypto', 'Crypto Instruments', '24/7 crypto markets connected to the shared MarketInstrument runtime when their real feed is ready.'); }
    public function commodities(MarketInstrumentCatalogService $catalog) { return $this->render($catalog, 'commodity', 'Commodity Instruments', 'Commodity market identities connected to the shared MarketInstrument data and analysis runtime.'); }

    /**
     * Compatibility endpoint for the former /admin/instruments/{id} detail URL.
     * Canonical market detail URLs now carry asset class + symbol.
     */
    public function show(MarketInstrument $instrument)
    {
        $instrument->loadMissing(['canonicalStock', 'stock']);

        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?: $instrument->stock;
            if ($stock) {
                return redirect()->route('admin.instruments.stocks.show', ['stock' => $stock->symbol]);
            }
        }

        if ($instrument->isForex()) {
            return redirect()->route('admin.instruments.forex.show', ['symbol' => $instrument->symbol]);
        }

        if ($instrument->asset_class === 'crypto') {
            return redirect()->route('admin.instruments.crypto.show', ['symbol' => $instrument->symbol]);
        }

        if ($instrument->isCommodity()) {
            return redirect()->route('admin.instruments.commodities.show', ['symbol' => $instrument->symbol]);
        }

        return redirect()->route('admin.instruments.index');
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
            ->firstOrFail();

        $instrument->load([
            'stock',
            'forexPair',
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'canonicalCommodityInstrument',
            'controlledMarketInstrument',
        ]);

        $marketplace = $prices->activeMarketplace();
        $analysis = $this->safeAnalysis($instrument, $analysisService, $prices, $marketplace);

        return view('admin.market-instruments.show', compact('instrument', 'analysis', 'marketplace'));
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
        return view('admin.market-instruments.index', [
            ...$catalog->build($scope, false),
            'scope' => $scope,
            'title' => $title,
            'description' => $description,
        ]);
    }
}

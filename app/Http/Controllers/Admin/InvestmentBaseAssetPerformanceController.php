<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateMarketReference;
use App\Models\PublicInvestmentBaseAsset;
use App\Services\InvestmentBaseAssetPerformanceService;

class InvestmentBaseAssetPerformanceController extends Controller
{
    public function private(
        PrivateMarketReference $reference,
        InvestmentBaseAssetPerformanceService $performance
    ) {
        $reference->loadCount('assets');

        return view(
            'admin.investment-base-assets.performance',
            [
                'kind' => 'private',
                'title' => $reference->name,
                'symbol' => $reference->symbol,
                'category' => $reference->category,
                'location' => $reference->location,
                'currency' => $reference->currency,
                'unit' => $reference->reference_unit,
                'status' => $reference->status,
                'usageCount' => (int) $reference->assets_count,
                'analysis' => $performance->forPrivate($reference),
                'backRoute' => route(
                    'admin.investments.instruments.private.base-assets.index'
                ),
                'manageRoute' => route(
                    'admin.investments.instruments.private.base-assets.show',
                    $reference
                ),
                'manageLabel' => 'Manage Base Asset',
                'runtimeRoute' => route(
                    'admin.investments.instruments.private.base-assets.performance.runtime',
                    $reference
                ),
            ]
        );
    }

    public function public(
        PublicInvestmentBaseAsset $baseAsset,
        InvestmentBaseAssetPerformanceService $performance
    ) {
        $baseAsset->load('marketInstrument');
        $baseAsset->loadCount('reserveAssets');

        abort_unless($baseAsset->marketInstrument, 404);

        $market = $baseAsset->marketInstrument;

        return view(
            'admin.investment-base-assets.performance',
            [
                'kind' => 'public',
                'title' => $market->name,
                'symbol' => $market->display_symbol ?: $market->symbol,
                'category' => $market->asset_class,
                'location' => $market->market,
                'currency' => $market->quote_asset ?: 'USD',
                'unit' => match ($market->asset_class) {
                    'stock' => 'share',
                    default => $market->base_asset ?: 'unit',
                },
                'status' => $baseAsset->status,
                'usageCount' => (int) $baseAsset->reserve_assets_count,
                'analysis' => $performance->forPublic($baseAsset),
                'backRoute' => route(
                    'admin.investments.instruments.public.base-assets.index'
                ),
                'manageRoute' => route(
                    'admin.investments.instruments.public.base-assets.show',
                    $baseAsset
                ),
                'manageLabel' => 'Manage Base Asset',
                'runtimeRoute' => null,
            ]
        );
    }
}

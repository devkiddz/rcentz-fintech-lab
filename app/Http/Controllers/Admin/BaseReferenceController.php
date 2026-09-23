<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketInstrument;
use App\Models\PrivateMarketReference;
use App\Services\PrivateInvestmentReserveMarketService;
use App\Services\PublicBaseReferenceRegistryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BaseReferenceController extends Controller
{
    public function index(
        Request $request,
        PrivateInvestmentReserveMarketService $publicMarket
    ) {
        $publicReferences = MarketInstrument::query()
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get()
            ->map(function (MarketInstrument $instrument) use ($publicMarket) {
                try {
                    $price = $publicMarket->currentPrice($instrument);
                } catch (\Throwable) {
                    $price = null;
                }

                try {
                    $unit = $publicMarket->unitLabel($instrument);
                } catch (\Throwable) {
                    $unit = 'units';
                }

                return [
                    'id' => $instrument->id,
                    'symbol' => $instrument->symbol,
                    'display_symbol' => $instrument->display_symbol ?: $instrument->symbol,
                    'name' => $instrument->name,
                    'asset_class' => $instrument->asset_class,
                    'market' => $instrument->market,
                    'quote_asset' => $instrument->quote_asset,
                    'precision' => max(0, min(8, (int) $instrument->price_precision)),
                    'price' => $price,
                    'unit' => $unit,
                    'is_active' => (bool) $instrument->is_active,
                ];
            });

        $privateReferences = PrivateMarketReference::query()
            ->withCount('assets')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $stats = [
            'public' => $publicReferences->count(),
            'public_active' => $publicReferences->where('is_active', true)->count(),
            'private' => $privateReferences->count(),
            'private_active' => $privateReferences->where('status', 'active')->count(),
        ];

        return view('admin.base-references.index', compact(
            'publicReferences',
            'privateReferences',
            'stats'
        ));
    }

    public function storePublic(
        Request $request,
        PublicBaseReferenceRegistryService $registry
    ) {
        $data = $request->validate([
            'public_asset_class' => ['required', 'in:stock,forex,crypto,commodity'],
            'public_symbol' => ['required', 'string', 'max:32'],
        ]);

        try {
            $result = $registry->register(
                $data['public_asset_class'],
                $data['public_symbol']
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['public_symbol' => $e->getMessage()])
                ->withInput();
        }

        /** @var MarketInstrument $instrument */
        $instrument = $result['instrument'];

        return redirect()
            ->route('admin.investments.instruments.base-assets.index', ['scope' => 'public'])
            ->with(
                'success',
                ($result['created'] ? 'Public base reference added: ' : 'Public base reference already registered: ')
                .$instrument->display_symbol.' · '.$instrument->name.'.'
            );
    }
}

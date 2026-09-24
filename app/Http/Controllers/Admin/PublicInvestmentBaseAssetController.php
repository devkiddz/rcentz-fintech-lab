<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketInstrument;
use App\Models\PublicInvestmentBaseAsset;
use App\Services\PrivateInvestmentReserveMarketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicInvestmentBaseAssetController extends Controller
{
    public function index(PrivateInvestmentReserveMarketService $markets)
    {
        $baseAssets = PublicInvestmentBaseAsset::query()
            ->with('marketInstrument')
            ->withCount('reserveAssets')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get()
            ->map(function (PublicInvestmentBaseAsset $baseAsset) use ($markets) {
                $instrument = $baseAsset->marketInstrument;
                $price = null;
                $unit = 'units';

                if ($instrument) {
                    try {
                        $price = $markets->currentPrice($instrument);
                    } catch (\Throwable) {
                        $price = null;
                    }

                    try {
                        $unit = $markets->unitLabel($instrument);
                    } catch (\Throwable) {
                        $unit = 'units';
                    }
                }

                $baseAsset->setAttribute('resolved_price', $price);
                $baseAsset->setAttribute('resolved_unit', $unit);

                return $baseAsset;
            });

        $designatedIds = PublicInvestmentBaseAsset::query()
            ->pluck('market_instrument_id');

        $candidates = MarketInstrument::query()
            ->active()
            ->whereNotIn('id', $designatedIds)
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get();

        return view(
            'admin.investment-base-assets.public.index',
            compact('baseAssets', 'candidates')
        );
    }

    public function show(
        PublicInvestmentBaseAsset $baseAsset,
        PrivateInvestmentReserveMarketService $markets
    ) {
        $baseAsset->load([
            'marketInstrument',
            'reserveAssets.instrument',
        ]);
        $baseAsset->loadCount('reserveAssets');

        abort_unless($baseAsset->marketInstrument, 404);

        $instrument = $baseAsset->marketInstrument;

        try {
            $livePrice = $markets->currentPrice($instrument);
        } catch (\Throwable) {
            $livePrice = null;
        }

        try {
            $unit = $markets->unitLabel($instrument);
        } catch (\Throwable) {
            $unit = 'units';
        }

        $designatedIds = PublicInvestmentBaseAsset::query()
            ->whereKeyNot($baseAsset->id)
            ->pluck('market_instrument_id');

        $candidates = MarketInstrument::query()
            ->active()
            ->whereNotIn('id', $designatedIds)
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get();

        return view(
            'admin.investment-base-assets.public.show',
            compact(
                'baseAsset',
                'instrument',
                'livePrice',
                'unit',
                'candidates'
            )
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'market_instrument_id' => [
                'required',
                'integer',
                'exists:market_instruments,id',
                Rule::unique(
                    'public_investment_base_assets',
                    'market_instrument_id'
                ),
            ],
        ]);

        $instrument = $this->activeMarketInstrument(
            (int) $data['market_instrument_id']
        );

        PublicInvestmentBaseAsset::query()->create([
            'market_instrument_id' => $instrument->id,
            'status' => 'active',
            'created_by_user_id' => auth()->id(),
            'metadata' => [
                'source' => 'admin_public_base_asset_creation',
            ],
        ]);

        return back()->with(
            'success',
            ($instrument->display_symbol ?: $instrument->symbol)
            .' created as a Public Investment Base Asset.'
        );
    }

    public function update(
        Request $request,
        PublicInvestmentBaseAsset $baseAsset
    ) {
        $data = $request->validate([
            'market_instrument_id' => [
                'required',
                'integer',
                'exists:market_instruments,id',
                Rule::unique(
                    'public_investment_base_assets',
                    'market_instrument_id'
                )->ignore($baseAsset->id),
            ],
        ]);

        $nextId = (int) $data['market_instrument_id'];
        $currentId = (int) $baseAsset->market_instrument_id;
        $linked = $baseAsset->reserveAssets()->exists();

        if ($linked && $nextId !== $currentId) {
            throw ValidationException::withMessages([
                'market_instrument_id' =>
                    'The underlying Market Instrument is locked because this Public Base Asset is already used by an investment reserve.',
            ]);
        }

        $instrument = $this->activeMarketInstrument($nextId);

        if ($nextId !== $currentId) {
            $metadata = is_array($baseAsset->metadata)
                ? $baseAsset->metadata
                : [];

            $metadata['last_reassigned_by_user_id'] = auth()->id();
            $metadata['last_reassigned_at'] = now()->toIso8601String();
            $metadata['previous_market_instrument_id'] = $currentId;

            $baseAsset->update([
                'market_instrument_id' => $instrument->id,
                'metadata' => $metadata,
            ]);
        }

        return back()->with(
            'success',
            'Public Base Asset updated.'
        );
    }

    public function toggleStatus(PublicInvestmentBaseAsset $baseAsset)
    {
        $baseAsset->load('marketInstrument');

        if (
            $baseAsset->status !== 'active'
            && (! $baseAsset->marketInstrument
                || ! $baseAsset->marketInstrument->is_active)
        ) {
            throw ValidationException::withMessages([
                'status' =>
                    'The underlying Market Instrument must be active before this Public Base Asset can be activated.',
            ]);
        }

        $linked = $baseAsset->reserveAssets()->exists();

        $baseAsset->update([
            'status' => $baseAsset->status === 'active'
                ? 'paused'
                : 'active',
        ]);

        return back()->with(
            'success',
            $baseAsset->status === 'active'
                ? 'Public Base Asset activated.'
                : (
                    $linked
                        ? 'Public Base Asset paused for new selection. Existing linked reserves remain intact and continue using the underlying market authority.'
                        : 'Public Base Asset paused.'
                )
        );
    }

    public function destroy(PublicInvestmentBaseAsset $baseAsset)
    {
        if ($baseAsset->reserveAssets()->exists()) {
            throw ValidationException::withMessages([
                'base_asset' =>
                    'This Public Base Asset cannot be removed while investment reserves are linked to it.',
            ]);
        }

        $baseAsset->load('marketInstrument');
        $symbol = $baseAsset->marketInstrument?->display_symbol
            ?: $baseAsset->marketInstrument?->symbol
            ?: 'Public Base Asset';

        $baseAsset->delete();

        return redirect()
            ->route(
                'admin.investments.instruments.public.base-assets.index'
            )
            ->with(
                'success',
                $symbol.' removed from the Investment Base Asset registry. The Market Instrument itself was not deleted.'
            );
    }

    private function activeMarketInstrument(int $id): MarketInstrument
    {
        $instrument = MarketInstrument::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->first();

        if (! $instrument) {
            throw ValidationException::withMessages([
                'market_instrument_id' =>
                    'Select an active public Market Instrument.',
            ]);
        }

        return $instrument;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketInstrument;
use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use App\Models\PrivateInvestmentReserveEvent;
use App\Models\PrivateInvestmentTransaction;
use App\Models\Setting;
use App\Services\PrivateInvestmentValuationService;
use App\Services\PrivateInvestmentReserveService;
use App\Services\PrivateInvestmentChartService;
use App\Services\PrivateInvestmentLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrivateInvestmentAdminController extends Controller
{
    public function index(Request $request)
    {
        $category = in_array((string) $request->query('category'), ['stock_market', 'forex', 'cryptocurrency', 'real_estate', 'bonds', 'hedge_assets'], true)
            ? (string) $request->query('category')
            : null;

        $query = PrivateInvestmentInstrument::query();

        if ($category) {
            $query->where('category', $category);
        }

        $instruments = $query
            ->with([
                'assets' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('is_reserve_backing', true)
                    ->with(['marketInstrument', 'privateMarketReference'])
                    ->orderByDesc('current_valuation'),
                'prices' => fn ($query) => $query
                    ->where('timeframe', '1d')
                    ->orderBy('recorded_at'),
            ])
            ->withSum([
                'assets as active_reserve_value' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('is_reserve_backing', true),
            ], 'current_valuation')
            ->withCount([
                'assets',
                'assets as active_reserve_assets_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('is_reserve_backing', true),
                'events',
                'holdings',
                'transactions',
                'holdings as active_holdings_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('units', '>', 0),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(20);

        $stats = [
            'instruments' => PrivateInvestmentInstrument::count(),
            'active' => PrivateInvestmentInstrument::where('status','active')->count(),
            'assets' => PrivateInvestmentAsset::where('status','active')->count(),
            'holdings' => PrivateInvestmentHolding::where('status','active')->count(),
            'transactions' => PrivateInvestmentTransaction::count(),
            'underlying_valuation' => (float) PrivateInvestmentAsset::where('status','active')->sum('current_valuation'),
        ];

        $presentation = [
            'title' => Setting::get('investment_pricing_authority_title', 'Pricing Authority'),
            'message' => Setting::get(
                'investment_pricing_authority_message',
                'Investment prices shown here are recorded by the private Investment Engine. They are not direct live-market quotes.'
            ),
        ];

        return view('admin.private-investments.index', compact('instruments','stats','presentation'));
    }

    public function storeInstrument(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|max:255',
            'symbol'=>'required|string|max:24|unique:private_investment_instruments,symbol',
            'category'=>'required|in:stock_market,forex,cryptocurrency,real_estate,bonds,hedge_assets',
            'description'=>'nullable|string|max:2000',
            'risk_level'=>'required|in:low,medium,high,very_high',
            'opening_price'=>'required|numeric|min:0.000001',
            'minimum_investment'=>'required|numeric|min:0',
            'maximum_investment'=>'nullable|numeric|min:0|gte:minimum_investment',
            'management_fee_percent'=>'required|numeric|min:0|max:100',
            'lock_period_days'=>'required|integer|min:0|max:3650',
            'duration_days'=>'required|integer|min:1|max:3650',
            'return_interval_days'=>'required|integer|min:1|max:3650|lte:duration_days',
            'projected_return_min_percent'=>'required|numeric|min:0|max:100',
            'projected_return_max_percent'=>'required|numeric|min:0|max:100|gte:projected_return_min_percent',
            'subscription_fee_percent'=>'required|numeric|min:0|max:100',
            'redemption_fee_percent'=>'required|numeric|min:0|max:100',
        ]);

        $instrument = PrivateInvestmentInstrument::create([
            ...$data,
            'slug'=>Str::slug($data['name']).'-'.strtolower($data['symbol']),
            'currency'=>'USD',
            'status'=>'paused',
            'current_price'=>$data['opening_price'],
            'previous_price'=>$data['opening_price'],
            'unit_supply'=>0,
            'available_units'=>0,
            'is_featured'=>$request->boolean('is_featured'),
            'is_visible'=>$request->boolean('is_visible', true),
            'last_valued_at'=>now(),
        ]);

        PrivateInvestmentPrice::create([
            'instrument_id'=>$instrument->id,
            'timeframe'=>'event',
            'open'=>$instrument->opening_price,
            'high'=>$instrument->opening_price,
            'low'=>$instrument->opening_price,
            'close'=>$instrument->opening_price,
            'change_amount'=>0,
            'change_percent'=>0,
            'source'=>'admin_opening_price',
            'recorded_at'=>now(),
        ]);

        return redirect()
            ->route('admin.investments.control.show',$instrument)
            ->with(
                'success',
                'Investment created paused with zero sellable capacity. Add verified reserve backing before activation.'
            );
    }

    public function preview(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentChartService $charts,
        PrivateInvestmentReserveService $reserves
    ) {
        $instrument->load([
            'assets' => fn ($query) => $query
                ->where('status', 'active')
                ->with(['marketInstrument', 'publicBaseAsset', 'privateMarketReference'])
                ->orderByDesc('current_valuation'),
            'events' => fn ($query) => $query
                ->where('approval_state', 'approved')
                ->latest('effective_at')
                ->limit(12),
        ]);

        $analysis = $charts->forInstrument($instrument);
        $reserveSummary = $reserves->summary($instrument);

        return view('admin.private-investments.preview', compact(
            'instrument',
            'analysis',
            'reserveSummary'
        ));
    }
    public function show(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentReserveService $reserves
    )
    {
        $instrument->load([
            'assets'=>fn($q)=>$q->with(['marketInstrument', 'publicBaseAsset', 'privateMarketReference'])->latest('created_at'),
            'events'=>fn($q)=>$q->latest('effective_at')->limit(25),
            'prices'=>fn($q)=>$q->latest('recorded_at')->limit(30),
            'lifecycleEvents'=>fn($q)=>$q->latest('effective_at')->limit(5),
            'reserveEvents'=>fn($q)=>$q->latest('effective_at')->limit(25),
        ]);

        $customers = \App\Models\User::query()
            ->where('is_admin', false)
            ->where('is_production_demo', false)
            ->with('wallet')
            ->orderBy('name')
            ->get();

        $reserveSummary = $reserves->summary($instrument);

        $publicBaseAssets = \App\Models\PublicInvestmentBaseAsset::query()
            ->with('marketInstrument')
            ->where('status', 'active')
            ->whereHas('marketInstrument', fn ($query) =>
                $query->where('is_active', true)
            )
            ->orderBy('id')
            ->get();

        $privateBaseAssets = \App\Models\PrivateMarketReference::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'admin.private-investments.show',
            compact(
                'instrument',
                'customers',
                'reserveSummary',
                'publicBaseAssets',
                'privateBaseAssets'
            )
        );
    }

    public function updateInstrument(Request $request, PrivateInvestmentInstrument $instrument)
    {
        $data=$request->validate([
            'name'=>'required|string|max:255',
            'category'=>'required|in:stock_market,forex,cryptocurrency,real_estate,bonds,hedge_assets',
            'description'=>'nullable|string|max:2000',
            'risk_level'=>'required|in:low,medium,high,very_high',
            'minimum_investment'=>'required|numeric|min:0',
            'maximum_investment'=>'nullable|numeric|min:0|gte:minimum_investment',
            'management_fee_percent'=>'required|numeric|min:0|max:100',
            'lock_period_days'=>'required|integer|min:0|max:3650',
            'duration_days'=>'required|integer|min:1|max:3650',
            'return_interval_days'=>'required|integer|min:1|max:3650|lte:duration_days',
            'projected_return_min_percent'=>'required|numeric|min:0|max:100',
            'projected_return_max_percent'=>'required|numeric|min:0|max:100|gte:projected_return_min_percent',
            'subscription_fee_percent'=>'required|numeric|min:0|max:100',
            'redemption_fee_percent'=>'required|numeric|min:0|max:100',
        ]);

        $instrument->update([
            ...$data,
            'is_featured'=>$request->boolean('is_featured'),
            'is_visible'=>$request->boolean('is_visible'),
        ]);

        return back()->with('success','Instrument settings updated.');
    }

    public function toggle(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentReserveService $reserves
    ) {
        if ($instrument->status === 'active') {
            $reserves->pauseInstrument(
                $instrument,
                auth()->id(),
                'Admin paused the investment instrument.'
            );

            return back()->with('success','Instrument paused. Existing reserve authority and customer holdings were preserved.');
        }

        $reserves->activateInstrument(
            $instrument,
            auth()->id(),
            'Admin activated the investment after reserve-capacity validation.'
        );

        return back()->with('success','Instrument activated from verified reserve-backed capacity.');
    }

    public function storeAsset(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentReserveService $reserves
    ) {
        [$data, $authority] = $this->validateReferencedReserveAsset($request);

        $asset = $reserves->addReserveAsset(
            $instrument,
            $data,
            auth()->id()
        );

        $asset = $this->applyReferenceAuthority(
            $asset,
            $authority,
            auth()->id()
        );

        $this->syncPrimaryReferenceAsset(
            $instrument,
            auth()->id()
        );

        $reserves->rebuildSingleBaseAssetHistory(
            $instrument->fresh()
        );

        return back()->with(
            'success',
            'Reserve asset '.$asset->name.' added from its selected reference authority and sellable capacity synchronized.'
        );
    }

    public function updateAsset(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentAsset $asset,
        PrivateInvestmentReserveService $reserves
    ) {
        abort_unless($asset->instrument_id === $instrument->id, 404);

        [$data, $authority] = $this->validateReferencedReserveAsset(
            $request,
            $asset
        );

        $asset = $reserves->updateReserveAsset(
            $instrument,
            $asset,
            $data,
            auth()->id()
        );

        $asset = $this->applyReferenceAuthority(
            $asset,
            $authority,
            auth()->id()
        );

        $this->syncPrimaryReferenceAsset(
            $instrument,
            auth()->id()
        );

        $reserves->rebuildSingleBaseAssetHistory(
            $instrument->fresh()
        );

        return back()->with(
            'success',
            'Reserve asset '.$asset->name.' updated from its selected reference authority and capacity synchronized.'
        );
    }

    public function destroyAsset(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentAsset $asset,
        PrivateInvestmentReserveService $reserves
    ) {
        abort_unless($asset->instrument_id === $instrument->id, 404);

        $reserves->removeReserveAsset(
            $instrument,
            $asset,
            auth()->id(),
            'Admin removed the reserve asset from active backing.'
        );

        $this->syncPrimaryReferenceAsset(
            $instrument,
            auth()->id()
        );

        $reserves->rebuildSingleBaseAssetHistory(
            $instrument->fresh()
        );

        return back()->with(
            'success',
            'Reserve asset removed. Customer coverage and sellable capacity were revalidated.'
        );
    }

    private function validateReferencedReserveAsset(
        Request $request,
        ?PrivateInvestmentAsset $asset = null
    ): array {
        $request->validate([
            'reference_authority' => 'required|string|max:100',
        ]);

        $authority = (string) $request->input('reference_authority');

        if (str_starts_with($authority, 'public:')) {
            $baseAssetId = (int) substr($authority, 7);

            $publicBaseAsset = \App\Models\PublicInvestmentBaseAsset::query()
                ->with('marketInstrument')
                ->whereKey($baseAssetId)
                ->where('status', 'active')
                ->first();

            if (
                ! $publicBaseAsset
                || ! $publicBaseAsset->marketInstrument
                || ! $publicBaseAsset->marketInstrument->is_active
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reference_authority' =>
                        'Select an active approved Public Investment Base Asset.',
                ]);
            }

            $request->merge([
                'valuation_mode' =>
                    PrivateInvestmentAsset::VALUATION_MARKET_LINKED,
                'market_instrument_id' =>
                    $publicBaseAsset->market_instrument_id,
                'current_unit_price' => null,
            ]);
        } elseif (str_starts_with($authority, 'private:')) {
            $referenceId = (int) substr($authority, 8);

            $reference = \App\Models\PrivateMarketReference::query()
                ->whereKey($referenceId)
                ->where('status', 'active')
                ->first();

            if (! $reference) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reference_authority' => 'Select an active private market reference.',
                ]);
            }

            $request->merge([
                // The existing reserve engine receives the reference price through
                // its legacy local-price branch; applyReferenceAuthority() then
                // persists the first-class PRIVATE authority on the asset.
                'valuation_mode' => PrivateInvestmentAsset::VALUATION_MANUAL,
                'market_instrument_id' => null,
                'current_unit_price' => (float) $reference->current_price,
            ]);
        } elseif ($authority === 'legacy') {
            if (
                ! $asset
                || $asset->market_instrument_id
                || $asset->private_market_reference_id
                || $asset->valuation_mode !== PrivateInvestmentAsset::VALUATION_MANUAL
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reference_authority' => 'Legacy mode is available only for an existing unlinked reserve. Select a public or private reference to convert it.',
                ]);
            }

            $request->merge([
                'valuation_mode' => PrivateInvestmentAsset::VALUATION_MANUAL,
                'market_instrument_id' => null,
                'current_unit_price' => (float) $asset->current_unit_price,
            ]);
        } else {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reference_authority' => 'Select either a Public Market or Private Market reference.',
            ]);
        }

        return [
            $this->validateReserveAsset($request),
            $authority,
        ];
    }

    private function applyReferenceAuthority(
        PrivateInvestmentAsset $asset,
        string $authority,
        ?int $actorUserId
    ): PrivateInvestmentAsset {
        if (str_starts_with($authority, 'private:')) {
            $referenceId = (int) substr($authority, 8);

            $reference = \App\Models\PrivateMarketReference::query()
                ->whereKey($referenceId)
                ->where('status', 'active')
                ->firstOrFail();

            $previousReferenceId = $asset->private_market_reference_id;
            $quantity = (float) $asset->reserve_quantity;
            $price = (float) $reference->current_price;
            $valuation = round($quantity * $price, 2);

            $asset->update([
                'private_market_reference_id' => $reference->id,
                'public_investment_base_asset_id' => null,
                'market_instrument_id' => null,
                'valuation_mode' => PrivateInvestmentAsset::VALUATION_PRIVATE,
                'current_unit_price' => $price,
                'current_valuation' => $valuation,
                'last_valued_at' => $reference->last_valued_at ?: now(),
            ]);

            \App\Models\PrivateInvestmentReserveEvent::query()->create([
                'instrument_id' => $asset->instrument_id,
                'asset_id' => $asset->id,
                'action' => 'private_reference_selected',
                'valuation_mode' => PrivateInvestmentAsset::VALUATION_PRIVATE,
                'previous_quantity' => $quantity,
                'new_quantity' => $quantity,
                'previous_unit_price' => $price,
                'new_unit_price' => $price,
                'previous_valuation' => $valuation,
                'new_valuation' => $valuation,
                'market_price' => null,
                'reason' => 'Reserve asset reference authority set to private reference '.$reference->symbol.'.',
                'created_by_user_id' => $actorUserId,
                'metadata' => [
                    'previous_private_market_reference_id' => $previousReferenceId,
                    'private_market_reference_id' => $reference->id,
                    'private_market_reference_symbol' => $reference->symbol,
                    'selection_source' => 'investment_asset_form',
                ],
                'effective_at' => now(),
            ]);

            return $asset->fresh();
        }

        if (str_starts_with($authority, 'public:')) {
            $baseAssetId = (int) substr($authority, 7);

            $publicBaseAsset = \App\Models\PublicInvestmentBaseAsset::query()
                ->with('marketInstrument')
                ->whereKey($baseAssetId)
                ->where('status', 'active')
                ->firstOrFail();

            $asset->update([
                'public_investment_base_asset_id' => $publicBaseAsset->id,
                'private_market_reference_id' => null,
                'market_instrument_id' =>
                    $publicBaseAsset->market_instrument_id,
                'valuation_mode' =>
                    PrivateInvestmentAsset::VALUATION_MARKET_LINKED,
            ]);

            return $asset->fresh();
        }

        // Existing unlinked manual reserves remain readable until deliberately
        // converted. New reserve creation never offers this option.
        $asset->update([
            'public_investment_base_asset_id' => null,
            'private_market_reference_id' => null,
            'market_instrument_id' => null,
            'valuation_mode' => PrivateInvestmentAsset::VALUATION_MANUAL,
        ]);

        return $asset->fresh();
    }

    public function applyValuation(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentValuationService $valuation
    ) {
        $data=$request->validate([
            'event_type'=>'required|string|max:60',
            'direction'=>'required|in:positive,negative,neutral',
            'adjustment_type'=>'required|in:percentage,fixed,set',
            'adjustment_value'=>'required|numeric|min:0.000001',
            'asset_id'=>'nullable|exists:private_investment_assets,id',
            'reason'=>'required|string|max:2000',
        ]);

        if (!empty($data['asset_id'])) {
            abort_unless($instrument->assets()->whereKey($data['asset_id'])->exists(), 422);
        }

        $valuation->apply($instrument,$data,auth()->id());

        return back()->with('success','Approved valuation event applied. Price history and active holdings were revalued.');
    }

    public function resetTestValuations(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentValuationService $valuation
    ) {
        $valuation->resetAdminTestHistory($instrument);

        return back()->with('success', 'Admin test valuation events cleared and the instrument returned to its last non-admin baseline price.');
    }


public function lifecycleHistory(PrivateInvestmentInstrument $instrument)
{
    $events = $instrument->lifecycleEvents()
        ->latest('effective_at')
        ->latest('id')
        ->paginate(25);

    return view('admin.private-investments.lifecycle-history', compact('instrument', 'events'));
}

public function applyLifecycleEvent(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentLifecycleService $lifecycle
    ) {
        $data = $request->validate([
            'type' => 'required|in:distribution,deduction',
            'calculation_mode' => 'required|in:fixed_per_unit,percent_current_value',
            'value' => 'required|numeric|min:0.000001',
            'reason' => 'required|string|max:2000',
        ]);

        $event = $lifecycle->apply($instrument, $data, auth()->id());

        return back()->with(
            'success',
            ucfirst($event->type).' applied to '.$event->affected_holdings.' active holding(s). Total '.currency_symbol().number_format((float) $event->total_amount, 2).'.'
        );
    }

    private function syncPrimaryReferenceAsset(
        PrivateInvestmentInstrument $instrument,
        ?int $actorUserId = null
    ): void {
        $instrument->refresh();

        $active = $instrument->assets()
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->orderByDesc('current_valuation')
            ->orderBy('id')
            ->get();

        $previousReferenceId = $instrument->reference_asset_id;

        if ($active->isEmpty()) {
            if ($previousReferenceId !== null) {
                $instrument->update([
                    'reference_asset_id' => null,
                ]);

                PrivateInvestmentReserveEvent::query()->create([
                    'instrument_id' => $instrument->id,
                    'asset_id' => null,
                    'action' => 'base_asset_reference_synchronized',
                    'valuation_mode' => null,
                    'previous_quantity' => null,
                    'new_quantity' => null,
                    'previous_unit_price' => null,
                    'new_unit_price' => null,
                    'previous_valuation' => null,
                    'new_valuation' => null,
                    'market_price' => null,
                    'reason' => 'Displayed Base Asset reference cleared because no active reserve backing remains.',
                    'created_by_user_id' => $actorUserId,
                    'metadata' => [
                        'source' => 'reserve_base_asset_authority',
                        'previous_reference_asset_id' => $previousReferenceId,
                        'new_reference_asset_id' => null,
                    ],
                    'effective_at' => now(),
                ]);
            }

            return;
        }

        $currentStillValid = $previousReferenceId !== null
            && $active->contains(
                fn (PrivateInvestmentAsset $asset) =>
                    (int) $asset->id === (int) $previousReferenceId
            );

        if ($currentStillValid) {
            return;
        }

        $selected = $active->first();

        $instrument->update([
            'reference_asset_id' => $selected->id,
        ]);

        PrivateInvestmentReserveEvent::query()->create([
            'instrument_id' => $instrument->id,
            'asset_id' => $selected->id,
            'action' => 'base_asset_reference_synchronized',
            'valuation_mode' => $selected->valuation_mode,
            'previous_quantity' => null,
            'new_quantity' => null,
            'previous_unit_price' => null,
            'new_unit_price' => (float) $selected->current_unit_price ?: null,
            'previous_valuation' => null,
            'new_valuation' => (float) $selected->current_valuation,
            'market_price' => $selected->isMarketLinked()
                ? ((float) $selected->current_unit_price ?: null)
                : null,
            'reason' => 'Displayed Base Asset reference synchronized from active reserve allocation.',
            'created_by_user_id' => $actorUserId,
            'metadata' => [
                'source' => 'reserve_base_asset_authority',
                'previous_reference_asset_id' => $previousReferenceId,
                'new_reference_asset_id' => $selected->id,
                'market_instrument_id' => $selected->market_instrument_id,
                'private_market_reference_id' =>
                    $selected->private_market_reference_id,
            ],
            'effective_at' => now(),
        ]);
    }

    public function setReferenceAsset(
        Request $request,
        PrivateInvestmentInstrument $instrument
    ) {
        // Compatibility endpoint only. The universal reserve/Base Asset editor
        // now owns this relationship; independent reference selection is retired.
        $this->syncPrimaryReferenceAsset(
            $instrument,
            auth()->id()
        );

        return back()->with(
            'success',
            'Base Asset display reference synchronized from active reserve allocation.'
        );
    }

    public function updatePresentation(Request $request)
    {
        $data=$request->validate([
            'title'=>'required|string|max:80',
            'message'=>'required|string|max:500',
        ]);

        Setting::set('investment_pricing_authority_title',$data['title']);
        Setting::set('investment_pricing_authority_message',$data['message']);

        return back()->with('success','Investment customer-facing pricing remark updated.');
    }

    private function validateReserveAsset(Request $request): array
    {
        if ($request->filled('asset_type')) {
            $assetType = Str::of((string) $request->input('asset_type'))
                ->trim()
                ->lower()
                ->replace(['-', ' '], '_')
                ->replaceMatches('/_+/', '_')
                ->trim('_')
                ->toString();

            $request->merge([
                'asset_type' => $assetType,
            ]);
        }

        return $request->validate([
            'valuation_mode'=>'required|in:manual,market_linked',
            'market_instrument_id'=>'nullable|required_if:valuation_mode,market_linked|integer|exists:market_instruments,id',
            'asset_type'=>'required|string|max:50',
            'name'=>'required|string|max:255',
            'description'=>'nullable|string|max:1000',
            'reserve_quantity'=>'required|numeric|gt:0',
            'reserve_unit'=>'required|string|max:32',
            'acquisition_unit_price'=>'nullable|numeric|min:0',
            'current_unit_price'=>'nullable|required_if:valuation_mode,manual|numeric|gt:0',
            'notes'=>'nullable|string|max:1500',
        ]);
    }
}

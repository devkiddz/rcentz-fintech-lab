<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use App\Models\PrivateInvestmentTransaction;
use App\Models\Setting;
use App\Services\PrivateInvestmentValuationService;
use App\Services\PrivateInvestmentLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrivateInvestmentAdminController extends Controller
{
    public function index(Request $request)
    {
        $category = in_array((string) $request->query('category'), ['stock_market', 'forex', 'cryptocurrency', 'real_estate', 'bonds'], true)
            ? (string) $request->query('category')
            : null;

        $query = PrivateInvestmentInstrument::query();

        if ($category) {
            $query->where('category', $category);
        }

        $instruments = $query
            ->withCount([
                'assets',
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
            'category'=>'required|in:stock_market,forex,cryptocurrency,real_estate,bonds',
            'description'=>'nullable|string|max:2000',
            'risk_level'=>'required|in:low,medium,high,very_high',
            'opening_price'=>'required|numeric|min:0.000001',
            'unit_supply'=>'required|numeric|min:0',
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
            'status'=>'active',
            'current_price'=>$data['opening_price'],
            'previous_price'=>$data['opening_price'],
            'available_units'=>$data['unit_supply'],
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

        return redirect()->route('admin.investments.control.show',$instrument)->with('success','Investment instrument created.');
    }

    public function show(PrivateInvestmentInstrument $instrument)
    {
        $instrument->load([
            'assets'=>fn($q)=>$q->latest('created_at'),
            'events'=>fn($q)=>$q->latest('effective_at')->limit(25),
            'prices'=>fn($q)=>$q->latest('recorded_at')->limit(30),
            'lifecycleEvents'=>fn($q)=>$q->latest('effective_at')->limit(5),
        ]);

        $customers = \App\Models\User::query()
            ->where('is_admin', false)
            ->with('wallet')
            ->orderBy('name')
            ->get();

        return view('admin.private-investments.show', compact('instrument', 'customers'));
    }

    public function updateInstrument(Request $request, PrivateInvestmentInstrument $instrument)
    {
        $data=$request->validate([
            'name'=>'required|string|max:255',
            'category'=>'required|in:stock_market,forex,cryptocurrency,real_estate,bonds',
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
            'unit_supply'=>'required|numeric|min:0',
            'available_units'=>'required|numeric|min:0',
        ]);

        $instrument->update([
            ...$data,
            'is_featured'=>$request->boolean('is_featured'),
            'is_visible'=>$request->boolean('is_visible'),
        ]);

        return back()->with('success','Instrument settings updated.');
    }

    public function toggle(PrivateInvestmentInstrument $instrument)
    {
        $instrument->update(['status'=>$instrument->status === 'active' ? 'paused' : 'active']);
        return back()->with('success','Instrument status updated.');
    }

    public function storeAsset(Request $request, PrivateInvestmentInstrument $instrument)
    {
        $data=$request->validate([
            'asset_type'=>'required|string|max:50',
            'name'=>'required|string|max:255',
            'description'=>'nullable|string|max:1000',
            'acquisition_value'=>'required|numeric|min:0',
            'current_valuation'=>'required|numeric|min:0',
            'ownership_percentage'=>'required|numeric|min:0|max:100',
            'notes'=>'nullable|string|max:1500',
        ]);

        $instrument->assets()->create([
            ...$data,
            'status'=>'active',
            'acquired_at'=>now()->toDateString(),
            'effective_at'=>now(),
        ]);

        return back()->with('success','Underlying asset added.');
    }

    public function destroyAsset(PrivateInvestmentInstrument $instrument, PrivateInvestmentAsset $asset)
    {
        abort_unless($asset->instrument_id === $instrument->id, 404);
        $asset->update(['status'=>'removed']);
        return back()->with('success','Underlying asset removed from active composition.');
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
}

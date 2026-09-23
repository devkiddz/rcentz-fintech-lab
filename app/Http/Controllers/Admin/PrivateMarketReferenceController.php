<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentReserveEvent;
use App\Models\PrivateMarketReference;
use App\Models\PrivateMarketReferencePrice;
use App\Services\PrivateInvestmentReserveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrivateMarketReferenceController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.investments.instruments.base-assets.index', ['scope' => 'private']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:32', 'alpha_dash', Rule::unique('private_market_references', 'symbol')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:255'],
            'reference_unit' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
            'current_price' => ['required', 'numeric', 'min:0.00000001'],
        ]);

        $reference = DB::transaction(function () use ($data) {
            $now = now();
            $price = (float) $data['current_price'];

            $reference = PrivateMarketReference::query()->create([
                ...$data,
                'symbol' => strtoupper($data['symbol']),
                'currency' => strtoupper($data['currency']),
                'previous_price' => null,
                'status' => 'active',
                'last_valued_at' => $now,
                'created_by_user_id' => auth()->id(),
            ]);

            PrivateMarketReferencePrice::query()->create([
                'reference_id' => $reference->id,
                'previous_price' => null,
                'price' => $price,
                'change_amount' => 0,
                'change_percent' => 0,
                'reason' => 'Opening private market reference valuation.',
                'valued_by_user_id' => auth()->id(),
                'recorded_at' => $now,
            ]);

            return $reference;
        });

        return redirect()
            ->route('admin.instruments.private-references.show', $reference)
            ->with('success', 'Private market reference created.');
    }

    public function show(PrivateMarketReference $reference)
    {
        $reference->load([
            'assets' => fn ($query) => $query
                ->with('instrument')
                ->orderBy('name'),
            'prices' => fn ($query) => $query
                ->latest('recorded_at')
                ->limit(50),
        ]);

        return view('admin.private-market-references.show', compact('reference'));
    }

    public function updateIdentity(
        Request $request,
        PrivateMarketReference $reference
    ) {
        $data = $request->validate([
            'symbol' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                \Illuminate\Validation\Rule::unique('private_market_references', 'symbol')
                    ->ignore($reference->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:255'],
            'reference_unit' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $reference->update([
            ...$data,
            'symbol' => strtoupper($data['symbol']),
            'currency' => strtoupper($data['currency']),
        ]);

        return back()->with('success', 'Private base reference updated.');
    }

    public function updatePrice(
        Request $request,
        PrivateMarketReference $reference,
        PrivateInvestmentReserveService $reserves
    ) {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0.00000001'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($reference, $reserves, $data) {
            $reference = PrivateMarketReference::query()
                ->lockForUpdate()
                ->findOrFail($reference->id);

            $previous = (float) $reference->current_price;
            $next = (float) $data['price'];
            $change = $next - $previous;
            $percent = $previous > 0 ? ($change / $previous) * 100 : 0;
            $now = now();

            $reference->update([
                'previous_price' => $previous,
                'current_price' => $next,
                'last_valued_at' => $now,
            ]);

            PrivateMarketReferencePrice::query()->create([
                'reference_id' => $reference->id,
                'previous_price' => $previous,
                'price' => $next,
                'change_amount' => $change,
                'change_percent' => $percent,
                'reason' => $data['reason'],
                'valued_by_user_id' => auth()->id(),
                'recorded_at' => $now,
            ]);

            $assets = PrivateInvestmentAsset::query()
                ->where('private_market_reference_id', $reference->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->lockForUpdate()
                ->get();

            $instrumentIds = [];

            foreach ($assets as $asset) {
                $previousValuation = (float) $asset->current_valuation;
                $quantity = (float) $asset->reserve_quantity;
                $newValuation = round($quantity * $next, 2);

                $asset->update([
                    'valuation_mode' => PrivateInvestmentAsset::VALUATION_PRIVATE,
                    'market_instrument_id' => null,
                    'current_unit_price' => $next,
                    'current_valuation' => $newValuation,
                    'last_valued_at' => $now,
                ]);

                PrivateInvestmentReserveEvent::query()->create([
                    'instrument_id' => $asset->instrument_id,
                    'asset_id' => $asset->id,
                    'action' => 'private_reference_revalued',
                    'valuation_mode' => PrivateInvestmentAsset::VALUATION_PRIVATE,
                    'previous_quantity' => $quantity,
                    'new_quantity' => $quantity,
                    'previous_unit_price' => $previous,
                    'new_unit_price' => $next,
                    'previous_valuation' => $previousValuation,
                    'new_valuation' => $newValuation,
                    'market_price' => null,
                    'reason' => $data['reason'],
                    'created_by_user_id' => auth()->id(),
                    'metadata' => [
                        'private_market_reference_id' => $reference->id,
                        'private_market_reference_symbol' => $reference->symbol,
                    ],
                    'effective_at' => $now,
                ]);

                $instrumentIds[$asset->instrument_id] = true;
            }

            foreach (array_keys($instrumentIds) as $instrumentId) {
                $reserves->revalueFromReserves(
                    PrivateInvestmentInstrument::query()->findOrFail($instrumentId)
                );
            }
        });

        return back()->with('success', 'Private reference valuation updated and linked reserves revalued.');
    }

    public function toggleStatus(PrivateMarketReference $reference)
    {
        $reference->update([
            'status' => $reference->status === 'active' ? 'paused' : 'active',
        ]);

        return back()->with('success', 'Private market reference status updated.');
    }
}

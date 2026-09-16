<?php

namespace App\Http\Controllers;

use App\Models\PrivateInvestmentInstrument;
use App\Services\PrivateInvestmentChartService;
use Illuminate\Http\Request;

class PrivateInvestmentMarketController extends Controller
{
    public function index(Request $request)
    {
        $query = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused']);

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->string('risk_level'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('symbol', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        $instruments = $query
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $categories = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $marketQuery = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused']);

        $featured = (clone $marketQuery)
            ->where('is_featured', true)
            ->orderByDesc('last_valued_at')
            ->limit(4)
            ->get();

        $movers = (clone $marketQuery)
            ->get()
            ->sortByDesc(fn ($instrument) => abs((float) $instrument->change_percent))
            ->take(5)
            ->values();

        $categoryStats = (clone $marketQuery)
            ->selectRaw('category, COUNT(*) as instrument_count')
            ->groupBy('category')
            ->pluck('instrument_count', 'category');

        $overview = [
            'instruments' => (clone $marketQuery)->count(),
            'categories' => (clone $marketQuery)->distinct('category')->count('category'),
            'featured' => (clone $marketQuery)->where('is_featured', true)->count(),
            'underlying_valuation' => (float) \App\Models\PrivateInvestmentAsset::query()
                ->where('status', 'active')
                ->sum('current_valuation'),
        ];

        return view('private-investments.index', compact(
            'instruments',
            'categories',
            'featured',
            'movers',
            'categoryStats',
            'overview'
        ));
    }

    public function stocks(Request $request)
    {
        return $this->categoryListing($request, 'stock_market', 'Stocks');
    }

    public function crypto(Request $request)
    {
        return $this->categoryListing($request, 'cryptocurrency', 'Cryptocurrency');
    }

    public function realEstate(Request $request)
    {
        return $this->categoryListing($request, 'real_estate', 'Real Estate');
    }

    public function bonds(Request $request)
    {
        return $this->categoryListing($request, 'bonds', 'Bonds & Fixed Income');
    }

    public function account()
    {
        $isAdmin = auth()->user()->isAdmin();

        $holdings = $isAdmin
            ? collect()
            : \App\Models\PrivateInvestmentHolding::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->get();

        $transactions = $isAdmin
            ? collect()
            : \App\Models\PrivateInvestmentTransaction::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->latest('executed_at')
                ->limit(8)
                ->get();

        $summary = [
            'holdings' => $holdings->count(),
            'cost_basis' => (float) $holdings->sum('cost_basis'),
            'current_value' => (float) $holdings->sum('current_value'),
            'profit_loss' => (float) $holdings->sum('unrealized_profit_loss'),
        ];

        $summary['return_percent'] = $summary['cost_basis'] > 0
            ? ($summary['profit_loss'] / $summary['cost_basis']) * 100
            : 0;

        return view('private-investments.account', compact('holdings', 'transactions', 'summary', 'isAdmin'));
    }

    public function portfolio()
    {
        $holdings = auth()->user()->isAdmin()
            ? collect()
            : \App\Models\PrivateInvestmentHolding::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->get();

        return view('private-investments.portfolio', compact('holdings'));
    }

    public function transactions()
    {
        $transactions = auth()->user()->isAdmin()
            ? collect()
            : \App\Models\PrivateInvestmentTransaction::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->latest('executed_at')
                ->paginate(20);

        return view('private-investments.transactions', compact('transactions'));
    }

    public function watchlist()
    {
        $items = auth()->user()->isAdmin()
            ? collect()
            : \App\Models\PrivateInvestmentWatchlist::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                ->latest('updated_at')
                ->get();

        return view('private-investments.watchlist', compact('items'));
    }

    public function storeWatchlist(Request $request, PrivateInvestmentInstrument $instrument)
    {
        abort_if(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'nullable|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        \App\Models\PrivateInvestmentWatchlist::query()->updateOrCreate(
            ['user_id'=>auth()->id(),'instrument_id'=>$instrument->id],
            [
                'target_price'=>$data['target_price'] ?? null,
                'priority'=>$data['priority'] ?? 'normal',
                'note'=>$data['note'] ?? null,
                'last_reviewed_at'=>now(),
            ]
        );

        return back()->with('success','Investment added to your watchlist.');
    }

    public function updateWatchlist(Request $request, PrivateInvestmentInstrument $instrument)
    {
        abort_if(auth()->user()->isAdmin(), 403);

        $data=$request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'required|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        $item=\App\Models\PrivateInvestmentWatchlist::query()
            ->where('user_id',auth()->id())
            ->where('instrument_id',$instrument->id)
            ->firstOrFail();

        $item->update([...$data,'last_reviewed_at'=>now()]);

        return back()->with('success','Watchlist settings updated.');
    }

    public function destroyWatchlist(PrivateInvestmentInstrument $instrument)
    {
        abort_if(auth()->user()->isAdmin(),403);

        \App\Models\PrivateInvestmentWatchlist::query()
            ->where('user_id',auth()->id())
            ->where('instrument_id',$instrument->id)
            ->delete();

        return back()->with('success','Investment removed from your watchlist.');
    }

    public function performance()
    {
        $holdings = auth()->user()->isAdmin()
            ? collect()
            : \App\Models\PrivateInvestmentHolding::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->orderByDesc('updated_at')
                ->get();

        return view('private-investments.performance', compact('holdings'));
    }

    private function categoryListing(Request $request, string $category, string $categoryTitle)
    {
        $request->merge(['category' => $category]);

        $query = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->where('category', $category);

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->string('risk_level'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('symbol', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        $instruments = $query
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $categories = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('private-investments.index', compact('instruments', 'categories', 'categoryTitle'));
    }

    public function legacyType(string $type)
    {
        return match ($type) {
            'stock_market' => redirect()->route('investments.stocks'),
            'cryptocurrency' => redirect()->route('investments.crypto'),
            'real_estate' => redirect()->route('investments.real-estate'),
            'bonds', 'fixed_income' => redirect()->route('investments.bonds'),
            default => redirect()->route('investments.index'),
        };
    }

    public function legacyCategory(string $category)
    {
        return $this->legacyType($category);
    }

    public function show(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentChartService $charts
    ) {
        abort_unless($instrument->is_visible, 404);

        $instrument->load([
            'assets' => fn ($q) => $q->where('status', 'active')->orderByDesc('current_valuation'),
            'events' => fn ($q) => $q->where('approval_state', 'approved')->latest('effective_at')->limit(12),
        ]);

        $analysis = $charts->forInstrument($instrument);

        $viewerHolding = auth()->user()->isAdmin()
            ? null
            : \App\Models\PrivateInvestmentHolding::query()
                ->where('user_id', auth()->id())
                ->where('instrument_id', $instrument->id)
                ->first();

        $viewerWallet = auth()->user()->isAdmin() ? null : auth()->user()->wallet;

        return view('private-investments.show', compact('instrument', 'analysis', 'viewerHolding', 'viewerWallet'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate(['q' => 'required|string|min:2|max:120']);

        return redirect()->route('investments.index', ['search' => $validated['q']]);
    }

    public function categories()
    {
        return redirect()->route('investments.index');
    }

    public function featured()
    {
        return redirect()->route('investments.index', ['featured' => 1]);
    }

    public function byType(string $type)
    {
        $mapping = [
            'real_estate' => 'real_estate',
            'stock_market' => 'stock_market',
            'cryptocurrency' => 'cryptocurrency',
        ];

        abort_unless(isset($mapping[$type]), 404);

        return redirect()->route('investments.index', ['category' => $mapping[$type]]);
    }

    public function byCategory(string $category)
    {
        return redirect()->route('investments.index', ['category' => $category]);
    }
}

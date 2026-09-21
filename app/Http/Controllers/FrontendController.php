<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\StockNews;
use App\Models\Stock;
use App\Models\BotProduct;
use App\Models\CryptoPair;
use App\Models\CopyStrategy;
use App\Models\ForexPair;
use App\Models\MarketInstrument;
use App\Models\PrivateInvestmentInstrument;
use App\Models\Signal;
use App\Services\BotMarketContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class FrontendController extends Controller
{
    public function __construct()
    {
        $this->ensureStorageLink();
    }

    /**
     * Ensure storage link exists, create it if it doesn't
     */
    private function ensureStorageLink()
    {
        $linkPath = public_path('storage');
        
        // Check if the storage link already exists
        if (!File::exists($linkPath)) {
            try {
                // Run the storage:link artisan command
                Artisan::call('storage:link');
                
                // info logs suppressed; only log errors
            } catch (\Exception $e) {
                // Log error but don't break the application
                \Log::error('Failed to create storage link: ' . $e->getMessage());
            }
        }
    }

    public function index(BotMarketContextService $markets)
    {
        $latestNews = StockNews::getFeaturedNews(6);
        if ($latestNews->isEmpty()) {
            $latestNews = StockNews::orderBy('published_at', 'desc')->limit(6)->get();
        }

        $featuredStocks = Stock::active()
            ->with('marketInstrument')
            ->where('company_name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderByDesc('volume')
            ->limit(8)
            ->get();

        $forexPairs = ForexPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(8)
            ->get();

        $cryptoPairs = CryptoPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(8)
            ->get();

        $marketShowcase = collect();
        $seriesFor = function (?MarketInstrument $instrument, array $fallback = []) use ($markets): array {
            $quotes = $instrument
                ? collect($markets->priceSeries($instrument, 28))
                    ->pluck('price')
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values()
                : collect();

            if ($quotes->count() < 2) {
                $quotes = collect($fallback)
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values();
            }

            return $quotes->all();
        };

        foreach ($featuredStocks as $stock) {
            $current = (float) $stock->current_price;
            $previous = (float) ($stock->previous_close ?? 0);
            $marketShowcase->push([
                'market_instrument_id' => $stock->market_instrument_id,
                'asset_class' => 'stock',
                'asset_label' => 'Stock',
                'symbol' => (string) $stock->symbol,
                'name' => (string) ($stock->company_name ?: 'Listed equity'),
                'price' => $current,
                'price_display' => '$'.number_format($current, 2),
                'previous_display' => $previous > 0 ? '$'.number_format($previous, 2) : '—',
                'change' => (float) ($stock->change_percentage ?? 0),
                'icon' => 'chart-no-axes-combined',
                'quotes' => $seriesFor($stock->marketInstrument, [$previous, $current]),
            ]);
        }

        foreach ($forexPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) $pair->previous_close;
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0;
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 5)));

            $marketShowcase->push([
                'market_instrument_id' => $pair->market_instrument_id,
                'asset_class' => 'forex',
                'asset_label' => 'Forex',
                'symbol' => (string) ($pair->display_symbol ?: $pair->symbol),
                'name' => (string) ($pair->name ?: 'Currency pair'),
                'price' => $current,
                'price_display' => number_format($current, $precision),
                'previous_display' => $previous > 0 ? number_format($previous, $precision) : '—',
                'change' => $change,
                'icon' => 'arrow-left-right',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
            ]);
        }

        foreach ($cryptoPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) $pair->previous_close;
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0;
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 2)));
            $quote = strtoupper((string) ($pair->quote_asset ?: 'USD'));
            $price = number_format($current, $precision);
            $previousPrice = number_format($previous, $precision);

            $marketShowcase->push([
                'market_instrument_id' => $pair->market_instrument_id,
                'asset_class' => 'crypto',
                'asset_label' => 'Crypto',
                'symbol' => (string) ($pair->display_symbol ?: $pair->symbol),
                'name' => (string) ($pair->name ?: 'Digital asset pair'),
                'price' => $current,
                'price_display' => $quote === 'USD' ? '$'.$price : $price.' '.$quote,
                'previous_display' => $previous > 0 ? ($quote === 'USD' ? '$'.$previousPrice : $previousPrice.' '.$quote) : '—',
                'change' => $change,
                'icon' => 'bitcoin',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
            ]);
        }

        $marketGroups = $marketShowcase
            ->groupBy('asset_class')
            ->map(fn ($items) => $items->values());

        $marketShowcase = collect(range(0, 7))
            ->flatMap(function (int $index) use ($marketGroups) {
                return collect(['stock', 'forex', 'crypto'])
                    ->map(fn ($assetClass) => $marketGroups->get($assetClass, collect())->get($index))
                    ->filter();
            })
            ->take(18)
            ->values();

        $marketTape = $marketShowcase->take(12)->values();

        $heroMarkets = collect(['stock', 'forex', 'crypto'])
            ->map(function (string $assetClass) use ($marketGroups, $markets) {
                $candidate = $marketGroups
                    ->get($assetClass, collect())
                    ->sortByDesc(fn (array $market) => abs((float) ($market['change'] ?? 0)))
                    ->first();

                if (! $candidate) {
                    return null;
                }

                $instrumentId = $candidate['market_instrument_id'] ?? null;
                $instrument = $instrumentId
                    ? MarketInstrument::query()
                        ->with(['canonicalStock', 'canonicalForexPair', 'canonicalCryptoPair', 'stock', 'forexPair'])
                        ->find($instrumentId)
                    : null;

                if (! $instrument) {
                    return [
                        ...$candidate,
                        'market_status_label' => 'Available',
                        'opportunity_label' => 'Strongest movement',
                    ];
                }

                $context = $markets->forInstrument($instrument, 32);
                $quotes = collect($context['quotes'] ?? [])
                    ->pluck('price')
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values();

                if ($quotes->count() < 2) {
                    $quotes = collect($candidate['quotes'] ?? [])
                        ->map(fn ($price) => (float) $price)
                        ->filter(fn ($price) => $price > 0)
                        ->values();
                }

                $status = (string) ($context['market_status'] ?? 'available');
                $statusLabel = match ($status) {
                    '24_7' => '24 / 7',
                    'open' => 'Open',
                    'closed' => 'Closed',
                    'controlled' => 'Controlled',
                    'unavailable' => 'Unavailable',
                    default => ucfirst(str_replace('_', ' ', $status)),
                };

                return [
                    ...$candidate,
                    'symbol' => (string) ($context['symbol'] ?? $candidate['symbol']),
                    'name' => (string) ($context['name'] ?? $candidate['name']),
                    'price_display' => (string) ($context['current_display'] ?? $candidate['price_display']),
                    'previous_display' => (string) ($context['previous_close_display'] ?? $candidate['previous_display']),
                    'change' => (float) ($context['change_percentage'] ?? $candidate['change']),
                    'market_status_label' => $statusLabel,
                    'quotes' => $quotes->all(),
                    'opportunity_label' => 'Strongest movement',
                ];
            })
            ->filter()
            ->values();

        $investmentUniverse = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->whereIn('category', ['real_estate', 'stock_market', 'forex', 'cryptocurrency'])
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderByDesc('last_valued_at')
            ->orderBy('name')
            ->get();

        $durationLabel = static function (?PrivateInvestmentInstrument $instrument): string {
            $days = (int) ($instrument?->duration_days ?? 0);
            if ($days <= 0) {
                return 'Flexible';
            }
            if ($days < 30) {
                return $days.' '.($days === 1 ? 'day' : 'days');
            }
            if ($days % 365 === 0) {
                $years = (int) ($days / 365);
                return $years.' '.($years === 1 ? 'year' : 'years');
            }
            if ($days % 30 === 0) {
                $months = (int) ($days / 30);
                return $months.' '.($months === 1 ? 'month' : 'months');
            }
            return $days.' days';
        };

        $investmentCategoryMeta = collect([
            'real_estate' => [
                'label' => 'Real Estate',
                'eyebrow' => 'Property-backed opportunities',
                'icon' => 'building-2',
                'route' => 'investments.real-estate',
                'accent' => '#f59e0b',
                'description' => 'Structured property exposure with defined capital requirements, duration and independent private-market valuation.',
            ],
            'stock_market' => [
                'label' => 'Stocks',
                'eyebrow' => 'Equity investment products',
                'icon' => 'chart-no-axes-combined',
                'route' => 'investments.stocks',
                'accent' => '#10b981',
                'description' => 'Private investment instruments built around equity exposure with their own unit pricing and investment lifecycle.',
            ],
            'forex' => [
                'label' => 'Forex',
                'eyebrow' => 'Currency-market opportunities',
                'icon' => 'arrow-left-right',
                'route' => 'investments.forex',
                'accent' => '#0ea5e9',
                'description' => 'Structured currency-market products separated from brokerage trading and governed by private investment pricing authority.',
            ],
            'cryptocurrency' => [
                'label' => 'Crypto',
                'eyebrow' => 'Digital-asset opportunities',
                'icon' => 'bitcoin',
                'route' => 'investments.crypto',
                'accent' => '#8b5cf6',
                'description' => 'Digital-asset investment products with explicit risk, duration and projected-range information before capital is committed.',
            ],
        ]);

        $investmentCategories = $investmentCategoryMeta->map(function (array $meta, string $category) use ($investmentUniverse, $durationLabel) {
            $items = $investmentUniverse->where('category', $category)->values();
            /** @var PrivateInvestmentInstrument|null $featured */
            $featured = $items->first();
            $currency = strtoupper((string) ($featured?->currency ?: 'USD'));
            $move = $featured ? (float) $featured->change_percent : null;

            return [
                ...$meta,
                'category' => $category,
                'count' => $items->count(),
                'featured' => $featured,
                'minimum_display' => $featured
                    ? $currency.' '.number_format((float) $featured->minimum_investment, 0)
                    : '—',
                'range_display' => $featured
                    ? number_format((float) $featured->projected_return_min_percent, 1).'–'.number_format((float) $featured->projected_return_max_percent, 1).'%'
                    : '—',
                'duration_display' => $durationLabel($featured),
                'risk_display' => $featured
                    ? ucwords(str_replace('_', ' ', (string) ($featured->risk_level ?: 'standard')))
                    : '—',
                'price_display' => $featured
                    ? $currency.' '.number_format((float) $featured->current_price, 2)
                    : '—',
                'move' => $move,
            ];
        })->values();

        $featuredInventory = Car::query()
            ->where('is_available', true)
            ->latest('created_at')
            ->limit(6)
            ->get();

        $signalShowcase = collect([
            ['asset_class' => 'stock', 'label' => 'Stock signal intelligence', 'icon' => 'chart-no-axes-combined'],
            ['asset_class' => 'forex', 'label' => 'Forex signal intelligence', 'icon' => 'arrow-left-right'],
            ['asset_class' => 'crypto', 'label' => 'Crypto signal intelligence', 'icon' => 'bitcoin'],
        ])->map(function (array $item) {
            $item['count'] = Signal::query()
                ->whereIn('status', ['published', 'active'])
                ->whereHas('marketInstrument', fn ($query) => $query->where('asset_class', $item['asset_class']))
                ->count();

            return $item;
        });

        $botAssetClassCount = BotProduct::query()
            ->with('marketInstrument:id,asset_class')
            ->where('is_active', true)
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->get(['id', 'market_instrument_id'])
            ->pluck('marketInstrument.asset_class')
            ->filter()
            ->unique()
            ->count();

        $publicCopyStrategies = CopyStrategy::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->count();

        $platformSystems = [
            'bot_asset_classes' => $botAssetClassCount,
            'copy_strategies' => $publicCopyStrategies,
            'signal_asset_classes' => $signalShowcase->where('count', '>', 0)->count(),
        ];

        $platformStats = [
            'instruments' => MarketInstrument::active()->count(),
            'investments' => PrivateInvestmentInstrument::query()
                ->where('is_visible', true)
                ->whereIn('status', ['active', 'paused'])
                ->where('name', 'not like', '%ACCEPTANCE%')
                ->count(),
            'bots' => BotProduct::query()
                ->where('is_active', true)
                ->where('name', 'not like', '%ACCEPTANCE%')
                ->count(),
            'signals' => Signal::query()->whereIn('status', ['published', 'active'])->count(),
        ];

        return view('frontend.home', compact(
            'latestNews',
            'marketShowcase',
            'marketTape',
            'heroMarkets',
            'investmentCategories',
            'featuredInventory',
            'signalShowcase',
            'platformSystems',
            'platformStats'
        ));
    }

    public function show($id)
    {
        $car = Car::findOrFail($id);
        
        // If car is not available, check if current user has purchased it or has pending purchase
        if (!$car->is_available) {
            // If user is not authenticated, deny access
            if (!auth()->check()) {
                abort(404);
            }
            
            // Check if the authenticated user has purchased this car or has a pending purchase
            $userHasPurchaseOrPending = $car->purchases()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['completed', 'pending', 'processing'])
                ->exists();
            
            // If user hasn't purchased this car or doesn't have pending purchase, deny access
            if (!$userHasPurchaseOrPending) {
                abort(404);
            }
        }
        
        return view('frontend.car_detail', compact('car'));
    }

    public function browse(Request $request)
    {
        $query = Car::query();
        
        // Filter by availability (default: show all)
        if ($request->has('available') && $request->available == 'true') {
            $query->where('is_available', true);
        }
        
        // Filter by make
        if ($request->has('make') && !empty($request->make)) {
            $query->where('make', $request->make);
        }
        
        // Filter by model
        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', $request->model);
        }
        
        // Filter by year
        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }
        
        // Sort options
        $sort = $request->sort ?? 'newest';
        
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        // Get unique makes, models and years for filters
        $makes = Car::distinct()->pluck('make')->filter();
        $models = Car::distinct()->pluck('model')->filter();
        $years = Car::distinct()->pluck('year')->filter()->sort()->reverse();
        
        $cars = $query->paginate(12);
        
        return view('frontend.browse', compact('cars', 'makes', 'models', 'years', 'sort'));
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validate the form data
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|in:general,support,billing,partnership,press',
                'message' => 'required|string|min:10|max:2000',
            ], [
                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'subject.required' => 'Please select a subject.',
                'subject.in' => 'Please select a valid subject.',
                'message.required' => 'Please enter your message.',
                'message.min' => 'Your message must be at least 10 characters long.',
                'message.max' => 'Your message cannot exceed 2000 characters.',
            ]);

            try {
                // Send email to site email
                $siteEmail = site_email();
                $subject = "Contact Form: " . ucfirst($validated['subject']);
                
                $emailContent = "
                New contact form submission from {$validated['first_name']} {$validated['last_name']}
                
                Email: {$validated['email']}
                Subject: " . ucfirst($validated['subject']) . "
                
                Message:
                {$validated['message']}
                
                ---
                This message was sent from the contact form on " . site_name() . "
                ";

                // Use Laravel's Mail facade to send the email
                \Mail::raw($emailContent, function($message) use ($siteEmail, $subject, $validated) {
                    $message->to($siteEmail)
                            ->subject($subject)
                            ->replyTo($validated['email'], $validated['first_name'] . ' ' . $validated['last_name']);
                });

                return redirect()->route('contact')->with('success', 'Thank you for your message! We will get back to you soon.');
                
            } catch (\Exception $e) {
                return redirect()->route('contact')
                    ->withErrors(['message' => 'Sorry, there was an error sending your message. Please try again later.'])
                    ->withInput();
            }
        }

        return view('frontend.contact');
    }

    public function helpCenter()
    {
        return view('frontend.help-center');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }
}

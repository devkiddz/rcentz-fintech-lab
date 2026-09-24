@extends('layouts.main')

@section('content')
@php
    $sparklinePoints = function ($quotes, int $width = 320, int $height = 92) {
        $values = collect($quotes ?? [])
            ->filter(fn ($value) => is_numeric($value) && (float) $value > 0)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($values->count() < 2) return '';

        $min = (float) $values->min();
        $max = (float) $values->max();
        $range = max($max - $min, abs($max) * 0.0001, 0.00000001);
        $count = max(1, $values->count() - 1);

        return $values->map(function ($value, $index) use ($min, $range, $count, $width, $height) {
            $x = ($index / $count) * $width;
            $normalized = ((float) $value - $min) / $range;
            $y = $height - ($normalized * ($height - 14)) - 7;
            return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
        })->implode(' ');
    };
@endphp

<style>
    .markets-page{
        min-height:70vh;
        background:
            radial-gradient(circle at 84% 6%,color-mix(in srgb,var(--brand-primary) 10%,transparent),transparent 27rem),
            radial-gradient(circle at 8% 26%,color-mix(in srgb,var(--brand-secondary) 6%,transparent),transparent 24rem),
            hsl(var(--background));
    }
    .markets-hero{
        position:relative;
        overflow:hidden;
        border:1px solid hsl(var(--border));
        background:
            linear-gradient(135deg,color-mix(in srgb,var(--brand-primary) 6%,hsl(var(--card))) 0%,hsl(var(--card)) 52%,color-mix(in srgb,var(--brand-secondary) 4%,hsl(var(--card))) 100%);
        box-shadow:0 24px 70px hsl(var(--foreground)/.07);
    }
    .markets-hero::after{
        content:"";
        position:absolute;
        width:20rem;
        height:20rem;
        right:-5rem;
        top:-8rem;
        border-radius:9999px;
        background:color-mix(in srgb,var(--brand-primary) 16%,transparent);
        filter:blur(55px);
        pointer-events:none;
    }
    .marketplace-toolbar{
        position:sticky;
        top:5rem;
        z-index:20;
        backdrop-filter:blur(18px);
        -webkit-backdrop-filter:blur(18px);
    }
    .marketplace-filter[data-active="true"]{
        background:var(--brand-primary);
        color:white;
        border-color:var(--brand-primary);
        box-shadow:0 10px 24px color-mix(in srgb,var(--brand-primary) 18%,transparent);
    }
    .marketplace-card{
        position:relative;
        overflow:hidden;
        min-height:19rem;
        border:1px solid hsl(var(--border));
        background:hsl(var(--card)/.90);
        box-shadow:0 16px 42px hsl(var(--foreground)/.055);
        transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
    }
    .marketplace-card::before{
        content:"";
        position:absolute;
        right:-4rem;
        top:-4rem;
        width:10rem;
        height:10rem;
        border-radius:9999px;
        background:color-mix(in srgb,var(--market-accent) 13%,transparent);
        filter:blur(30px);
        pointer-events:none;
    }
    .marketplace-card:hover{
        transform:translateY(-4px);
        border-color:color-mix(in srgb,var(--market-accent) 30%,hsl(var(--border)));
        box-shadow:0 22px 55px color-mix(in srgb,var(--market-accent) 8%,hsl(var(--foreground)/.06));
    }
    .marketplace-chart{
        background:
            linear-gradient(hsl(var(--border)/.24) 1px,transparent 1px),
            linear-gradient(90deg,hsl(var(--border)/.20) 1px,transparent 1px),
            linear-gradient(180deg,hsl(var(--muted)/.24),transparent);
        background-size:100% 33%,25% 100%,100% 100%;
    }
    .marketplace-card[hidden]{display:none !important}
    .marketplace-stat{
        background:hsl(var(--card)/.72);
        border:1px solid hsl(var(--border));
    }
    .marketplace-search{
        background:hsl(var(--background)/.78);
    }
</style>

<section class="markets-page">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8 lg:pb-20 lg:pt-8">
        <section class="markets-hero rounded-[2rem] px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
            <div class="relative z-10 grid items-end gap-8 lg:grid-cols-[1.15fr_.85fr]">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">
                        {{ localize('ui.markets.eyebrow', 'Global market marketplace') }}
                    </p>
                    <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-[-.055em] sm:text-5xl lg:text-6xl">
                        {{ localize('ui.markets.titleA', 'Research Markets.') }}
                        <span style="color:var(--brand-primary)">{{ localize('ui.markets.titleB', 'Choose Your Opportunity.') }}</span>
                    </h1>
                    <p class="mt-5 max-w-2xl text-sm leading-7 text-muted-foreground sm:text-base">
                        {{ localize('ui.markets.copy', 'Browse stocks, forex, crypto and commodities from one dedicated marketplace. Compare current prices, movement and short market context before opening a trading workspace.') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 lg:grid-cols-2 xl:grid-cols-5">
                    @foreach([
                        ['All',$marketStats['total'] ?? 0],
                        ['Stocks',$marketStats['stock'] ?? 0],
                        ['Forex',$marketStats['forex'] ?? 0],
                        ['Crypto',$marketStats['crypto'] ?? 0],
                        ['Commodities',$marketStats['commodity'] ?? 0],
                    ] as [$label,$value])
                        <div class="marketplace-stat rounded-2xl px-3 py-3">
                            <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-xl font-semibold tabular-nums text-foreground">{{ number_format($value) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="marketplace-toolbar mt-5 rounded-2xl border border-border bg-background/80 p-3 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2" role="group" aria-label="{{ localize('ui.markets.filters', 'Market filters') }}">
                    @foreach([
                        ['all','All markets'],
                        ['stock','Stocks'],
                        ['forex','Forex'],
                        ['crypto','Crypto'],
                        ['commodity','Commodities'],
                    ] as [$value,$label])
                        <button
                            type="button"
                            data-marketplace-filter="{{ $value }}"
                            data-active="{{ $value === 'all' ? 'true' : 'false' }}"
                            class="marketplace-filter inline-flex h-9 items-center rounded-xl border border-border bg-card px-3.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <label class="marketplace-search flex h-10 min-w-0 items-center gap-2 rounded-xl border border-border px-3 lg:w-80">
                    <i data-lucide="search" class="h-4 w-4 shrink-0 text-muted-foreground"></i>
                    <input
                        type="search"
                        data-marketplace-search
                        class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-foreground outline-none placeholder:text-muted-foreground/70 focus:ring-0"
                        placeholder="{{ localize('ui.markets.search', 'Search symbol or market') }}"
                    >
                </label>
            </div>
        </div>

        <div data-marketplace-grid class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($marketCards as $market)
                @php
                    $change = (float) ($market['change'] ?? 0);
                    $positive = $change >= 0;
                    $points = $sparklinePoints($market['quotes'] ?? []);
                    $accent = match($market['asset_class']) {
                        'stock' => '#0ea5e9',
                        'forex' => '#8b5cf6',
                        'crypto' => '#f59e0b',
                        'commodity' => '#10b981',
                        default => 'var(--brand-primary)',
                    };
                    $searchKey = strtolower(($market['symbol'] ?? '').' '.($market['name'] ?? '').' '.($market['asset_label'] ?? ''));
                @endphp

                <article
                    class="marketplace-card rounded-2xl p-4 sm:p-5"
                    data-marketplace-card
                    data-market-class="{{ $market['asset_class'] }}"
                    data-market-search="{{ $searchKey }}"
                    style="--market-accent:{{ $accent }}"
                >
                    <div class="relative z-10 flex h-full flex-col">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background:color-mix(in srgb,var(--market-accent) 11%,transparent);color:var(--market-accent)">
                                    <i data-lucide="{{ $market['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-base font-semibold text-foreground">{{ $market['symbol'] }}</p>
                                        <span class="rounded-full px-2 py-1 text-[8px] font-semibold uppercase tracking-[.12em]" style="background:color-mix(in srgb,var(--market-accent) 10%,transparent);color:var(--market-accent)">{{ $market['asset_label'] }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ $market['name'] }}</p>
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-base font-semibold tabular-nums text-foreground">{{ $market['price_display'] }}</p>
                                <p class="mt-1 text-xs font-semibold tabular-nums {{ $positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $positive ? '+' : '' }}{{ number_format($change, 2) }}%
                                </p>
                            </div>
                        </div>

                        <div class="marketplace-chart mt-5 h-28 overflow-hidden rounded-xl border border-border/60 p-2">
                            @if($points !== '')
                                <svg viewBox="0 0 320 92" preserveAspectRatio="none" class="h-full w-full" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="marketFill{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="{{ $accent }}" stop-opacity=".20"/>
                                            <stop offset="100%" stop-color="{{ $accent }}" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>
                                    <polygon points="0,92 {{ $points }} 320,92" fill="url(#marketFill{{ $loop->index }})"/>
                                    <polyline points="{{ $points }}" fill="none" stroke="{{ $accent }}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @else
                                <div class="flex h-full items-center justify-center text-xs text-muted-foreground">
                                    {{ localize('ui.markets.awaiting_history', 'Awaiting price history') }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-4 border-t border-border pt-4">
                            <div>
                                <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">{{ localize('ui.markets.access', 'Access') }}</p>
                                <p class="mt-1 text-xs font-medium text-foreground">{{ auth()->check() ? localize('ui.markets.workspace_ready', 'Workspace ready') : localize('ui.markets.signin_required', 'Sign in to open') }}</p>
                            </div>
                            <a href="{{ $market['action_url'] }}" class="inline-flex h-9 items-center gap-2 rounded-xl px-3.5 text-xs font-semibold text-white transition hover:brightness-110" style="background:var(--brand-primary)">
                                {{ auth()->check() ? localize('ui.markets.open', 'Open market') : localize('ui.nav.sign_in', 'Sign in') }}
                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-border bg-card p-10 text-center">
                    <i data-lucide="chart-candlestick" class="mx-auto h-8 w-8 text-muted-foreground"></i>
                    <p class="mt-3 text-sm font-semibold text-foreground">{{ localize('ui.markets.empty', 'No public markets are available yet.') }}</p>
                </div>
            @endforelse
        </div>

        <div data-marketplace-empty class="mt-5 hidden rounded-2xl border border-dashed border-border bg-card p-10 text-center">
            <i data-lucide="search-x" class="mx-auto h-8 w-8 text-muted-foreground"></i>
            <p class="mt-3 text-sm font-semibold text-foreground">{{ localize('ui.markets.no_match', 'No markets match this filter.') }}</p>
            <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.markets.no_match_copy', 'Try another asset class or search term.') }}</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const cards = Array.from(document.querySelectorAll('[data-marketplace-card]'));
    const filters = Array.from(document.querySelectorAll('[data-marketplace-filter]'));
    const search = document.querySelector('[data-marketplace-search]');
    const empty = document.querySelector('[data-marketplace-empty]');
    let activeClass = 'all';

    const apply = () => {
        const term = (search?.value || '').trim().toLowerCase();
        let visible = 0;

        cards.forEach((card) => {
            const classMatch = activeClass === 'all' || card.dataset.marketClass === activeClass;
            const termMatch = !term || (card.dataset.marketSearch || '').includes(term);
            const show = classMatch && termMatch;
            card.hidden = !show;
            if (show) visible++;
        });

        empty?.classList.toggle('hidden', visible !== 0);
    };

    filters.forEach((button) => {
        button.addEventListener('click', () => {
            activeClass = button.dataset.marketplaceFilter || 'all';
            filters.forEach((item) => item.dataset.active = item === button ? 'true' : 'false');
            apply();
        });
    });

    search?.addEventListener('input', apply);
    apply();
})();
</script>
@endpush

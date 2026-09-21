@extends('layouts.main')

@section('content')
@php
    $tagline = setting('site_tagline', 'Markets, intelligence and financial control.');
    $description = setting('site_description', 'A modern financial platform for markets, portfolio management, intelligent signals, automation and private investments.');
    $company = setting('company_name', site_name());
    $accountUrl = auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) : route('register');
    $marketUrl = auth()->check() ? route('instruments.index') : route('login');
    $investmentUrl = auth()->check() ? route('investments.index') : route('login');
    $botUrl = auth()->check() ? route('ai-bots.marketplace') : route('login');
    $copyUrl = auth()->check() ? route('copy-trading.marketplace') : route('login');
    $signalUrl = auth()->check() ? route('signals.index') : route('login');
    $activeSignalTotal = (int) $signalShowcase->sum('count');

    $sparklinePoints = function ($quotes, int $width = 300, int $height = 96) {
        $values = collect($quotes ?? [])
            ->map(fn ($value) => is_array($value) ? ($value['price'] ?? null) : $value)
            ->filter(fn ($value) => is_numeric($value) && (float) $value > 0)
            ->map(fn ($value) => (float) $value)
            ->values();

        if ($values->count() < 2) {
            return '';
        }

        $min = (float) $values->min();
        $max = (float) $values->max();
        $range = max($max - $min, abs($max) * 0.0001, 0.00000001);
        $count = max(1, $values->count() - 1);

        return $values->map(function ($value, $index) use ($min, $range, $count, $width, $height) {
            $x = ($index / $count) * $width;
            $normalized = ((float) $value - $min) / $range;
            $y = $height - ($normalized * ($height - 12)) - 6;
            return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
        })->implode(' ');
    };
@endphp

<style>
    .home-hero {
        background:
            radial-gradient(circle at 18% 16%, color-mix(in srgb, var(--brand-primary) 32%, transparent) 0, transparent 36%),
            radial-gradient(circle at 82% 18%, color-mix(in srgb, var(--brand-primary) 18%, transparent) 0, transparent 30%),
            linear-gradient(135deg, color-mix(in srgb, var(--brand-primary) 20%, #090d14) 0%, #090d14 52%, #0b1018 100%);
    }
    .home-hero::after {
        content:"";
        position:absolute;
        inset:auto -12rem -17rem auto;
        width:34rem;
        height:34rem;
        border-radius:9999px;
        background:color-mix(in srgb, var(--brand-primary) 24%, transparent);
        filter:blur(90px);
        pointer-events:none;
    }
    .home-hero-orb { animation:home-float 8s ease-in-out infinite; }
    @keyframes home-float { 0%,100%{transform:translate3d(0,0,0)} 50%{transform:translate3d(0,-10px,0)} }
    .market-tape-track{display:flex;width:max-content;animation:market-tape 38s linear infinite}
    .market-tape:hover .market-tape-track{animation-play-state:paused}
    @keyframes market-tape{from{transform:translateX(0)}to{transform:translateX(-50%)}}
    .home-carousel{scrollbar-width:none;-ms-overflow-style:none}
    .home-carousel::-webkit-scrollbar{display:none}
    .home-market-card{min-width:min(82vw,300px)}
    .home-pulse-glow{box-shadow:0 24px 78px color-mix(in srgb,var(--brand-primary) 14%,transparent),0 22px 64px rgba(0,0,0,.24)}
    .home-pulse-shell{isolation:isolate}
    .home-pulse-shell::before{content:"";position:absolute;inset:0;z-index:-1;background:radial-gradient(circle at 92% 5%,color-mix(in srgb,var(--brand-primary) 20%,transparent),transparent 34%),radial-gradient(circle at 8% 100%,rgba(37,99,235,.08),transparent 30%),linear-gradient(145deg,#0d1119 0%,#090d14 54%,#0d1017 100%)}
    .home-pulse-shell::after{content:"";position:absolute;inset:0 auto 0 0;width:2px;background:linear-gradient(180deg,transparent 5%,var(--brand-primary) 44%,color-mix(in srgb,var(--brand-primary) 24%,transparent) 76%,transparent 96%);box-shadow:0 0 24px color-mix(in srgb,var(--brand-primary) 36%,transparent);pointer-events:none}
    .home-pulse-chip{border:0;background:rgba(255,255,255,.045);box-shadow:inset 0 0 0 1px rgba(255,255,255,.07);backdrop-filter:blur(14px)}
    .home-pulse-card{position:relative;overflow:hidden;min-height:22rem;background:linear-gradient(160deg,color-mix(in srgb,var(--pulse-accent) 8%,rgba(255,255,255,.035)) 0%,rgba(255,255,255,.022) 42%,rgba(255,255,255,.012) 100%);box-shadow:inset 0 0 0 1px rgba(255,255,255,.065),0 18px 46px rgba(0,0,0,.18);transition:transform .2s ease,box-shadow .2s ease}
    .home-pulse-card:hover{transform:translateY(-3px);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--pulse-accent) 18%,rgba(255,255,255,.07)),0 22px 54px color-mix(in srgb,var(--pulse-accent) 7%,rgba(0,0,0,.25))}
    .home-pulse-card::before{content:"";position:absolute;right:-4.5rem;top:-4.5rem;width:11rem;height:11rem;border-radius:9999px;background:color-mix(in srgb,var(--pulse-accent) 18%,transparent);filter:blur(34px);pointer-events:none}
    .home-pulse-card::after{content:"";position:absolute;left:1.25rem;right:1.25rem;top:0;height:1px;background:linear-gradient(90deg,transparent,color-mix(in srgb,var(--pulse-accent) 35%,rgba(255,255,255,.25)),transparent);opacity:.7;pointer-events:none}
    .home-pulse-card-chart{position:relative;background:linear-gradient(180deg,rgba(255,255,255,.025),transparent)}
    .home-pulse-card-chart::before{content:"";position:absolute;left:0;right:0;top:52%;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.055),transparent);pointer-events:none}
    .home-pulse-metric{background:rgba(255,255,255,.025);box-shadow:inset 0 0 0 1px rgba(255,255,255,.045)}
    .home-investment-card{position:relative;overflow:hidden;min-height:31rem;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
    .home-investment-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--investment-accent) 42%,hsl(var(--border)));box-shadow:0 22px 60px color-mix(in srgb,var(--investment-accent) 10%,transparent)}
    .home-investment-card::before{content:"";position:absolute;right:-5rem;top:-5rem;width:13rem;height:13rem;border-radius:9999px;background:color-mix(in srgb,var(--investment-accent) 14%,transparent);filter:blur(18px);pointer-events:none}
    .home-system-card{position:relative;overflow:hidden;min-height:29rem;background:linear-gradient(180deg,color-mix(in srgb,var(--system-accent) 6%,hsl(var(--card))) 0%,hsl(var(--card)) 40%,color-mix(in srgb,var(--system-accent) 3%,hsl(var(--card))) 100%);border:1px solid color-mix(in srgb,var(--system-accent) 10%,rgba(15,23,42,.06));box-shadow:0 18px 48px rgba(15,23,42,.08),inset 0 1px 0 rgba(255,255,255,.52);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
    .dark .home-system-card{background:linear-gradient(180deg,rgba(255,255,255,.034),rgba(255,255,255,.016));border-color:rgba(255,255,255,.06);box-shadow:0 22px 60px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.04)}
    .home-system-card:hover{transform:translateY(-4px);border-color:color-mix(in srgb,var(--system-accent) 22%,rgba(15,23,42,.08));box-shadow:0 24px 64px color-mix(in srgb,var(--system-accent) 9%,rgba(15,23,42,.10)),inset 0 1px 0 rgba(255,255,255,.56)}
    .dark .home-system-card:hover{border-color:color-mix(in srgb,var(--system-accent) 24%,rgba(255,255,255,.10));box-shadow:0 26px 70px color-mix(in srgb,var(--system-accent) 10%,rgba(0,0,0,.45)),inset 0 1px 0 rgba(255,255,255,.05)}
    .home-system-card::before{content:"";position:absolute;right:-4rem;top:-5rem;width:13rem;height:13rem;border-radius:9999px;background:color-mix(in srgb,var(--system-accent) 17%,transparent);filter:blur(28px);pointer-events:none}
    .home-system-card::after{content:"";position:absolute;left:1.5rem;right:1.5rem;top:0;height:1px;background:linear-gradient(90deg,transparent,color-mix(in srgb,var(--system-accent) 24%,rgba(255,255,255,.25)),transparent);opacity:.55;pointer-events:none}
    .home-system-pill{border:0;background:color-mix(in srgb,var(--system-accent) 10%, transparent);color:color-mix(in srgb,var(--system-accent) 70%, hsl(var(--foreground)));box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--system-accent) 14%, transparent)}
    .dark .home-system-pill{background:color-mix(in srgb,var(--system-accent) 12%, rgba(255,255,255,.02));color:color-mix(in srgb,var(--system-accent) 56%, white);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--system-accent) 16%, transparent)}
    .home-system-icon{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--system-accent) 14%, transparent);background:color-mix(in srgb,var(--system-accent) 12%, transparent);color:var(--system-accent)}
    .dark .home-system-icon{background:color-mix(in srgb,var(--system-accent) 12%, rgba(255,255,255,.02));color:color-mix(in srgb,var(--system-accent) 72%, white)}
    .home-system-step{display:flex;gap:.75rem;border:0;border-radius:1rem;padding:.9rem .95rem;background:color-mix(in srgb,var(--system-accent) 5.5%, transparent);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--system-accent) 8%, transparent)}
    .dark .home-system-step{background:color-mix(in srgb,var(--system-accent) 6%, rgba(255,255,255,.015));box-shadow:inset 0 0 0 1px rgba(255,255,255,.03)}
    .home-system-rule{background:color-mix(in srgb,var(--system-accent) 84%, white)}
    .dark .home-system-rule{background:color-mix(in srgb,var(--system-accent) 64%, white)}
    .home-system-footer{margin-top:1.25rem;border-radius:1rem;padding:.95rem 1rem;background:color-mix(in srgb,var(--system-accent) 4.5%, transparent);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--system-accent) 8%, transparent)}
    .dark .home-system-footer{background:rgba(255,255,255,.025);box-shadow:inset 0 0 0 1px rgba(255,255,255,.035)}
    .home-accordion[open] summary .home-accordion-chevron{transform:rotate(180deg)}
    .home-accordion summary::-webkit-details-marker{display:none}
    .home-accordion summary{list-style:none}
    .home-inventory{scrollbar-width:none;-ms-overflow-style:none}
    .home-inventory::-webkit-scrollbar{display:none}
    .home-inventory-card{min-width:min(86vw,360px)}
    .home-inventory-image{background:linear-gradient(145deg,color-mix(in srgb,var(--brand-primary) 10%,#111827),#111827)}
    @media (min-width:640px){.home-inventory-card{min-width:340px}}
    @media (min-width:640px){.home-market-card{min-width:300px}}
    @media (prefers-reduced-motion:reduce){.market-tape-track{animation:none}.home-hero-orb{animation:none}}
</style>

<section class="home-hero relative overflow-hidden border-b border-white/10 text-white">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-20 top-10 h-80 w-80 rounded-full opacity-50" style="background:color-mix(in srgb,var(--brand-primary) 28%,transparent);filter:blur(120px)"></div>
        <div class="absolute right-[6%] top-12 h-72 w-72 rounded-full opacity-30" style="background:color-mix(in srgb,var(--brand-primary) 20%,transparent);filter:blur(120px)"></div>
        <div class="absolute bottom-[-9rem] left-[38%] h-72 w-72 rounded-full opacity-20" style="background:#2563eb;filter:blur(135px)"></div>
    </div>

    <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[.86fr_1.14fr] lg:px-8 lg:py-24 xl:gap-16">
        <div class="flex flex-col justify-center">
            <div class="inline-flex w-fit items-center gap-2 rounded-full border border-white/15 bg-black/15 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.16em] text-white/70 backdrop-blur">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-35" style="background:var(--brand-primary)"></span><span class="relative inline-flex h-2 w-2 rounded-full" style="background:var(--brand-primary)"></span></span>
                Market access · Intelligence · Automation
            </div>

            <p class="mt-8 text-xs font-semibold uppercase tracking-[.2em] text-white/60">{{ $company }}</p>
            <h1 class="mt-3 max-w-3xl text-5xl font-semibold tracking-[-.06em] sm:text-6xl lg:text-[4.6rem] lg:leading-[.93]">{{ $tagline }}</h1>
            <p class="mt-6 max-w-2xl text-base leading-7 text-white/65 sm:text-lg">{{ $description }}</p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $accountUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:brightness-110" style="background:var(--brand-primary);box-shadow:0 18px 44px color-mix(in srgb,var(--brand-primary) 24%,transparent)"><i data-lucide="arrow-up-right" class="h-4 w-4"></i>{{ auth()->check() ? 'Open workspace' : 'Create an account' }}</a>
                <a href="#markets" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/[.04] px-5 text-sm font-semibold text-white/90 backdrop-blur transition hover:bg-white/[.08]">Explore markets<i data-lucide="chevron-down" class="h-4 w-4"></i></a>
            </div>

            <div class="mt-10 grid max-w-2xl grid-cols-3 overflow-hidden rounded-2xl border border-white/10 bg-black/15 backdrop-blur">
                @foreach([
                    ['Market instruments', $platformStats['instruments'] ?? 0],
                    ['Investment products', $platformStats['investments'] ?? 0],
                    ['Automation + signals', ($platformStats['bots'] ?? 0) + ($platformStats['signals'] ?? 0)],
                ] as [$label,$value])
                    <div class="border-r border-white/10 p-4 last:border-r-0 sm:p-5">
                        <p class="text-2xl font-semibold tabular-nums sm:text-3xl">{{ number_format($value) }}</p>
                        <p class="mt-1 text-[8px] uppercase tracking-[.14em] text-white/45 sm:text-[9px]">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center">
            <div class="home-pulse-glow home-pulse-shell relative w-full overflow-hidden rounded-[2rem] border border-white/10 shadow-2xl backdrop-blur-xl">
                <div class="relative flex flex-col gap-4 border-b border-white/10 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-35"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span></span>
                            <p class="text-[9px] font-semibold uppercase tracking-[.2em] text-white/48">Live market pulse</p>
                        </div>
                        <h2 class="mt-2 text-xl font-semibold tracking-[-.025em] sm:text-2xl">Three markets. Three current opportunities.</h2>
                        <p class="mt-2 max-w-lg text-[11px] leading-5 text-white/42">A movement-led scan of the strongest current Stock, Forex and Crypto candidates using real stored market history.</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 lg:min-w-[19rem]">
                        <div class="home-pulse-chip rounded-xl px-3 py-2.5"><p class="text-[7px] font-semibold uppercase tracking-[.13em] text-white/30">State</p><p class="mt-1 text-[10px] font-semibold text-emerald-300">Market ready</p></div>
                        <div class="home-pulse-chip rounded-xl px-3 py-2.5"><p class="text-[7px] font-semibold uppercase tracking-[.13em] text-white/30">Universe</p><p class="mt-1 text-[10px] font-semibold text-white/85">{{ number_format($platformStats['instruments'] ?? 0) }} tracked</p></div>
                        <div class="home-pulse-chip rounded-xl px-3 py-2.5"><p class="text-[7px] font-semibold uppercase tracking-[.13em] text-white/30">Coverage</p><p class="mt-1 text-[10px] font-semibold text-white/85">3 asset classes</p></div>
                    </div>
                </div>

                @if($heroMarkets->isNotEmpty())
                    <div class="relative p-4 sm:p-5">
                        <div class="grid gap-3 lg:grid-cols-3">
                            @foreach($heroMarkets as $market)
                                @php
                                    $points = $sparklinePoints($market['quotes'] ?? [], 280, 92);
                                    $pulseAccent = match($market['asset_class'] ?? '') {
                                        'stock' => '#fb7185',
                                        'forex' => '#38bdf8',
                                        'crypto' => '#a78bfa',
                                        default => '#fb7185',
                                    };
                                @endphp
                                <article class="home-pulse-card group rounded-[1.45rem] p-4 sm:p-5" style="--pulse-accent:{{ $pulseAccent }}">
                                    <div class="relative flex h-full flex-col">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full px-2 py-1 text-[7px] font-semibold uppercase tracking-[.14em]" style="background:color-mix(in srgb,var(--pulse-accent) 11%,transparent);color:color-mix(in srgb,var(--pulse-accent) 72%,white);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--pulse-accent) 16%,transparent)">{{ $market['opportunity_label'] ?? 'Movement candidate' }}</span>
                                                    <span class="text-[7px] font-semibold uppercase tracking-[.14em] text-white/30">{{ $market['asset_label'] }}</span>
                                                </div>
                                                <h3 class="mt-4 truncate text-xl font-semibold tracking-[-.035em] sm:text-2xl">{{ $market['symbol'] }}</h3>
                                                <p class="mt-1 truncate text-[9px] text-white/34">{{ $market['name'] }}</p>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="text-[7px] font-semibold uppercase tracking-[.13em] text-white/27">Current</p>
                                                <p class="mt-1.5 text-base font-semibold tabular-nums sm:text-lg">{{ $market['price_display'] }}</p>
                                                <p class="mt-1 text-[9px] font-semibold {{ $market['change'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">{{ $market['change'] >= 0 ? '+' : '' }}{{ number_format($market['change'],2) }}%</p>
                                            </div>
                                        </div>

                                        <div class="home-pulse-card-chart mt-5 h-28 overflow-hidden rounded-xl sm:h-32">
                                            @if($points)
                                                <svg viewBox="0 0 280 92" preserveAspectRatio="none" class="relative z-[1] h-full w-full" role="img" aria-label="{{ $market['symbol'] }} recent price movement">
                                                    <defs>
                                                        <linearGradient id="pulseFill{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="0%" stop-color="{{ $market['change'] >= 0 ? '#34d399' : '#fb7185' }}" stop-opacity=".2" />
                                                            <stop offset="100%" stop-color="{{ $market['change'] >= 0 ? '#34d399' : '#fb7185' }}" stop-opacity="0" />
                                                        </linearGradient>
                                                    </defs>
                                                    <polygon points="0,92 {{ $points }} 280,92" fill="url(#pulseFill{{ $loop->index }})" />
                                                    <polyline points="{{ $points }}" fill="none" stroke="{{ $market['change'] >= 0 ? '#34d399' : '#fb7185' }}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                                </svg>
                                            @else
                                                <div class="relative z-[1] flex h-full items-center justify-center text-[9px] text-white/28">Price history is building</div>
                                            @endif
                                        </div>

                                        <div class="mt-4 grid grid-cols-3 gap-2">
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[6px] uppercase tracking-[.12em] text-white/25">Previous</p><p class="mt-1.5 truncate text-[9px] font-semibold text-white/72">{{ $market['previous_display'] ?? '—' }}</p></div>
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[6px] uppercase tracking-[.12em] text-white/25">Market</p><p class="mt-1.5 truncate text-[9px] font-semibold text-white/72">{{ $market['market_status_label'] ?? 'Available' }}</p></div>
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[6px] uppercase tracking-[.12em] text-white/25">History</p><p class="mt-1.5 text-[9px] font-semibold text-white/72">{{ number_format(count($market['quotes'] ?? [])) }} pts</p></div>
                                        </div>

                                        <div class="mt-auto flex items-center justify-between pt-4 text-[8px] text-white/28">
                                            <span>Movement-led scan</span>
                                            <span class="inline-flex items-center gap-1.5 font-semibold" style="color:color-mix(in srgb,var(--pulse-accent) 70%,white)">{{ $market['asset_label'] }} <i data-lucide="activity" class="h-3 w-3"></i></span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-3 flex flex-col gap-2 rounded-xl bg-black/15 px-3 py-3 text-[8px] text-white/30 sm:flex-row sm:items-center sm:justify-between">
                            <span>Three strongest current movement candidates — one from each supported market class.</span>
                            <span class="font-semibold text-white/45">Stored market history · no decorative data</span>
                        </div>
                    </div>
                @else
                    <div class="relative p-8 text-center text-sm text-white/45">Market instruments will appear here as the configured universe becomes available.</div>
                @endif
            </div>
        </div>

    </div>
</section>

@if($marketTape->isNotEmpty())
<section class="market-tape overflow-hidden border-b border-border bg-card/95" aria-label="Market tape">
    <div class="flex items-stretch">
        <div class="hidden shrink-0 items-center px-5 text-[9px] font-semibold uppercase tracking-[.16em] text-white sm:flex" style="background:var(--brand-primary)">Market tape</div>
        <div class="min-w-0 flex-1 overflow-hidden">
            <div class="market-tape-track">
                @foreach([1,2] as $copy)
                    <div class="flex shrink-0 items-center" aria-hidden="{{ $copy === 2 ? 'true' : 'false' }}">
                        @foreach($marketTape as $market)
                            <div class="flex min-w-max items-center gap-3 border-r border-border px-5 py-3">
                                <span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $market['asset_label'] }}</span>
                                <span class="text-xs font-semibold">{{ $market['symbol'] }}</span>
                                <span class="text-xs tabular-nums text-muted-foreground">{{ $market['price_display'] }}</span>
                                <span class="text-[10px] font-semibold {{ $market['change'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $market['change'] >= 0 ? '+' : '' }}{{ number_format($market['change'],2) }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<section id="markets" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Market universe</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em] sm:text-4xl">One market window. Multiple asset classes.</h2>
            <p class="mt-4 text-sm leading-7 text-muted-foreground">Move through stocks, currencies and digital assets without turning the homepage into another quote table.</p>
        </div>
        <div class="flex items-center gap-2"><button type="button" data-market-prev class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card hover:bg-muted" aria-label="Previous markets"><i data-lucide="arrow-left" class="h-4 w-4"></i></button><button type="button" data-market-next class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card hover:bg-muted" aria-label="Next markets"><i data-lucide="arrow-right" class="h-4 w-4"></i></button></div>
    </div>

    <div class="mt-7 flex flex-wrap gap-2" data-market-filters>
        @foreach([['all','All markets'],['stock','Stocks'],['forex','Forex'],['crypto','Crypto']] as [$filter,$label])
            <button type="button" data-market-filter="{{ $filter }}" class="rounded-full border border-border px-3.5 py-2 text-[10px] font-semibold uppercase tracking-[.12em] text-muted-foreground transition hover:bg-muted" data-active="{{ $filter === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="home-carousel mt-5 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2" data-market-carousel>
        @forelse($marketShowcase as $market)
            @php $marketPoints = $sparklinePoints($market['quotes'] ?? [], 280, 78); @endphp
            <article class="home-market-card snap-start overflow-hidden rounded-2xl border border-border bg-card transition hover:-translate-y-0.5 hover:shadow-lg" data-market-card data-asset="{{ $market['asset_class'] }}">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div><span class="rounded-full bg-muted px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">{{ $market['asset_label'] }}</span><h3 class="mt-4 text-xl font-semibold">{{ $market['symbol'] }}</h3><p class="mt-1 line-clamp-1 text-xs text-muted-foreground">{{ $market['name'] }}</p></div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-background"><i data-lucide="{{ $market['icon'] }}" class="h-4 w-4"></i></div>
                    </div>
                    <div class="mt-6 flex items-end justify-between gap-4"><div><p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Market price</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $market['price_display'] }}</p></div><p class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $market['change'] >= 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-red-500/10 text-red-600' }}">{{ $market['change'] >= 0 ? '+' : '' }}{{ number_format($market['change'],2) }}%</p></div>

                    <div class="mt-5 h-20 overflow-hidden rounded-xl border border-border/70 bg-background/55 px-2 py-2">
                        @if($marketPoints)
                            <svg viewBox="0 0 280 78" preserveAspectRatio="none" class="h-full w-full" role="img" aria-label="{{ $market['symbol'] }} recent price history">
                                <polyline points="{{ $marketPoints }}" fill="none" stroke="{{ $market['change'] >= 0 ? '#059669' : '#e11d48' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            </svg>
                        @else
                            <div class="flex h-full items-center justify-center text-[9px] text-muted-foreground">Price history is building</div>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-border pt-3">
                        <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Previous</p><p class="mt-1 text-[11px] font-semibold tabular-nums">{{ $market['previous_display'] ?? '—' }}</p></div>
                        <div class="text-right"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">History</p><p class="mt-1 text-[11px] font-semibold">{{ number_format(count($market['quotes'] ?? [])) }} points</p></div>
                    </div>
                </div>
                <div class="h-1 w-full" style="background:linear-gradient(90deg,var(--brand-primary),transparent)"></div>
            </article>
        @empty
            <div class="w-full rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">No active market instruments are available yet.</div>
        @endforelse
    </div>
    <div class="mt-5"><a href="{{ $marketUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold hover:text-muted-foreground">Open full market universe <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a></div>
</section>

<section id="opportunities" class="border-y border-border bg-card/35">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Private investment market</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em] sm:text-4xl">Four investment categories. Better information before capital moves.</h2>
                <p class="mt-4 text-sm leading-7 text-muted-foreground">Real Estate, Stocks, Forex and Crypto stay inside the private investment engine with their own pricing, risk, duration and projected-range authority.</p>
            </div>
            <a href="{{ $investmentUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold transition hover:text-muted-foreground">Explore all investments <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($investmentCategories as $category)
                @php
                    $featured = $category['featured'];
                    $categoryUrl = auth()->check() ? route($category['route']) : route('login');
                    $move = $category['move'];
                @endphp
                <article class="home-investment-card group rounded-[1.75rem] border border-border bg-background" style="--investment-accent:{{ $category['accent'] }}">
                    <div class="relative flex h-full flex-col p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl border" style="border-color:color-mix(in srgb,var(--investment-accent) 24%,transparent);background:color-mix(in srgb,var(--investment-accent) 11%,transparent);color:var(--investment-accent)"><i data-lucide="{{ $category['icon'] }}" class="h-5 w-5"></i></span>
                            <span class="rounded-full border border-border bg-card px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ number_format($category['count']) }} {{ $category['count'] === 1 ? 'offering' : 'offerings' }}</span>
                        </div>

                        <p class="mt-6 text-[8px] font-semibold uppercase tracking-[.15em]" style="color:var(--investment-accent)">{{ $category['eyebrow'] }}</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-[-.035em]">{{ $category['label'] }}</h3>
                        <p class="mt-3 text-xs leading-6 text-muted-foreground">{{ $category['description'] }}</p>

                        <div class="mt-5 rounded-2xl border border-border bg-card/70 p-4">
                            @if($featured)
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0"><p class="text-[8px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Featured instrument</p><p class="mt-1.5 line-clamp-2 text-sm font-semibold">{{ $featured->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $featured->symbol }}</p></div>
                                    <div class="shrink-0 text-right"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Unit price</p><p class="mt-1.5 text-xs font-semibold tabular-nums">{{ $category['price_display'] }}</p>@if($move !== null)<p class="mt-1 text-[9px] font-semibold {{ $move >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $move >= 0 ? '+' : '' }}{{ number_format($move,2) }}%</p>@endif</div>
                                </div>
                            @else
                                <div class="flex min-h-14 items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-muted text-muted-foreground"><i data-lucide="clock-3" class="h-4 w-4"></i></span><div><p class="text-xs font-semibold">Category ready</p><p class="mt-1 text-[9px] text-muted-foreground">No public offering is currently listed.</p></div></div>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-border bg-border">
                            <div class="bg-card p-3"><p class="text-[7px] uppercase tracking-[.12em] text-muted-foreground">Minimum</p><p class="mt-1.5 text-xs font-semibold">{{ $category['minimum_display'] }}</p></div>
                            <div class="bg-card p-3"><p class="text-[7px] uppercase tracking-[.12em] text-muted-foreground">Projected range</p><p class="mt-1.5 text-xs font-semibold">{{ $category['range_display'] }}</p></div>
                            <div class="bg-card p-3"><p class="text-[7px] uppercase tracking-[.12em] text-muted-foreground">Duration</p><p class="mt-1.5 text-xs font-semibold">{{ $category['duration_display'] }}</p></div>
                            <div class="bg-card p-3"><p class="text-[7px] uppercase tracking-[.12em] text-muted-foreground">Risk</p><p class="mt-1.5 text-xs font-semibold">{{ $category['risk_display'] }}</p></div>
                        </div>

                        <a href="{{ $categoryUrl }}" class="mt-auto inline-flex items-center justify-between gap-3 pt-6 text-xs font-semibold"><span>Explore {{ $category['label'] }}</span><span class="flex h-8 w-8 items-center justify-center rounded-full border border-border bg-card transition group-hover:translate-x-0.5" style="color:var(--investment-accent)"><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="systems" class="relative overflow-hidden bg-background">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -left-24 top-8 h-72 w-72 rounded-full" style="background:color-mix(in srgb,var(--brand-primary) 10%,transparent);filter:blur(105px)"></div>
        <div class="absolute right-0 top-10 h-72 w-72 rounded-full" style="background:rgba(139,92,246,.08);filter:blur(110px)"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-border/70 to-transparent"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-end">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Trading systems</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-[-.045em] text-foreground sm:text-4xl">Professional automation, with each engine staying in its own lane.</h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-muted-foreground lg:justify-self-end">Bot Trader, Copy Trader and Automated Signals are three different authorities. Each one has its own contract, execution path and lifecycle instead of being compressed into one vague “AI trading” promise.</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-3">
            <article class="home-system-card group rounded-[1.8rem] p-6" style="--system-accent:#0ea5e9">
                <div class="relative flex h-full flex-col">
                    <div class="flex items-start justify-between gap-4"><span class="home-system-icon flex h-11 w-11 items-center justify-center rounded-2xl"><i data-lucide="bot" class="h-5 w-5"></i></span><span class="home-system-pill rounded-full px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.12em]">{{ number_format($platformStats['bots'] ?? 0) }} active products</span></div>
                    <p class="mt-7 text-[8px] font-semibold uppercase tracking-[.15em]" style="color:var(--system-accent)">Bot Trader</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-foreground">Strategy rules become controlled broker execution.</h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">Automated strategies operate through customer limits and the same multi-asset brokerage authority used by direct trading. The bot does not bypass execution controls.</p>
                    <div class="mt-6 space-y-2.5">
                        @foreach([['Strategy engine','Evaluates configured market rules'],['BrokerOrder','Creates the execution authority'],['Market router','Routes Stock · Forex · Crypto'],['Position lifecycle','Tracks the resulting exposure']] as [$step,$copy])
                            <div class="home-system-step"><span class="home-system-rule mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"></span><div><p class="text-[10px] font-semibold text-foreground/90">{{ $step }}</p><p class="mt-1 text-[9px] leading-5 text-muted-foreground">{{ $copy }}</p></div></div>
                        @endforeach
                    </div>
                    <div class="home-system-footer flex items-center justify-between"><span class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Asset classes in active products</span><strong class="text-sm text-foreground">{{ number_format($platformSystems['bot_asset_classes'] ?? 0) }}</strong></div>
                    <a href="{{ $botUrl }}" class="mt-auto inline-flex items-center gap-2 pt-6 text-xs font-semibold text-foreground">Open Bot Trader <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" style="color:var(--system-accent)"></i></a>
                </div>
            </article>

            <article class="home-system-card group rounded-[1.8rem] p-6" style="--system-accent:#f59e0b">
                <div class="relative flex h-full flex-col">
                    <div class="flex items-start justify-between gap-4"><span class="home-system-icon flex h-11 w-11 items-center justify-center rounded-2xl"><i data-lucide="users-round" class="h-5 w-5"></i></span><span class="home-system-pill rounded-full px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.12em]">{{ number_format($platformSystems['copy_strategies'] ?? 0) }} public strategies</span></div>
                    <p class="mt-7 text-[8px] font-semibold uppercase tracking-[.15em]" style="color:var(--system-accent)">Copy Trader</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-foreground">Follow a strategy without surrendering allocation control.</h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">Provider activity is replicated through a follower relationship with allocation, ratio and trade-size controls. Copied activity still enters the broker execution path.</p>
                    <div class="mt-6 space-y-2.5">
                        @foreach([['Provider strategy','Defines the source activity'],['Copy relationship','Owns follower allocation limits'],['Copy controls','Apply ratio and trade-size boundaries'],['Broker execution','Creates attributable follower positions']] as [$step,$copy])
                            <div class="home-system-step"><span class="home-system-rule mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"></span><div><p class="text-[10px] font-semibold text-foreground/90">{{ $step }}</p><p class="mt-1 text-[9px] leading-5 text-muted-foreground">{{ $copy }}</p></div></div>
                        @endforeach
                    </div>
                    <div class="home-system-footer flex items-center justify-between"><span class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Control model</span><strong class="text-xs text-foreground">Allocation based</strong></div>
                    <a href="{{ $copyUrl }}" class="mt-auto inline-flex items-center gap-2 pt-6 text-xs font-semibold text-foreground">Open Copy Trader <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" style="color:var(--system-accent)"></i></a>
                </div>
            </article>

            <article class="home-system-card group rounded-[1.8rem] p-6" style="--system-accent:#8b5cf6">
                <div class="relative flex h-full flex-col">
                    <div class="flex items-start justify-between gap-4"><span class="home-system-icon flex h-11 w-11 items-center justify-center rounded-2xl"><i data-lucide="radio-tower" class="h-5 w-5"></i></span><span class="home-system-pill rounded-full px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.12em]">{{ number_format($activeSignalTotal) }} active</span></div>
                    <p class="mt-7 text-[8px] font-semibold uppercase tracking-[.15em]" style="color:var(--system-accent)">Automated Signals</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-foreground">Analysis can evolve without pretending it executed a trade.</h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">Signal intelligence has delivery and lifecycle authority of its own. A published signal may become active, adjusted, closed or stopped, while premium entry, stop and targets remain entitled customer data.</p>
                    <div class="mt-6 space-y-2.5">
                        @foreach([['Deterministic analysis','Builds the market thesis'],['SignalDelivery','Owns customer access'],['Lifecycle events','Track ready · active · adjusted · terminal'],['Execution boundary','No automatic Signal → Trade']] as [$step,$copy])
                            <div class="home-system-step"><span class="home-system-rule mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full"></span><div><p class="text-[10px] font-semibold text-foreground/90">{{ $step }}</p><p class="mt-1 text-[9px] leading-5 text-muted-foreground">{{ $copy }}</p></div></div>
                        @endforeach
                    </div>
                    <div class="home-system-footer grid grid-cols-3 gap-2">
                        @foreach($signalShowcase as $signal)
                            <div><p class="text-[7px] uppercase tracking-[.11em] text-muted-foreground">{{ str_replace(' signal intelligence','',$signal['label']) }}</p><p class="mt-1 text-xs font-semibold text-foreground">{{ number_format($signal['count']) }}</p></div>
                        @endforeach
                    </div>
                    <a href="{{ $signalUrl }}" class="mt-auto inline-flex items-center gap-2 pt-6 text-xs font-semibold text-foreground">Open Signals <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" style="color:var(--system-accent)"></i></a>
                </div>
            </article>
        </div>
    </div>
</section>

@if(($featuredInventory ?? collect())->isNotEmpty())
<section id="inventory" class="overflow-hidden bg-background">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-3xl">
                <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Marketplace inventory</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em] sm:text-4xl">Available inventory, presented as part of the wider financial marketplace.</h2>
                <p class="mt-4 text-sm leading-7 text-muted-foreground">Browse currently available physical inventory without allowing commerce to overpower the platform's core markets, intelligence and investment experience.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-inventory-prev class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Previous inventory"><i data-lucide="arrow-left" class="h-4 w-4"></i></button>
                <button type="button" data-inventory-next class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground" aria-label="Next inventory"><i data-lucide="arrow-right" class="h-4 w-4"></i></button>
            </div>
        </div>

        <div data-inventory-carousel class="home-inventory mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
            @foreach($featuredInventory as $item)
                <article class="home-inventory-card group snap-start overflow-hidden rounded-[1.75rem] border border-border bg-card">
                    <a href="{{ route('cars.show', $item->id) }}" class="block">
                        <div class="home-inventory-image relative aspect-[16/10] overflow-hidden">
                            @if($item->first_image)
                                <img src="{{ str_starts_with($item->first_image, 'http') ? $item->first_image : asset('storage/'.$item->first_image) }}"
                                     alt="{{ $item->title }}"
                                     class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.035]"
                                     loading="lazy" decoding="async">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/[.05] text-white/70 backdrop-blur">
                                        <i data-lucide="package-open" class="h-7 w-7"></i>
                                    </div>
                                </div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/70 to-transparent"></div>
                            <span class="absolute left-4 top-4 rounded-full border border-white/15 bg-black/35 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.14em] text-white/80 backdrop-blur">Available</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">{{ trim(($item->make ?? '').' '.($item->model ?? '')) ?: 'Marketplace inventory' }}</p>
                                    <h3 class="mt-2 line-clamp-2 text-xl font-semibold tracking-[-.03em]">{{ $item->title }}</h3>
                                </div>
                                <i data-lucide="arrow-up-right" class="mt-1 h-4 w-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"></i>
                            </div>
                            <div class="mt-5 flex items-center justify-between gap-4 border-t border-border pt-4">
                                <div>
                                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Listed price</p>
                                    <p class="mt-1 text-base font-semibold">{{ $item->formatted_price }}</p>
                                </div>
                                @if($item->year)
                                    <div class="text-right">
                                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Year</p>
                                        <p class="mt-1 text-sm font-semibold">{{ $item->year }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            <a href="{{ route('cars.browse') }}" class="inline-flex items-center gap-2 text-xs font-semibold transition hover:text-muted-foreground">Browse all inventory <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
        </div>
    </div>
</section>
@endif

<section id="calculator" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="grid gap-5 lg:grid-cols-[1.08fr_.92fr]">
        <div class="overflow-hidden rounded-[1.75rem] border border-border bg-card">
            <div class="relative overflow-hidden border-b border-border p-6 sm:p-8">
                <div class="absolute -right-20 -top-24 h-56 w-56 rounded-full" style="background:color-mix(in srgb,var(--brand-primary) 12%,transparent);filter:blur(50px)"></div>
                <div class="relative">
                    <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Investment calculator</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em]">See how time and consistency can compound.</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground">Adjust the assumptions yourself. The result is an educational projection, not a promise of performance.</p>
                </div>
            </div>

            <div class="p-6 sm:p-8" data-investment-calculator>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Initial investment</span><div class="mt-2 flex h-11 items-center rounded-xl border border-border bg-background px-3"><span class="mr-2 text-xs text-muted-foreground">$</span><input data-calc-principal type="number" min="0" step="100" value="5000" class="w-full bg-transparent text-sm font-semibold outline-none"></div></label>
                    <label class="block"><span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Monthly contribution</span><div class="mt-2 flex h-11 items-center rounded-xl border border-border bg-background px-3"><span class="mr-2 text-xs text-muted-foreground">$</span><input data-calc-monthly type="number" min="0" step="50" value="250" class="w-full bg-transparent text-sm font-semibold outline-none"></div></label>
                    <label class="block"><span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Time horizon</span><div class="mt-2 flex h-11 items-center rounded-xl border border-border bg-background px-3"><input data-calc-years type="number" min="1" max="50" step="1" value="5" class="w-full bg-transparent text-sm font-semibold outline-none"><span class="ml-2 text-xs text-muted-foreground">years</span></div></label>
                    <label class="block"><span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Assumed annual return</span><div class="mt-2 flex h-11 items-center rounded-xl border border-border bg-background px-3"><input data-calc-rate type="number" min="0" max="100" step="0.1" value="8" class="w-full bg-transparent text-sm font-semibold outline-none"><span class="ml-2 text-xs text-muted-foreground">%</span></div></label>
                </div>

                <div class="mt-6 grid gap-px overflow-hidden rounded-2xl border border-border bg-border sm:grid-cols-3">
                    <div class="bg-background p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Projected value</p><p data-calc-value class="mt-2 text-xl font-semibold tabular-nums">$0.00</p></div>
                    <div class="bg-background p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Contributions</p><p data-calc-contributions class="mt-2 text-xl font-semibold tabular-nums">$0.00</p></div>
                    <div class="bg-background p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Estimated growth</p><p data-calc-growth class="mt-2 text-xl font-semibold tabular-nums text-emerald-600">$0.00</p></div>
                </div>
                <p class="mt-4 text-[10px] leading-5 text-muted-foreground">This calculator is based only on the assumptions entered above. It is not a forecast, quote, recommendation or guarantee of investment performance.</p>
            </div>
        </div>

        <div id="platform" class="overflow-hidden rounded-[1.75rem] border border-border bg-card">
            <div class="border-b border-border p-6 sm:p-8">
                <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Built for financial operations</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em]">One interface. Specialist engines underneath.</h2>
                <p class="mt-3 text-sm leading-7 text-muted-foreground">Open the areas below to see how the platform keeps customer experience coherent while preserving domain authority.</p>
            </div>

            <div class="divide-y divide-border">
                @foreach([
                    ['Markets & execution','Stocks, Forex and Crypto share one market registry while execution routes remain asset-aware and auditable.','candlestick-chart'],
                    ['Intelligence & automation','Signal analysis, trading bots and copy trading expose intelligence and automation without collapsing their ownership rules.','radio-tower'],
                    ['Portfolio & investments','Liquid-market positions and private investment holdings remain separate accounting domains inside one customer portfolio.','briefcase-business'],
                    ['Access & operations','Memberships govern commercial access while administration, communication and financial controls remain independent authorities.','sliders-horizontal'],
                ] as $index => [$title,$copy,$icon])
                    <details class="home-accordion group" {{ $index === 0 ? 'open' : '' }}>
                        <summary class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5 sm:px-8">
                            <span class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-muted"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span><span class="text-sm font-semibold">{{ $title }}</span></span>
                            <i data-lucide="chevron-down" class="home-accordion-chevron h-4 w-4 shrink-0 text-muted-foreground transition-transform"></i>
                        </summary>
                        <div class="px-6 pb-5 pl-[4.5rem] text-xs leading-6 text-muted-foreground sm:px-8 sm:pb-6 sm:pl-[5rem]">{{ $copy }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if($latestNews->isNotEmpty())
<section class="border-y border-border bg-card/35">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-6"><div><p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">Market context</p><h2 class="mt-2 text-2xl font-semibold">Latest market coverage</h2></div><i data-lucide="newspaper" class="h-5 w-5 text-muted-foreground"></i></div>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach($latestNews->take(3) as $news)
                <article class="group rounded-2xl border border-border bg-background p-5 transition hover:-translate-y-0.5 hover:shadow-lg"><p class="text-[9px] font-semibold uppercase tracking-[.14em] text-muted-foreground">{{ $news->symbol ?? 'Market' }}</p><h3 class="mt-3 line-clamp-2 text-sm font-semibold leading-6">{{ $news->headline ?? $news->title }}</h3><p class="mt-3 line-clamp-3 text-xs leading-5 text-muted-foreground">{{ $news->summary }}</p><div class="mt-5 flex items-center gap-2 text-[10px] font-semibold text-muted-foreground">Market context <i data-lucide="arrow-up-right" class="h-3 w-3 transition group-hover:translate-x-0.5"></i></div></article>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="relative overflow-hidden text-white" style="background:linear-gradient(115deg,color-mix(in srgb,var(--brand-primary) 92%,#000),color-mix(in srgb,var(--brand-primary) 58%,#111827))">
    <div class="pointer-events-none absolute -right-24 -top-32 h-80 w-80 rounded-full border border-white/10 bg-white/[.04]"></div>
    <div class="relative mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div><p class="text-[10px] font-semibold uppercase tracking-[.18em] text-white/55">Financial workspace</p><h2 class="mt-2 max-w-3xl text-3xl font-semibold tracking-[-.04em]">Move from discovery to controlled financial action.</h2><p class="mt-3 max-w-2xl text-sm leading-6 text-white/65">Markets, intelligence, automation, portfolio tools and investment products remain one account away.</p></div>
        <a href="{{ $accountUrl }}" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 px-5 text-sm font-semibold shadow-lg transition hover:-translate-y-0.5 hover:bg-black/85" style="background:#0b0f16;color:#ffffff !important;box-shadow:0 18px 42px rgba(0,0,0,.22)"><span style="color:#ffffff">{{ auth()->check() ? 'Go to workspace' : 'Get started' }}</span><i data-lucide="arrow-right" class="h-4 w-4" style="color:#ffffff"></i></a>
    </div>
</section>

@push('scripts')
<script>
(() => {
    const carousel = document.querySelector('[data-market-carousel]');
    const cards = () => Array.from(carousel?.querySelectorAll('[data-market-card]:not([hidden])') || []);
    const step = (direction) => {
        if (!carousel) return;
        const card = cards()[0];
        if (!card) return;
        carousel.scrollBy({ left: direction * (card.getBoundingClientRect().width + 12), behavior: 'smooth' });
    };
    document.querySelector('[data-market-prev]')?.addEventListener('click', () => step(-1));
    document.querySelector('[data-market-next]')?.addEventListener('click', () => step(1));

    document.querySelectorAll('[data-market-filter]').forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.marketFilter;
            document.querySelectorAll('[data-market-filter]').forEach(item => {
                const active = item === button;
                item.dataset.active = active ? 'true' : 'false';
                item.style.background = active ? 'var(--brand-primary)' : '';
                item.style.borderColor = active ? 'var(--brand-primary)' : '';
                item.style.color = active ? '#fff' : '';
            });
            document.querySelectorAll('[data-market-card]').forEach(card => card.hidden = filter !== 'all' && card.dataset.asset !== filter);
            carousel?.scrollTo({ left: 0, behavior: 'smooth' });
        });
    });

    const firstFilter = document.querySelector('[data-market-filter="all"]');
    if (firstFilter) {
        firstFilter.style.background = 'var(--brand-primary)';
        firstFilter.style.borderColor = 'var(--brand-primary)';
        firstFilter.style.color = '#fff';
    }

    let marketTimer = null;
    const startMarketRotation = () => {
        if (!carousel || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        clearInterval(marketTimer);
        marketTimer = setInterval(() => {
            const visible = cards();
            if (visible.length < 2) return;
            const max = carousel.scrollWidth - carousel.clientWidth - 8;
            if (carousel.scrollLeft >= max) carousel.scrollTo({ left: 0, behavior: 'smooth' });
            else step(1);
        }, 4500);
    };
    carousel?.addEventListener('mouseenter', () => clearInterval(marketTimer));
    carousel?.addEventListener('mouseleave', startMarketRotation);
    startMarketRotation();

    const inventoryCarousel = document.querySelector('[data-inventory-carousel]');
    const inventoryStep = (direction) => {
        if (!inventoryCarousel) return;
        const card = inventoryCarousel.querySelector('.home-inventory-card');
        if (!card) return;
        inventoryCarousel.scrollBy({ left: direction * (card.getBoundingClientRect().width + 16), behavior: 'smooth' });
    };
    document.querySelector('[data-inventory-prev]')?.addEventListener('click', () => inventoryStep(-1));
    document.querySelector('[data-inventory-next]')?.addEventListener('click', () => inventoryStep(1));

    const calculator = document.querySelector('[data-investment-calculator]');
    if (calculator) {
        const principal = calculator.querySelector('[data-calc-principal]');
        const monthly = calculator.querySelector('[data-calc-monthly]');
        const years = calculator.querySelector('[data-calc-years]');
        const rate = calculator.querySelector('[data-calc-rate]');
        const value = calculator.querySelector('[data-calc-value]');
        const contributions = calculator.querySelector('[data-calc-contributions]');
        const growth = calculator.querySelector('[data-calc-growth]');
        const money = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
        const calculate = () => {
            const p = Math.max(0, Number(principal.value) || 0);
            const m = Math.max(0, Number(monthly.value) || 0);
            const y = Math.min(50, Math.max(1, Number(years.value) || 1));
            const annual = Math.max(0, Number(rate.value) || 0) / 100;
            const months = Math.round(y * 12);
            const monthlyRate = annual / 12;
            const principalFuture = p * Math.pow(1 + monthlyRate, months);
            const recurringFuture = monthlyRate > 0 ? m * ((Math.pow(1 + monthlyRate, months) - 1) / monthlyRate) : m * months;
            const projected = principalFuture + recurringFuture;
            const contributed = p + (m * months);
            value.textContent = money.format(projected);
            contributions.textContent = money.format(contributed);
            growth.textContent = money.format(Math.max(0, projected - contributed));
        };
        [principal, monthly, years, rate].forEach(input => input?.addEventListener('input', calculate));
        calculate();
    }
})();
</script>
@endpush
@endsection

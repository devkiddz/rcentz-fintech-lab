@extends('layouts.main')

@section('content')
@php
    $taglineDefault = 'Markets, intelligence and financial control.';
    $descriptionDefault = 'A modern financial platform for markets, portfolio management, intelligent signals, automation and private investments.';
    $configuredTagline = trim((string) setting('site_tagline', $taglineDefault));
    $configuredDescription = trim((string) setting('site_description', $descriptionDefault));
    $tagline = $configuredTagline !== '' && $configuredTagline !== $taglineDefault
        ? $configuredTagline
        : localize('ui.r3.home.tagline_default', $taglineDefault);
    $description = $configuredDescription !== '' && $configuredDescription !== $descriptionDefault
        ? $configuredDescription
        : localize('ui.r3.home.description_default', $descriptionDefault);
    $company = setting('company_name', site_name());
    $accountUrl = auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) : route('register');
    $marketUrl = auth()->check() ? route('instruments.index') : route('login');
    $investmentUrl = auth()->check() ? route('investments.index') : route('login');
    $botUrl = auth()->check() ? route('ai-bots.marketplace') : route('login');
    $copyUrl = auth()->check() ? route('copy-trading.marketplace') : route('login');
    $signalUrl = auth()->check() ? route('signals.index') : route('login');

    $sparklinePoints = function ($quotes, int $width = 300, int $height = 96) {
        $values = collect($quotes ?? [])
            ->map(fn ($value) => is_array($value) ? ($value['price'] ?? null) : $value)
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

<style>
    .home-proof-rich{min-height:40rem}
    .home-proof-chart{background:linear-gradient(180deg,rgba(255,255,255,.028),rgba(255,255,255,.008))}
    @media(max-width:1023px){.home-proof-rich{min-height:auto}}
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
                {{ localize('ui.r3.home.badge', 'Markets · Intelligence · Controlled execution') }}
            </div>

            <p class="mt-8 text-xs font-semibold uppercase tracking-[.2em] text-white/60">{{ $company }}</p>
            <h1 class="mt-3 max-w-3xl text-5xl font-semibold tracking-[-.06em] sm:text-6xl lg:text-[4.6rem] lg:leading-[.93]">{{ $tagline }}</h1>
            <p class="mt-6 max-w-2xl text-base leading-7 text-white/65 sm:text-lg">{{ $description }}</p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $accountUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:brightness-110" style="background:var(--brand-primary);box-shadow:0 18px 44px color-mix(in srgb,var(--brand-primary) 24%,transparent)"><i data-lucide="arrow-up-right" class="h-4 w-4"></i>{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.nav.create_account', 'Create account') }}</a>
                <a href="#markets" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/[.04] px-5 text-sm font-semibold text-white/90 backdrop-blur transition hover:bg-white/[.08]">{{ localize('ui.r3.home.hero_explore', 'Explore the platform') }}<i data-lucide="chevron-down" class="h-4 w-4"></i></a>
            </div>

            <div class="mt-10 grid max-w-2xl grid-cols-3 overflow-hidden rounded-2xl border border-white/10 bg-black/15 backdrop-blur">
                @foreach([
                    [localize('ui.r3.home.metric.instruments', 'Market instruments'), $platformStats['instruments'] ?? 0],
                    [localize('ui.r3.home.metric.signals', 'Active signals'), $platformStats['signals'] ?? 0],
                    [localize('ui.r3.home.metric.investments', 'Investment products'), $platformStats['investments'] ?? 0],
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
                            <p class="text-[11px] font-semibold uppercase tracking-[.16em] text-white/60">{{ localize('ui.r3.home.snapshot_eyebrow', 'Market activity') }}</p>
                        </div>
                        <h2 class="mt-2 text-xl font-semibold tracking-[-.025em] sm:text-2xl">{{ localize('ui.r3.home.snapshot_title', 'Tesla, Gold and Bitcoin at a glance.') }}</h2>
                        <p class="mt-2 max-w-lg text-[13px] leading-6 text-white/55">{{ localize('ui.r3.home.snapshot_copy', 'Current prices, market movement, ranges and recent history for Tesla, Gold and Bitcoin.') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 lg:min-w-[15rem]">
                        <div class="home-pulse-chip rounded-xl px-3 py-2.5"><p class="text-[9px] font-semibold uppercase tracking-[.11em] text-white/45">{{ localize('ui.r3.home.snapshot_real_data', 'Market data') }}</p><p class="mt-1 text-[12px] font-semibold text-emerald-300">{{ localize('ui.r3.home.market_status.available', 'Available') }}</p></div>
                        <div class="home-pulse-chip rounded-xl px-3 py-2.5"><p class="text-[9px] font-semibold uppercase tracking-[.11em] text-white/45">{{ localize('ui.r3.home.featured_assets', 'Selected markets') }}</p><p class="mt-1 text-[12px] font-semibold text-white/85">3</p></div>
                    </div>
                </div>

                <div class="relative p-4 sm:p-5">
                    <div class="grid gap-3 lg:grid-cols-3">
                        @foreach($heroMarkets as $market)
                            @php
                                $available = (bool) ($market['available'] ?? false);
                                $points = $available ? $sparklinePoints($market['quotes'] ?? [], 320, 112) : '';
                                $pulseAccent = match($market['asset_class'] ?? '') {
                                    'stock' => '#fb7185',
                                    'commodity' => '#fbbf24',
                                    'crypto' => '#a78bfa',
                                    default => '#38bdf8',
                                };
                                $assetLabel = localize('ui.r3.home.asset.'.($market['asset_class'] ?? 'market'), $market['asset_label'] ?? 'Market');
                                $statusKey = 'ui.r3.home.market_status.'.($market['status'] ?? 'available');
                                $trend = strtolower((string) ($market['trend'] ?? 'neutral'));
                                $trendKey = 'ui.r3.home.proof.trend_'.$trend;
                                $rangeIs20d = ($market['range_kind'] ?? '') === '20d';
                                $activityCode = (string) ($market['activity_code'] ?? 'volume');
                                $updated = !empty($market['updated_at']) ? \Carbon\Carbon::parse($market['updated_at'])->format('M j · H:i') : '—';
                            @endphp
                            <article class="home-pulse-card home-proof-rich group rounded-[1.45rem] p-4 sm:p-5" style="--pulse-accent:{{ $pulseAccent }}">
                                @if($available)
                                    <div class="relative flex h-full flex-col">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <span class="rounded-full px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.12em]" style="background:color-mix(in srgb,var(--pulse-accent) 11%,transparent);color:color-mix(in srgb,var(--pulse-accent) 72%,white);box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--pulse-accent) 16%,transparent)">{{ $assetLabel }}</span>
                                                <h3 class="mt-4 truncate text-xl font-semibold tracking-[-.035em] sm:text-2xl">{{ $market['symbol'] }}</h3>
                                                <p class="mt-1 truncate text-[11px] text-white/52">{{ $market['name'] }}</p>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="text-[9px] font-semibold uppercase tracking-[.11em] text-white/45">{{ localize('ui.r3.home.proof.current', 'Current') }}</p>
                                                <p class="mt-1.5 text-lg font-semibold tabular-nums sm:text-xl">{{ $market['price_display'] }}</p>
                                                <div class="mt-1 flex items-center justify-end gap-1.5 text-[11px] font-semibold {{ ($market['change'] ?? 0) >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                                    <span>{{ $market['change_amount_display'] ?? '' }}</span>
                                                    <span>{{ ($market['change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format((float) ($market['change'] ?? 0),2) }}%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="home-pulse-card-chart home-proof-chart mt-5 h-32 overflow-hidden rounded-xl sm:h-36">
                                            @if($points)
                                                <svg viewBox="0 0 320 112" preserveAspectRatio="none" class="relative z-[1] h-full w-full" role="img" aria-label="{{ $market['symbol'] }} recent market history">
                                                    <defs>
                                                        <linearGradient id="proofFill{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="0%" stop-color="{{ ($market['change'] ?? 0) >= 0 ? '#34d399' : '#fb7185' }}" stop-opacity=".18" />
                                                            <stop offset="100%" stop-color="{{ ($market['change'] ?? 0) >= 0 ? '#34d399' : '#fb7185' }}" stop-opacity="0" />
                                                        </linearGradient>
                                                    </defs>
                                                    <line x1="0" y1="28" x2="320" y2="28" stroke="rgba(255,255,255,.045)" stroke-width="1" />
                                                    <line x1="0" y1="56" x2="320" y2="56" stroke="rgba(255,255,255,.045)" stroke-width="1" />
                                                    <line x1="0" y1="84" x2="320" y2="84" stroke="rgba(255,255,255,.045)" stroke-width="1" />
                                                    <polygon points="0,112 {{ $points }} 320,112" fill="url(#proofFill{{ $loop->index }})" />
                                                    <polyline points="{{ $points }}" fill="none" stroke="{{ ($market['change'] ?? 0) >= 0 ? '#34d399' : '#fb7185' }}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                                </svg>
                                            @else
                                                <div class="relative z-[1] flex h-full items-center justify-center text-[11px] text-white/45">{{ localize('ui.r3.home.proof.history_building', 'Price history is building') }}</div>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid grid-cols-3 gap-2">
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5">
                                                <p class="text-[9px] uppercase tracking-[.1em] text-white/45">{{ localize('ui.r3.home.proof.trend', 'Trend') }}</p>
                                                <p class="mt-1.5 text-[12px] font-semibold text-white/86">{{ localize($trendKey, ucfirst($trend)) }}</p>
                                            </div>
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5">
                                                <p class="text-[9px] uppercase tracking-[.1em] text-white/45">{{ localize('ui.r3.home.proof.momentum', 'Momentum') }}</p>
                                                <p class="mt-1.5 text-[12px] font-semibold {{ ($market['momentum_percent'] ?? 0) >= 0 ? 'text-emerald-300' : 'text-red-300' }}">{{ ($market['momentum_percent'] ?? 0) >= 0 ? '+' : '' }}{{ number_format((float) ($market['momentum_percent'] ?? 0),1) }}%</p>
                                            </div>
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5">
                                                <p class="text-[9px] uppercase tracking-[.1em] text-white/45">{{ localize('ui.r3.home.proof.'.($activityCode === 'volume' ? 'volume' : 'spot_feed'), $activityCode === 'volume' ? 'Volume' : 'Spot feed') }}</p>
                                                <p class="mt-1.5 truncate text-[12px] font-semibold text-white/86">{{ $market['activity_value'] ?? '—' }}</p>
                                                @if(!empty($market['activity_meta_value']))<p class="mt-0.5 text-[9px] text-white/48">{{ $market['activity_meta_value'] }} {{ localize('ui.r3.home.proof.vs_average', 'vs avg') }}</p>@endif
                                            </div>
                                        </div>

                                        <div class="mt-3 rounded-xl bg-white/[.025] px-3 py-3 ring-1 ring-inset ring-white/[.045]">
                                            <div class="flex items-center justify-between gap-3 text-[9px] uppercase tracking-[.09em] text-white/46">
                                                <span>{{ $rangeIs20d ? localize('ui.r3.home.proof.range_20d', '20D range') : localize('ui.r3.home.proof.day_range', 'Day range') }}</span>
                                                <span class="text-white/48">{{ $market['range_low_display'] ?? '—' }} — {{ $market['range_high_display'] ?? '—' }}</span>
                                            </div>
                                            <div class="relative mt-2 h-1.5 rounded-full bg-white/[.07]">
                                                <div class="absolute inset-y-0 left-0 rounded-full" style="width:{{ number_format((float) ($market['range_position'] ?? 50),2,'.','') }}%;background:linear-gradient(90deg,color-mix(in srgb,var(--pulse-accent) 35%,transparent),var(--pulse-accent))"></div>
                                                <span class="absolute top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/60" style="left:{{ number_format((float) ($market['range_position'] ?? 50),2,'.','') }}%;background:var(--pulse-accent);box-shadow:0 0 12px color-mix(in srgb,var(--pulse-accent) 60%,transparent)"></span>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[9px] uppercase tracking-[.1em] text-white/43">{{ localize('ui.r3.home.proof.previous', 'Previous close') }}</p><p class="mt-1.5 truncate text-[11px] font-semibold text-white/78">{{ $market['previous_display'] ?? '—' }}</p></div>
                                            @if($rangeIs20d)
                                                <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[9px] uppercase tracking-[.1em] text-white/43">{{ localize('ui.r3.home.proof.range_20d', '20D range') }}</p><p class="mt-1.5 truncate text-[11px] font-semibold text-white/78">{{ $market['range_low_display'] ?? '—' }} — {{ $market['range_high_display'] ?? '—' }}</p></div>
                                            @else
                                                <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[9px] uppercase tracking-[.1em] text-white/43">{{ localize('ui.r3.home.proof.open', 'Open') }}</p><p class="mt-1.5 truncate text-[11px] font-semibold text-white/78">{{ $market['open_display'] ?? '—' }}</p></div>
                                                <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[9px] uppercase tracking-[.1em] text-white/43">{{ localize('ui.r3.home.proof.high', 'High') }}</p><p class="mt-1.5 truncate text-[11px] font-semibold text-white/78">{{ $market['high_display'] ?? '—' }}</p></div>
                                                <div class="home-pulse-metric rounded-xl px-3 py-2.5"><p class="text-[9px] uppercase tracking-[.1em] text-white/43">{{ localize('ui.r3.home.proof.low', 'Low') }}</p><p class="mt-1.5 truncate text-[11px] font-semibold text-white/78">{{ $market['low_display'] ?? '—' }}</p></div>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-white/[.06] pt-3">
                                            <div><p class="text-[10px] uppercase tracking-[.1em] text-white/48">{{ localize('ui.r3.home.proof.support', 'Support') }}</p><p class="mt-1 text-[11px] font-semibold text-white/72">{{ $market['support_display'] ?? '—' }}</p></div>
                                            <div class="text-right"><p class="text-[10px] uppercase tracking-[.1em] text-white/48">{{ localize('ui.r3.home.proof.resistance', 'Resistance') }}</p><p class="mt-1 text-[11px] font-semibold text-white/72">{{ $market['resistance_display'] ?? '—' }}</p></div>
                                        </div>

                                        <div class="mt-auto flex items-center justify-between gap-3 pt-4 text-[10px] text-white/48">
                                            <span>{{ localize($statusKey, ucfirst(str_replace('_', ' ', $market['status'] ?? 'available'))) }}</span>
                                            <span>{{ localize('ui.r3.home.proof.updated', 'Updated') }} {{ $updated }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="relative flex min-h-[31rem] flex-col items-center justify-center px-4 text-center">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/[.05] text-white/55 ring-1 ring-inset ring-white/[.08]"><i data-lucide="activity" class="h-5 w-5"></i></span>
                                        <p class="mt-5 text-[10px] font-semibold uppercase tracking-[.12em]" style="color:color-mix(in srgb,var(--pulse-accent) 72%,white)">{{ $assetLabel }}</p>
                                        <h3 class="mt-2 text-2xl font-semibold">{{ $market['symbol'] }}</h3>
                                        <p class="mt-1 text-[11px] text-white/52">{{ $market['name'] }}</p>
                                        <p class="mt-5 max-w-[16rem] text-[12px] leading-5 text-white/50">{{ localize('ui.r3.home.proof.data_warming', 'Market data is not available for this instrument yet. This card will update when the feed is available.') }}</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-col gap-2 rounded-xl bg-black/15 px-4 py-3 text-[10px] text-white/48 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ localize('ui.r3.home.proof.fixed_assets', 'Tesla · Gold · Bitcoin') }}</span>
                        <span class="font-semibold text-white/45">{{ localize('ui.r3.home.proof.no_fake_data', 'Price history · market activity · key levels') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if($marketTape->isNotEmpty())
<section class="market-tape overflow-hidden border-b border-border bg-card/95" aria-label="{{ localize('ui.r3.home.snapshot_real_data', 'Market data') }}">
    <div class="flex items-stretch">
        <div class="hidden shrink-0 items-center px-5 text-[9px] font-semibold uppercase tracking-[.16em] text-white sm:flex" style="background:var(--brand-primary)">{{ localize('ui.r3.home.snapshot_real_data', 'Market data') }}</div>
        <div class="min-w-0 flex-1 overflow-hidden">
            <div class="market-tape-track">
                @foreach([1,2] as $copy)
                    <div class="flex shrink-0 items-center" aria-hidden="{{ $copy === 2 ? 'true' : 'false' }}">
                        @foreach($marketTape as $market)
                            <div class="flex min-w-max items-center gap-3 border-r border-border px-5 py-3">
                                <span class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r3.home.asset.'.($market['asset_class'] ?? 'market'), $market['asset_label'] ?? 'Market') }}</span>
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
            <p class="text-[11px] font-semibold uppercase tracking-[.15em]" style="color:var(--brand-primary)">{{ localize('ui.r3.home.snapshot_eyebrow', 'Market activity') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em] sm:text-4xl">{{ localize('ui.r3.home.proof_markets', 'Stocks · Forex · Commodities · Crypto') }}</h2>
            <p class="mt-4 text-base leading-7 text-muted-foreground">{{ localize('ui.r3.home.market_universe_copy', 'Browse Stocks, Forex, Commodities and Crypto from the platform’s market catalogue.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" data-market-prev class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card hover:bg-muted" aria-label="Previous markets"><i data-lucide="arrow-left" class="h-4 w-4"></i></button>
            <button type="button" data-market-next class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card hover:bg-muted" aria-label="Next markets"><i data-lucide="arrow-right" class="h-4 w-4"></i></button>
        </div>
    </div>

    <div class="mt-7 flex flex-wrap gap-2" data-market-filters>
        @foreach([
            ['all','ui.r3.home.market_filter.all','All markets'],
            ['stock','ui.r3.home.market_filter.stocks','Stocks'],
            ['forex','ui.r3.home.market_filter.forex','Forex'],
            ['commodity','ui.r3.home.market_filter.commodities','Commodities'],
            ['crypto','ui.r3.home.market_filter.crypto','Crypto'],
        ] as [$filter,$key,$label])
            <button type="button"
                    data-market-filter="{{ $filter }}"
                    class="rounded-full border border-border px-3.5 py-2 text-[10px] font-semibold uppercase tracking-[.12em] text-muted-foreground transition hover:bg-muted"
                    data-active="{{ $filter === 'all' ? 'true' : 'false' }}">
                {{ localize($key, $label) }}
            </button>
        @endforeach
    </div>

    <div class="home-carousel mt-5 flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2" data-market-carousel>
        @forelse($marketShowcase as $market)
            @php $marketPoints = $sparklinePoints($market['quotes'] ?? [], 280, 78); @endphp
            <article class="home-market-card snap-start overflow-hidden rounded-2xl border border-border bg-card transition hover:-translate-y-0.5 hover:shadow-lg"
                     data-market-card
                     data-asset="{{ $market['asset_class'] ?? 'market' }}">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <span class="rounded-full bg-muted px-2.5 py-1 text-[8px] font-semibold uppercase tracking-[.14em] text-muted-foreground">
                                {{ localize('ui.r3.home.asset.'.($market['asset_class'] ?? 'market'), $market['asset_label'] ?? 'Market') }}
                            </span>
                            <h3 class="mt-4 text-xl font-semibold">{{ $market['symbol'] }}</h3>
                            <p class="mt-1 line-clamp-1 text-xs text-muted-foreground">{{ $market['name'] }}</p>
                        </div>
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-border bg-background">
                            <i data-lucide="{{ $market['icon'] ?? 'chart-no-axes-combined' }}" class="h-4 w-4"></i>
                        </div>
                    </div>

                    <div class="mt-6 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Market price</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $market['price_display'] }}</p>
                        </div>
                        <p class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $market['change'] >= 0 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-red-500/10 text-red-600' }}">
                            {{ $market['change'] >= 0 ? '+' : '' }}{{ number_format($market['change'],2) }}%
                        </p>
                    </div>

                    <div class="mt-5 h-20 overflow-hidden rounded-xl border border-border/70 bg-background/55 px-2 py-2">
                        @if($marketPoints)
                            <svg viewBox="0 0 280 78" preserveAspectRatio="none" class="h-full w-full" role="img" aria-label="{{ $market['symbol'] }} recent price history">
                                <polyline points="{{ $marketPoints }}" fill="none" stroke="{{ $market['change'] >= 0 ? '#059669' : '#e11d48' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                            </svg>
                        @else
                            <div class="flex h-full items-center justify-center text-[9px] text-muted-foreground">{{ localize('ui.r3.home.proof.history_building', 'Price history is building') }}</div>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-border pt-3">
                        <div>
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.r3.home.proof.previous', 'Previous close') }}</p>
                            <p class="mt-1 text-[11px] font-semibold tabular-nums">{{ $market['previous_display'] ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">History</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ number_format(count($market['quotes'] ?? [])) }} points</p>
                        </div>
                    </div>
                </div>
                <div class="h-1 w-full" style="background:linear-gradient(90deg,var(--brand-primary),transparent)"></div>
            </article>
        @empty
            <div class="w-full rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">{{ localize('ui.r3.home.snapshot_empty', 'Configured market instruments will appear here when data is available.') }}</div>
        @endforelse
    </div>

    <div class="mt-5">
        <a href="{{ $marketUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold hover:text-muted-foreground">
            {{ localize('ui.r3.home.card.markets_cta', 'Explore markets') }}
            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
        </a>
    </div>
</section>

<section id="opportunities" class="border-y border-border bg-card/35">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-3xl">
            <p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">{{ localize('ui.r3.home.platform_eyebrow', 'One financial workspace') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-[-.04em] sm:text-4xl">{{ localize('ui.r3.home.platform_title', 'The platform is broad. The homepage does not have to be.') }}</h2>
            <p class="mt-4 text-sm leading-7 text-muted-foreground">{{ localize('ui.r3.home.platform_copy', 'Each product has its own workspace and authority. This page gives you the map; the account gives you the depth.') }}</p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['chart-candlestick','#0ea5e9','ui.r3.home.card.markets_title','Markets & trading','ui.r3.home.card.markets_copy','Research Stocks, Forex, Commodities and Crypto, with trading routed only through supported execution adapters.','ui.r3.home.card.markets_cta','Explore markets',$marketUrl,$platformStats['instruments'] ?? 0,'ui.r3.home.metric.instruments','Market instruments'],
                ['radio-tower','#8b5cf6','ui.r3.home.card.signals_title','Signal intelligence','ui.r3.home.card.signals_copy','Receive structured market analysis with delivery, revision and lifecycle tracking kept separate from trade execution.','ui.r3.home.card.signals_cta','Open signals',$signalUrl,$platformStats['signals'] ?? 0,'ui.r3.home.metric.signals','Active signals'],
                ['gem','#f59e0b','ui.r3.home.card.investments_title','Private investments','ui.r3.home.card.investments_copy','Evaluate investment products with their own pricing, duration, risk and account records instead of mixing them with brokerage positions.','ui.r3.home.card.investments_cta','Explore investments',$investmentUrl,$platformStats['investments'] ?? 0,'ui.r3.home.metric.investments','Investment products'],
                ['bot','#10b981','ui.r3.home.card.automation_title','Bots & copy trading','ui.r3.home.card.automation_copy','Run configured bot strategies or follow public strategies while preserving allocation and broker controls.','ui.r3.home.card.automation_cta','Explore automation',$botUrl,($platformStats['bots'] ?? 0)+($platformStats['copy_strategies'] ?? 0),'ui.r3.home.metric.automation','Automation products'],
            ] as [$icon,$accent,$titleKey,$title,$copyKey,$copy,$ctaKey,$cta,$url,$count,$metricKey,$metric])
                <article class="home-investment-card group rounded-[1.75rem] border border-border bg-background" style="--investment-accent:{{ $accent }};min-height:22rem">
                    <div class="relative flex h-full flex-col p-5 sm:p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl border" style="border-color:color-mix(in srgb,var(--investment-accent) 24%,transparent);background:color-mix(in srgb,var(--investment-accent) 11%,transparent);color:var(--investment-accent)"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                        <h3 class="mt-6 text-2xl font-semibold tracking-[-.035em]">{{ localize($titleKey, $title) }}</h3>
                        <p class="mt-3 text-xs leading-6 text-muted-foreground">{{ localize($copyKey, $copy) }}</p>
                        <div class="mt-5 rounded-2xl border border-border bg-card/70 p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ localize($metricKey, $metric) }}</p><p class="mt-1.5 text-xl font-semibold">{{ number_format($count) }}</p></div>
                        <a href="{{ $url }}" class="mt-auto inline-flex items-center justify-between gap-3 pt-6 text-xs font-semibold"><span>{{ localize($ctaKey, $cta) }}</span><span class="flex h-8 w-8 items-center justify-center rounded-full border border-border bg-card transition group-hover:translate-x-0.5" style="color:var(--investment-accent)"><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="systems" class="relative overflow-hidden bg-background">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true"><div class="absolute -left-24 top-8 h-72 w-72 rounded-full" style="background:color-mix(in srgb,var(--brand-primary) 10%,transparent);filter:blur(105px)"></div><div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-border/70 to-transparent"></div></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-end">
            <div><p class="text-[10px] font-semibold uppercase tracking-[.18em]" style="color:var(--brand-primary)">{{ localize('ui.r3.home.control_eyebrow', 'Control before complexity') }}</p><h2 class="mt-3 text-3xl font-semibold tracking-[-.045em] text-foreground sm:text-4xl">{{ localize('ui.r3.home.control_title', 'Different financial actions should not share one vague authority.') }}</h2></div>
            <p class="max-w-2xl text-sm leading-7 text-muted-foreground lg:justify-self-end">{{ localize('ui.r3.home.control_copy', 'The platform separates analysis, execution and investment ownership so customers can understand what each action actually does.') }}</p>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-2">
            @foreach([
                ['radio-tower','#8b5cf6','ui.r3.home.control.analysis_title','Analysis remains analysis','ui.r3.home.control.analysis_copy','Signals can be published, adjusted or closed without silently creating a trade.'],
                ['route','#0ea5e9','ui.r3.home.control.execution_title','Execution has a defined route','ui.r3.home.control.execution_copy','Direct trades, bot trades and copied trades enter controlled brokerage execution instead of bypassing it.'],
                ['notebook-tabs','#f59e0b','ui.r3.home.control.records_title','Financial records stay separated','ui.r3.home.control.records_copy','Broker positions, private investments and money activity keep their own records and lifecycle authority.'],
                ['shield-check','#10b981','ui.r3.home.control.account_title','The customer remains the account authority','ui.r3.home.control.account_copy','Access, allocation and account actions remain visible and attributable to the customer relationship.'],
            ] as [$icon,$accent,$titleKey,$title,$copyKey,$copy])
                <article class="home-system-card rounded-[1.8rem] p-6" style="--system-accent:{{ $accent }};min-height:auto">
                    <div class="flex items-start gap-4"><span class="home-system-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span><div><h3 class="text-lg font-semibold tracking-[-.025em]">{{ localize($titleKey, $title) }}</h3><p class="mt-2 text-sm leading-6 text-muted-foreground">{{ localize($copyKey, $copy) }}</p></div></div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="relative overflow-hidden text-white" style="background:linear-gradient(115deg,color-mix(in srgb,var(--brand-primary) 92%,#000),color-mix(in srgb,var(--brand-primary) 58%,#111827))">
    <div class="pointer-events-none absolute -right-24 -top-32 h-80 w-80 rounded-full border border-white/10 bg-white/[.04]"></div>
    <div class="relative mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div><p class="text-[10px] font-semibold uppercase tracking-[.18em] text-white/55">{{ localize('ui.r3.home.cta_eyebrow', 'Your financial workspace') }}</p><h2 class="mt-2 max-w-3xl text-3xl font-semibold tracking-[-.04em]">{{ localize('ui.r3.home.cta_title', 'Move from market discovery to controlled financial action.') }}</h2><p class="mt-3 max-w-2xl text-sm leading-6 text-white/65">{{ localize('ui.r3.home.cta_copy', 'Open one account for markets, signals, trading, investments and automation without turning every product into the same workflow.') }}</p></div>
        <a href="{{ $accountUrl }}" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl border border-white/15 px-5 text-sm font-semibold shadow-lg transition hover:-translate-y-0.5 hover:bg-black/85" style="background:#0b0f16;color:#ffffff !important;box-shadow:0 18px 42px rgba(0,0,0,.22)"><span style="color:#ffffff">{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.nav.create_account', 'Create account') }}</span><i data-lucide="arrow-right" class="h-4 w-4" style="color:#ffffff"></i></a>
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

            document.querySelectorAll('[data-market-card]').forEach(card => {
                card.hidden = filter !== 'all' && card.dataset.asset !== filter;
            });

            carousel?.scrollTo({ left: 0, behavior: 'smooth' });
        });
    });

    const allFilter = document.querySelector('[data-market-filter="all"]');
    if (allFilter) {
        allFilter.style.background = 'var(--brand-primary)';
        allFilter.style.borderColor = 'var(--brand-primary)';
        allFilter.style.color = '#fff';
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
})();
</script>
@endpush
@endsection

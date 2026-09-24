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
    $automotiveUrl = route('cars.browse');
    $registerUrl = route('register');
    $workspaceUrl = auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')) : $registerUrl;

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

    /* V1 release hero: all visual accents derive from the configured theme. */
    .release-hero{
        --hero-primary:var(--brand-primary);
        --hero-secondary:var(--brand-secondary);
        position:relative;
        overflow:hidden;
        background:
            radial-gradient(circle at 78% 24%,color-mix(in srgb,var(--hero-primary) 13%,transparent),transparent 34%),
            radial-gradient(circle at 84% 78%,color-mix(in srgb,var(--hero-secondary) 9%,transparent),transparent 30%),
            hsl(var(--background));
    }
    .dark .release-hero{
        background:
            radial-gradient(circle at 78% 24%,color-mix(in srgb,var(--hero-primary) 22%,transparent),transparent 36%),
            radial-gradient(circle at 84% 78%,color-mix(in srgb,var(--hero-secondary) 14%,transparent),transparent 32%),
            hsl(var(--background));
    }
    .release-hero::before{
        content:"";
        position:absolute;
        inset:0;
        pointer-events:none;
        background-image:
            linear-gradient(hsl(var(--border)/.12) 1px,transparent 1px),
            linear-gradient(90deg,hsl(var(--border)/.12) 1px,transparent 1px);
        background-size:56px 56px;
        mask-image:linear-gradient(to bottom,rgba(0,0,0,.46),transparent 88%);
    }
    .release-hero-shell{
        position:relative;
        min-height:34rem;
    }
    .release-hero-slide{
        position:absolute;
        inset:0;
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transform:translateX(18px);
        transition:opacity .42s ease,transform .42s ease,visibility .42s ease;
    }
    .release-hero-slide[data-active="true"]{
        position:relative;
        opacity:1;
        visibility:visible;
        pointer-events:auto;
        transform:none;
    }
    .release-hero-layout{
        display:grid;
        gap:2rem;
        align-items:center;
    }
    @media(min-width:760px){
        .release-hero-layout{
            grid-template-columns:minmax(0,.86fr) minmax(0,1.14fr);
            gap:2.4rem;
        }
    }
    .release-hero-copy{max-width:35rem}
    .release-hero-title{
        font-size:clamp(2.5rem,5.2vw,4.75rem);
        line-height:.98;
        letter-spacing:-.058em;
        color:hsl(var(--foreground));
    }
    @media(min-width:760px) and (max-width:1080px){
        .release-hero-title{font-size:clamp(2.65rem,4.9vw,3.8rem)}
    }
    .release-hero-primary-text{color:var(--hero-primary)}
    .release-hero-secondary-text{color:var(--hero-secondary)}
    .release-hero-primary-button{
        background:var(--hero-primary);
        color:#fff;
        box-shadow:0 14px 34px color-mix(in srgb,var(--hero-primary) 20%,transparent);
        transition:transform .2s ease,filter .2s ease,box-shadow .2s ease;
    }
    .release-hero-primary-button:hover{
        transform:translateY(-2px);
        filter:brightness(1.06);
        box-shadow:0 18px 40px color-mix(in srgb,var(--hero-primary) 26%,transparent);
    }
    .release-hero-secondary-button{
        border:1px solid hsl(var(--border));
        background:hsl(var(--card)/.76);
        color:hsl(var(--foreground));
        transition:transform .2s ease,border-color .2s ease,background .2s ease;
    }
    .release-hero-secondary-button:hover{
        transform:translateY(-2px);
        border-color:color-mix(in srgb,var(--hero-primary) 42%,hsl(var(--border)));
        background:color-mix(in srgb,var(--hero-primary) 5%,hsl(var(--card)));
    }

    /* Laptop / product illustration */
    .release-device-wrap{
        position:relative;
        min-width:0;
        padding:.75rem .25rem 1.35rem;
    }
    .release-device-glow{
        position:absolute;
        inset:10% 5% 10% 12%;
        border-radius:9999px;
        background:color-mix(in srgb,var(--hero-primary) 18%,transparent);
        filter:blur(66px);
        pointer-events:none;
    }
    .release-device{
        position:relative;
        width:100%;
        max-width:43rem;
        margin-inline:auto;
        transform:perspective(1200px) rotateY(-3.5deg) rotateX(1deg);
        transform-origin:center;
    }
    .release-device-screen{
        position:relative;
        overflow:hidden;
        border:1px solid color-mix(in srgb,var(--hero-primary) 24%,hsl(var(--border)));
        border-radius:1.35rem;
        background:hsl(var(--card));
        box-shadow:0 28px 72px rgba(15,23,42,.20),inset 0 1px 0 hsl(var(--background)/.65);
    }
    .dark .release-device-screen{
        box-shadow:0 30px 76px rgba(0,0,0,.38),inset 0 1px 0 rgba(255,255,255,.05);
    }
    .release-device-base{
        width:92%;
        height:.72rem;
        margin:-.08rem auto 0;
        border-radius:0 0 9999px 9999px;
        background:linear-gradient(180deg,color-mix(in srgb,hsl(var(--foreground)) 18%,hsl(var(--card))),hsl(var(--muted)));
        box-shadow:0 9px 22px rgba(15,23,42,.12);
    }
    .release-device-hinge{
        width:18%;
        height:.22rem;
        margin:0 auto;
        border-radius:9999px;
        background:color-mix(in srgb,var(--hero-primary) 18%,hsl(var(--border)));
    }
    .release-screen-top{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:1rem;
        border-bottom:1px solid hsl(var(--border)/.75);
        padding:.68rem .85rem;
    }
    .release-screen-body{
        padding:.85rem;
    }
    .release-ui-panel{
        border:1px solid hsl(var(--border)/.72);
        background:hsl(var(--background)/.62);
        border-radius:1rem;
    }
    .dark .release-ui-panel{
        background:rgba(255,255,255,.025);
        border-color:rgba(255,255,255,.06);
    }
    .release-ui-icon{
        color:var(--hero-primary);
        background:color-mix(in srgb,var(--hero-primary) 9%,transparent);
        box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--hero-primary) 14%,transparent);
    }
    .release-chart-line{
        fill:none;
        stroke:var(--hero-primary);
        stroke-width:3;
        stroke-linecap:round;
        stroke-linejoin:round;
        vector-effect:non-scaling-stroke;
    }

    /* Carousel selectors */
    .release-hero-tabs{
        display:grid;
        gap:.65rem;
        grid-template-columns:repeat(3,minmax(0,1fr));
        margin-top:1.5rem;
    }
    .release-hero-tab{
        min-width:0;
        border:1px solid hsl(var(--border)/.85);
        background:hsl(var(--card)/.68);
        border-radius:1rem;
        padding:.75rem;
        text-align:left;
        transition:transform .2s ease,border-color .2s ease,background .2s ease,box-shadow .2s ease;
    }
    .release-hero-tab:hover{transform:translateY(-2px)}
    .release-hero-tab[data-active="true"]{
        border-color:color-mix(in srgb,var(--hero-primary) 58%,hsl(var(--border)));
        background:color-mix(in srgb,var(--hero-primary) 6%,hsl(var(--card)));
        box-shadow:0 10px 26px color-mix(in srgb,var(--hero-primary) 10%,transparent);
    }
    .release-hero-number{
        display:flex;
        height:2rem;
        width:2rem;
        align-items:center;
        justify-content:center;
        border-radius:.65rem;
        border:1px solid hsl(var(--border));
        color:hsl(var(--foreground));
        font-size:.68rem;
        font-weight:700;
    }
    .release-hero-tab[data-active="true"] .release-hero-number{
        border-color:color-mix(in srgb,var(--hero-primary) 50%,hsl(var(--border)));
        color:var(--hero-primary);
    }

    @media(max-width:759px){
        .release-hero-shell{min-height:auto}
        .release-device{transform:none}
        .release-hero-tabs{grid-template-columns:1fr}
        .release-hero-tab{padding:.7rem}
    }
    @media(prefers-reduced-motion:reduce){
        .release-hero-slide{transition:none}
    }

    /* Slide 1 growth illustration */
    .release-growth-wrap{
        position:relative;
        display:flex;
        min-width:0;
        align-items:center;
        justify-content:center;
        padding:.25rem 0;
    }
    .release-growth-aura{
        position:absolute;
        inset:14% 10%;
        border-radius:9999px;
        background:color-mix(in srgb,var(--brand-primary) 19%,transparent);
        filter:blur(78px);
        opacity:.62;
        pointer-events:none;
    }
    .release-growth-svg{
        position:relative;
        z-index:1;
        width:min(100%,46rem);
        height:auto;
        overflow:visible;
    }

    .growth-panel-stop-a{stop-color:hsl(var(--card))}
    .growth-panel-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 5%,hsl(var(--background)))}
    .dark .growth-panel-stop-a{stop-color:#111827}
    .dark .growth-panel-stop-b{stop-color:#07101b}

    .growth-bar-stop-a{stop-color:color-mix(in srgb,var(--brand-primary) 72%,white)}
    .growth-bar-stop-b{stop-color:var(--brand-primary)}

    .growth-tile-stop-a{stop-color:hsl(var(--card))}
    .growth-tile-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 5%,hsl(var(--muted)))}
    .dark .growth-tile-stop-a{stop-color:#152033}
    .dark .growth-tile-stop-b{stop-color:#0a1220}

    .growth-panel-stroke{
        stroke:color-mix(in srgb,var(--brand-primary) 26%,hsl(var(--border)));
        stroke-width:2;
    }
    .growth-grid line{
        stroke:hsl(var(--border));
        stroke-width:1;
        stroke-dasharray:5 8;
    }
    .growth-badge-bg{
        fill:hsl(var(--card)/.92);
        stroke:color-mix(in srgb,var(--brand-primary) 28%,hsl(var(--border)));
        stroke-width:2;
    }
    .dark .growth-badge-bg{fill:rgba(10,18,31,.88)}
    .growth-accent-fill{fill:var(--brand-primary)}
    .growth-accent-stroke{
        fill:none;
        stroke:var(--brand-primary);
        stroke-width:4;
        stroke-linecap:round;
        stroke-linejoin:round;
    }
    .growth-arrow-mark{
        fill:none;
        stroke:#fff;
        stroke-width:5;
        stroke-linecap:round;
        stroke-linejoin:round;
    }
    .growth-count-text{
        fill:hsl(var(--foreground));
        font-size:34px;
        font-weight:800;
        letter-spacing:-1.4px;
    }
    .growth-label-text{
        fill:hsl(var(--muted-foreground));
        font-size:22px;
        font-weight:500;
    }
    .release-growth-line{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 72%,white);
        stroke-width:5;
        stroke-linecap:round;
        stroke-linejoin:round;
        filter:url(#growthGlow);
    }
    .growth-node{
        fill:hsl(var(--background));
        stroke:color-mix(in srgb,var(--brand-primary) 64%,white);
        stroke-width:5;
    }
    .growth-tile-stroke{
        stroke:color-mix(in srgb,var(--brand-primary) 20%,hsl(var(--border)));
        stroke-width:1.5;
    }
    .growth-icon-muted-fill{fill:hsl(var(--muted-foreground)/.62)}
    .growth-panel-icon-fill{fill:hsl(var(--card))}
    .growth-icon-muted-stroke{
        fill:none;
        stroke:hsl(var(--muted-foreground));
        stroke-width:5;
        stroke-linecap:round;
    }
    .release-growth-orbit{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 48%,transparent);
        stroke-width:2.3;
        stroke-linecap:round;
        filter:url(#growthGlow);
    }
    .release-growth-orbit-b{opacity:.48}

    /* entrance / counting emphasis */
    .release-hero-slide[data-active="true"] .growth-bar{
        transform-box:fill-box;
        transform-origin:center bottom;
        animation:growthBarRise .72s cubic-bezier(.2,.8,.2,1) both;
    }
    .release-hero-slide[data-active="true"] .growth-bar-1{animation-delay:.08s}
    .release-hero-slide[data-active="true"] .growth-bar-2{animation-delay:.15s}
    .release-hero-slide[data-active="true"] .growth-bar-3{animation-delay:.22s}
    .release-hero-slide[data-active="true"] .growth-bar-4{animation-delay:.29s}
    .release-hero-slide[data-active="true"] .growth-bar-5{animation-delay:.36s}
    .release-hero-slide[data-active="true"] .growth-bar-6{animation-delay:.43s}

    .release-hero-slide[data-active="true"] .release-growth-line{
        stroke-dasharray:900;
        stroke-dashoffset:900;
        animation:growthLineDraw 1.25s .22s ease forwards;
    }
    .release-hero-slide[data-active="true"] .growth-node{
        transform-box:fill-box;
        transform-origin:center;
        animation:growthNodePop .38s ease both;
    }
    .release-hero-slide[data-active="true"] .growth-node-1{animation-delay:.35s}
    .release-hero-slide[data-active="true"] .growth-node-2{animation-delay:.48s}
    .release-hero-slide[data-active="true"] .growth-node-3{animation-delay:.61s}
    .release-hero-slide[data-active="true"] .growth-node-4{animation-delay:.74s}
    .release-hero-slide[data-active="true"] .growth-node-5{animation-delay:.87s}
    .release-hero-slide[data-active="true"] .growth-node-6{animation-delay:1s}

    .release-growth-badge{
        transform-box:fill-box;
        transform-origin:center;
    }
    .release-growth-badge[data-count-complete="true"]{
        animation:growthBadgeBounce .58s cubic-bezier(.2,.95,.3,1.35);
    }
    .release-growth-orbit-dot{
        animation:growthOrbitPulse 2.6s ease-in-out infinite;
    }

    @keyframes growthBarRise{
        from{transform:scaleY(.08);opacity:.25}
        to{transform:scaleY(1);opacity:1}
    }
    @keyframes growthLineDraw{
        to{stroke-dashoffset:0}
    }
    @keyframes growthNodePop{
        0%{transform:scale(.2);opacity:0}
        72%{transform:scale(1.18);opacity:1}
        100%{transform:scale(1);opacity:1}
    }
    @keyframes growthBadgeBounce{
        0%{transform:translateY(0) scale(1)}
        38%{transform:translateY(-9px) scale(1.035)}
        68%{transform:translateY(3px) scale(.995)}
        100%{transform:translateY(0) scale(1)}
    }
    @keyframes growthOrbitPulse{
        0%,100%{opacity:.46}
        50%{opacity:1}
    }

    @media(max-width:759px){
        .release-growth-svg{width:min(100%,39rem)}
    }
    @media(prefers-reduced-motion:reduce){
        .release-hero-slide[data-active="true"] .growth-bar,
        .release-hero-slide[data-active="true"] .release-growth-line,
        .release-hero-slide[data-active="true"] .growth-node,
        .release-growth-badge[data-count-complete="true"],
        .release-growth-orbit-dot{
            animation:none !important;
        }
    }
</style>


<section class="release-hero border-b border-border" data-release-hero>
    <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-8 sm:px-6 sm:pb-10 sm:pt-10 lg:px-8">
        <div class="release-hero-shell">

            {{-- 01 / MARKETS --}}
                        <article class="release-hero-slide" data-release-slide="0" data-active="true">
                <div class="release-hero-layout">
                    <div class="release-hero-copy">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">
                            {{ localize('ui.release.hero.markets.eyebrow', 'One platform. More opportunities.') }}
                        </p>

                        <h1 class="release-hero-title mt-4 font-semibold">
                            {{ localize('ui.release.hero.markets.trade', 'Trade') }}
                            <span class="release-hero-primary-text">{{ localize('ui.release.hero.markets.smarter', 'Smarter.') }}</span>
                            <span class="block">
                                {{ localize('ui.release.hero.markets.invest', 'Invest With') }}
                                <span class="release-hero-primary-text">{{ localize('ui.release.hero.markets.confidence', 'Confidence.') }}</span>
                            </span>
                        </h1>

                        <p class="mt-5 max-w-xl text-sm leading-6 text-muted-foreground sm:text-base sm:leading-7">
                            {{ localize('ui.release.hero.markets.copy', 'Research global stocks, forex, crypto and commodities, access investment products, and do more through one connected financial platform.') }}
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $marketUrl }}" class="release-hero-primary-button inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold">
                                {{ localize('ui.release.hero.explore', 'Explore Market') }}
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                            <a href="{{ $registerUrl }}" class="release-hero-secondary-button inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold">
                                {{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.release.hero.register', 'Register') }}
                            </a>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2.5 text-[9px] font-medium text-muted-foreground sm:text-[10px]">
                            <span class="inline-flex items-center gap-2">
                                <i data-lucide="shield-check" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>
                                {{ localize('ui.release.hero.controlled', 'Secure & controlled access') }}
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <i data-lucide="chart-no-axes-combined" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>
                                {{ localize('ui.release.hero.realtime', 'Multi-asset market data') }}
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <i data-lucide="layers-3" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>
                                {{ localize('ui.release.hero.products', 'Distinct product systems') }}
                            </span>
                        </div>
                    </div>

                    <div class="release-growth-wrap" aria-label="Growth and financial opportunity illustration">
                        <div class="release-growth-aura" aria-hidden="true"></div>

                        <svg class="release-growth-svg" viewBox="0 0 760 560" role="img" aria-labelledby="releaseGrowthTitle releaseGrowthDesc">
                            <title id="releaseGrowthTitle">{{ localize('ui.release.hero.growth.title', 'Financial growth illustration') }}</title>
                            <desc id="releaseGrowthDesc">{{ localize('ui.release.hero.growth.desc', 'An illustrative growth panel with rising bars and an upward curve.') }}</desc>

                            <defs>
                                <linearGradient id="growthPanelFill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" class="growth-panel-stop-a"/>
                                    <stop offset="100%" class="growth-panel-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthBarFill" x1="0" y1="1" x2="0" y2="0">
                                    <stop offset="0%" class="growth-bar-stop-a"/>
                                    <stop offset="100%" class="growth-bar-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthTileFill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" class="growth-tile-stop-a"/>
                                    <stop offset="100%" class="growth-tile-stop-b"/>
                                </linearGradient>

                                <filter id="growthGlow" x="-40%" y="-40%" width="180%" height="180%">
                                    <feGaussianBlur stdDeviation="8" result="blur"/>
                                    <feMerge>
                                        <feMergeNode in="blur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>

                                <filter id="growthSoftShadow" x="-20%" y="-20%" width="140%" height="150%">
                                    <feDropShadow dx="0" dy="18" stdDeviation="18" flood-opacity=".22"/>
                                </filter>
                            </defs>

                            <g class="release-growth-card" filter="url(#growthSoftShadow)">
                                <rect x="54" y="54" width="650" height="446" rx="38" fill="url(#growthPanelFill)" class="growth-panel-stroke"/>

                                <g class="growth-grid" opacity=".34">
                                    <line x1="120" y1="160" x2="650" y2="160"/>
                                    <line x1="120" y1="226" x2="650" y2="226"/>
                                    <line x1="120" y1="292" x2="650" y2="292"/>
                                    <line x1="120" y1="358" x2="650" y2="358"/>
                                    <line x1="178" y1="142" x2="178" y2="372"/>
                                    <line x1="270" y1="142" x2="270" y2="372"/>
                                    <line x1="362" y1="142" x2="362" y2="372"/>
                                    <line x1="454" y1="142" x2="454" y2="372"/>
                                    <line x1="546" y1="142" x2="546" y2="372"/>
                                </g>

                                <g class="release-growth-badge" data-growth-badge>
                                    <rect x="112" y="90" width="254" height="78" rx="23" class="growth-badge-bg"/>
                                    <circle cx="150" cy="129" r="24" class="growth-accent-fill"/>
                                    <path d="M140 138 L160 118 M149 118 H160 V129" class="growth-arrow-mark"/>
                                    <text x="184" y="137" class="growth-count-text">
                                        +<tspan data-growth-count>0</tspan>%
                                    </text>
                                    <text x="294" y="137" class="growth-label-text">growth</text>
                                </g>

                                <g class="release-growth-bars" filter="url(#growthGlow)">
                                    <rect class="growth-bar growth-bar-1" x="142" y="320" width="56" height="52" rx="12" fill="url(#growthBarFill)"/>
                                    <rect class="growth-bar growth-bar-2" x="228" y="282" width="56" height="90" rx="12" fill="url(#growthBarFill)"/>
                                    <rect class="growth-bar growth-bar-3" x="314" y="250" width="56" height="122" rx="12" fill="url(#growthBarFill)"/>
                                    <rect class="growth-bar growth-bar-4" x="400" y="210" width="56" height="162" rx="12" fill="url(#growthBarFill)"/>
                                    <rect class="growth-bar growth-bar-5" x="486" y="164" width="56" height="208" rx="12" fill="url(#growthBarFill)"/>
                                    <rect class="growth-bar growth-bar-6" x="572" y="112" width="56" height="260" rx="12" fill="url(#growthBarFill)"/>
                                </g>

                                <path
                                    class="release-growth-line"
                                    d="M150 326 C188 304 207 286 236 294 C267 303 286 266 322 260 C355 255 369 280 404 244 C438 210 456 221 492 194 C523 171 537 152 570 128 C594 111 612 94 638 78"
                                />

                                <g class="release-growth-nodes" filter="url(#growthGlow)">
                                    <circle class="growth-node growth-node-1" cx="150" cy="326" r="8"/>
                                    <circle class="growth-node growth-node-2" cx="236" cy="294" r="8"/>
                                    <circle class="growth-node growth-node-3" cx="322" cy="260" r="8"/>
                                    <circle class="growth-node growth-node-4" cx="404" cy="244" r="8"/>
                                    <circle class="growth-node growth-node-5" cx="492" cy="194" r="8"/>
                                    <circle class="growth-node growth-node-6" cx="570" cy="128" r="8"/>
                                    <path d="M630 89 L650 67 L644 99 Z" class="growth-accent-fill"/>
                                </g>

                                <g class="release-growth-tiles">
                                    <g transform="translate(116 392)">
                                        <rect width="118" height="82" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <rect x="24" y="43" width="11" height="18" rx="3" class="growth-accent-fill"/>
                                        <rect x="44" y="33" width="11" height="28" rx="3" class="growth-accent-fill"/>
                                        <rect x="64" y="22" width="11" height="39" rx="3" class="growth-accent-fill"/>
                                    </g>

                                    <g transform="translate(246 392)">
                                        <rect width="118" height="82" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <circle cx="59" cy="42" r="22" class="growth-icon-muted-fill"/>
                                        <path d="M59 42 L59 20 A22 22 0 0 1 81 42 Z" class="growth-panel-icon-fill"/>
                                    </g>

                                    <g transform="translate(376 392)">
                                        <rect width="118" height="82" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <rect x="39" y="20" width="40" height="43" rx="8" fill="none" class="growth-accent-stroke"/>
                                        <line x1="49" y1="33" x2="69" y2="33" class="growth-accent-stroke"/>
                                        <line x1="49" y1="43" x2="69" y2="43" class="growth-accent-stroke"/>
                                        <line x1="49" y1="53" x2="63" y2="53" class="growth-accent-stroke"/>
                                    </g>

                                    <g transform="translate(506 392)">
                                        <rect width="118" height="82" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <circle cx="59" cy="41" r="14" fill="none" class="growth-icon-muted-stroke"/>
                                        <path d="M59 15 V24 M59 58 V67 M33 41 H42 M76 41 H85 M41 23 L47 29 M71 53 L77 59 M77 23 L71 29 M47 53 L41 59" class="growth-icon-muted-stroke"/>
                                    </g>
                                </g>

                                <path class="release-growth-orbit release-growth-orbit-a" d="M24 370 C148 456 535 468 730 205"/>
                                <path class="release-growth-orbit release-growth-orbit-b" d="M36 402 C248 522 609 428 738 258"/>
                                <circle cx="72" cy="394" r="5" class="growth-accent-fill release-growth-orbit-dot"/>
                                <circle cx="690" cy="292" r="5" class="growth-accent-fill release-growth-orbit-dot"/>
                            </g>
                        </svg>
                    </div>
                </div>
            </article>

            {{-- 02 / AUTOMOTIVE --}}
            <article class="release-hero-slide" data-release-slide="1" data-active="false">
                <div class="release-hero-layout">
                    <div class="release-hero-copy">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">{{ localize('ui.release.hero.auto.eyebrow', 'Digital commerce · automotive inventory') }}</p>
                        <h2 class="release-hero-title mt-4 font-semibold">
                            {{ localize('ui.release.hero.auto.discover', 'Discover') }}
                            <span class="release-hero-primary-text">{{ localize('ui.release.hero.auto.premium', 'Premium') }}</span>
                            <span class="block">{{ localize('ui.release.hero.auto.vehicles', 'Vehicle') }}
                                <span class="release-hero-secondary-text">{{ localize('ui.release.hero.auto.inventory', 'Inventory.') }}</span>
                            </span>
                        </h2>
                        <p class="mt-5 max-w-xl text-sm leading-6 text-muted-foreground sm:text-base sm:leading-7">{{ localize('ui.release.hero.auto.copy', 'Browse available vehicles through the same connected platform while commerce remains distinct from your financial market activity.') }}</p>
                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $automotiveUrl }}" class="release-hero-primary-button inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold">{{ localize('ui.release.hero.auto.browse', 'Browse Inventory') }}<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                            <a href="{{ $registerUrl }}" class="release-hero-secondary-button inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold">{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.release.hero.register', 'Register') }}</a>
                        </div>
                    </div>

                    <div class="release-device-wrap">
                        <div class="release-device-glow"></div>
                        <div class="release-device">
                            <div class="release-device-screen">
                                <div class="release-screen-top">
                                    <div class="flex items-center gap-2">
                                        <span class="release-ui-icon flex h-7 w-7 items-center justify-center rounded-lg"><i data-lucide="car-front" class="h-3.5 w-3.5"></i></span>
                                        <div><p class="text-[7px] uppercase tracking-[.14em] text-muted-foreground">{{ $company }}</p><p class="text-[10px] font-semibold text-foreground">{{ localize('ui.release.hero.auto.visual', 'Automotive Inventory') }}</p></div>
                                    </div>
                                    <span class="rounded-full border border-border bg-card px-2 py-1 text-[7px] font-semibold text-muted-foreground">{{ number_format($platformStats['automotive_inventory'] ?? 0) }} {{ localize('ui.release.hero.auto.available', 'available') }}</span>
                                </div>

                                <div class="release-screen-body">
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        @forelse($featuredCars as $car)
                                            <a href="{{ route('cars.show', $car->id) }}" class="group overflow-hidden rounded-xl border border-border bg-card">
                                                <div class="aspect-[4/3] overflow-hidden bg-muted">
                                                    @if($car->first_image)
                                                        <img src="{{ str_starts_with($car->first_image, 'http') ? $car->first_image : asset('storage/'.$car->first_image) }}" alt="{{ $car->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy" decoding="async">
                                                    @else
                                                        <div class="flex h-full items-center justify-center"><i data-lucide="car-front" class="h-7 w-7 text-muted-foreground"></i></div>
                                                    @endif
                                                </div>
                                                <div class="p-2.5">
                                                    <p class="truncate text-[9px] font-semibold text-foreground">{{ $car->title }}</p>
                                                    <p class="mt-1 text-[7px] text-muted-foreground">{{ $car->year }} · {{ $car->make }}</p>
                                                    <p class="mt-2 text-[10px] font-semibold tabular-nums text-foreground">{{ currency_symbol() }}{{ number_format((float)$car->price,0) }}</p>
                                                </div>
                                            </a>
                                        @empty
                                            @foreach([1,2,3] as $slot)
                                                <div class="overflow-hidden rounded-xl border border-dashed border-border bg-card">
                                                    <div class="flex aspect-[4/3] items-center justify-center bg-muted/40"><i data-lucide="car-front" class="h-7 w-7 text-muted-foreground"></i></div>
                                                    <div class="p-2.5"><p class="text-[9px] font-semibold text-foreground">{{ localize('ui.release.hero.auto.placeholder', 'Vehicle listing') }}</p><p class="mt-1 text-[7px] text-muted-foreground">{{ localize('ui.release.hero.auto.ready', 'Ready for publishing') }}</p></div>
                                                </div>
                                            @endforeach
                                        @endforelse
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        @foreach([['shield-check','Listing flow'],['credit-card','Checkout'],['history','Purchase records']] as [$icon,$label])
                                            <div class="release-ui-panel px-3 py-2.5"><i data-lucide="{{ $icon }}" class="h-3 w-3" style="color:var(--brand-primary)"></i><p class="mt-1.5 text-[8px] font-semibold text-foreground">{{ $label }}</p></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="release-device-hinge"></div>
                            <div class="release-device-base"></div>
                        </div>
                    </div>
                </div>
            </article>

            {{-- 03 / DASHBOARD EXPERIENCE --}}
            <article class="release-hero-slide" data-release-slide="2" data-active="false">
                <div class="release-hero-layout">
                    <div class="release-hero-copy">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">{{ localize('ui.release.hero.dashboard.eyebrow', 'Trading · investments · account experience') }}</p>
                        <h2 class="release-hero-title mt-4 font-semibold">
                            {{ localize('ui.release.hero.dashboard.your', 'Your') }}
                            <span class="release-hero-primary-text">{{ localize('ui.release.hero.dashboard.financial', 'Financial') }}</span>
                            <span class="block">{{ localize('ui.release.hero.dashboard.workspace', 'Workspace,') }}
                                <span class="release-hero-secondary-text">{{ localize('ui.release.hero.dashboard.connected', 'Connected.') }}</span>
                            </span>
                        </h2>
                        <p class="mt-5 max-w-xl text-sm leading-6 text-muted-foreground sm:text-base sm:leading-7">{{ localize('ui.release.hero.dashboard.copy', 'Move from market discovery to trading, investment products, automation and account records through dedicated workspaces under one profile.') }}</p>
                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $investmentUrl }}" class="release-hero-primary-button inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold">{{ localize('ui.release.hero.dashboard.invest', 'Explore Investments') }}<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
                            <a href="{{ $workspaceUrl }}" class="release-hero-secondary-button inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold">{{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.release.hero.register', 'Register') }}</a>
                        </div>
                    </div>

                    <div class="release-device-wrap">
                        <div class="release-device-glow"></div>
                        <div class="release-device">
                            <div class="release-device-screen">
                                <div class="release-screen-top">
                                    <div class="flex items-center gap-2">
                                        <span class="release-ui-icon flex h-7 w-7 items-center justify-center rounded-lg"><i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i></span>
                                        <div><p class="text-[7px] uppercase tracking-[.14em] text-muted-foreground">{{ $company }}</p><p class="text-[10px] font-semibold text-foreground">{{ localize('ui.release.hero.dashboard.visual', 'Trading & Investment') }}</p></div>
                                    </div>
                                    <span class="rounded-full border border-border bg-card px-2 py-1 text-[7px] font-semibold text-muted-foreground">{{ localize('ui.release.hero.dashboard.account', 'Account workspace') }}</span>
                                </div>

                                <div class="release-screen-body">
                                    <div class="grid gap-3 sm:grid-cols-[1.2fr_.8fr]">
                                        <div class="release-ui-panel p-3.5">
                                            <div class="flex items-center justify-between gap-3">
                                                <div><p class="text-[7px] uppercase tracking-[.13em] text-muted-foreground">{{ localize('ui.release.hero.dashboard.execution', 'Market execution') }}</p><p class="mt-1 text-sm font-semibold text-foreground">{{ localize('ui.release.hero.dashboard.multiasset', 'Multi-asset trading') }}</p></div>
                                                <span class="release-ui-icon flex h-8 w-8 items-center justify-center rounded-lg"><i data-lucide="chart-candlestick" class="h-3.5 w-3.5"></i></span>
                                            </div>
                                            <div class="mt-3 h-28 rounded-xl bg-muted/30 p-2">
                                                <svg viewBox="0 0 500 120" preserveAspectRatio="none" class="h-full w-full" aria-hidden="true">
                                                    <line x1="0" y1="30" x2="500" y2="30" stroke="hsl(var(--border))" stroke-opacity=".55"/>
                                                    <line x1="0" y1="61" x2="500" y2="61" stroke="hsl(var(--border))" stroke-opacity=".55"/>
                                                    <line x1="0" y1="92" x2="500" y2="92" stroke="hsl(var(--border))" stroke-opacity=".55"/>
                                                    <path d="M0 96 C34 88,58 98,86 76 C116 54,143 69,174 51 C204 34,232 50,261 39 C291 26,320 37,351 23 C384 10,414 30,444 17 C469 8,486 11,500 6" class="release-chart-line"/>
                                                </svg>
                                            </div>
                                            <div class="mt-3 grid grid-cols-3 gap-2">
                                                @foreach([['Signals',$platformStats['signals'] ?? 0],['Bots',$platformStats['bots'] ?? 0],['Copy',$platformStats['copy_strategies'] ?? 0]] as [$label,$value])
                                                    <div class="rounded-lg border border-border bg-card px-2.5 py-2"><p class="text-[7px] uppercase tracking-[.09em] text-muted-foreground">{{ $label }}</p><p class="mt-1 text-[10px] font-semibold tabular-nums text-foreground">{{ number_format($value) }}</p></div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="grid gap-2">
                                            <div class="release-ui-panel p-3">
                                                <div class="flex items-center justify-between"><div><p class="text-[7px] uppercase tracking-[.12em] text-muted-foreground">{{ localize('ui.release.hero.dashboard.investments', 'Investment products') }}</p><p class="mt-1 text-lg font-semibold tabular-nums text-foreground">{{ number_format($platformStats['investments'] ?? 0) }}</p></div><span class="release-ui-icon flex h-8 w-8 items-center justify-center rounded-lg"><i data-lucide="gem" class="h-3.5 w-3.5"></i></span></div>
                                            </div>
                                            @foreach($featuredInvestments as $investment)
                                                <div class="release-ui-panel p-3">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="min-w-0"><p class="truncate text-[9px] font-semibold text-foreground">{{ $investment->symbol }}</p><p class="mt-1 truncate text-[7px] text-muted-foreground">{{ $investment->name }}</p></div>
                                                        <div class="shrink-0 text-right"><p class="text-[8px] font-semibold tabular-nums text-foreground">{{ number_format((float)$investment->current_price,2) }}</p><p class="mt-1 text-[7px] {{ $investment->change_percent >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $investment->change_percent >= 0 ? '+' : '' }}{{ number_format($investment->change_percent,2) }}%</p></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="release-device-hinge"></div>
                            <div class="release-device-base"></div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        {{-- 3 carousel preview tabs --}}
        <div class="release-hero-tabs">
            <button type="button" class="release-hero-tab" data-release-thumb="0" data-active="true">
                <div class="flex items-center gap-3">
                    <span class="release-hero-number">01</span>
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-foreground">{{ localize('ui.release.hero.tab1', 'Markets & Assets') }}</p><p class="mt-1 truncate text-[8px] text-muted-foreground">{{ localize('ui.release.hero.tab1copy', 'Trade global assets with market intelligence') }}</p></div>
                    <i data-lucide="chart-no-axes-combined" class="ml-auto hidden h-4 w-4 text-muted-foreground sm:block"></i>
                </div>
            </button>
            <button type="button" class="release-hero-tab" data-release-thumb="1" data-active="false">
                <div class="flex items-center gap-3">
                    <span class="release-hero-number">02</span>
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-foreground">{{ localize('ui.release.hero.tab2', 'Automotive Inventory') }}</p><p class="mt-1 truncate text-[8px] text-muted-foreground">{{ localize('ui.release.hero.tab2copy', 'Browse and manage available vehicle inventory') }}</p></div>
                    <i data-lucide="car-front" class="ml-auto hidden h-4 w-4 text-muted-foreground sm:block"></i>
                </div>
            </button>
            <button type="button" class="release-hero-tab" data-release-thumb="2" data-active="false">
                <div class="flex items-center gap-3">
                    <span class="release-hero-number">03</span>
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-foreground">{{ localize('ui.release.hero.tab3', 'Trading & Investment') }}</p><p class="mt-1 truncate text-[8px] text-muted-foreground">{{ localize('ui.release.hero.tab3copy', 'Dashboard, trading and investment experience') }}</p></div>
                    <i data-lucide="layout-dashboard" class="ml-auto hidden h-4 w-4 text-muted-foreground sm:block"></i>
                </div>
            </button>
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <button type="button" data-release-prev class="flex h-9 w-9 items-center justify-center rounded-full border border-border bg-card text-foreground transition hover:bg-muted" aria-label="Previous slide"><i data-lucide="chevron-left" class="h-4 w-4"></i></button>
            <button type="button" data-release-next class="release-hero-primary-button flex h-9 w-9 items-center justify-center rounded-full" aria-label="Next slide"><i data-lucide="chevron-right" class="h-4 w-4"></i></button>
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
    const releaseHero = document.querySelector('[data-release-hero]');
    const releaseSlides = Array.from(releaseHero?.querySelectorAll('[data-release-slide]') || []);
    const releaseThumbs = Array.from(releaseHero?.querySelectorAll('[data-release-thumb]') || []);
    const releasePrev = releaseHero?.querySelector('[data-release-prev]');
    const releaseNext = releaseHero?.querySelector('[data-release-next]');
    let releaseIndex = 0;
    let releaseTimer = null;

    const animateGrowthCount = () => {
        const countNode = releaseHero?.querySelector('[data-growth-count]');
        const badge = releaseHero?.querySelector('[data-growth-badge]');
        if (!countNode || !badge) return;

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        badge.dataset.countComplete = 'false';

        if (countNode._growthFrame) {
            cancelAnimationFrame(countNode._growthFrame);
            countNode._growthFrame = null;
        }

        if (reduceMotion) {
            countNode.textContent = '68';
            badge.dataset.countComplete = 'true';
            return;
        }

        const duration = 1450;
        const started = performance.now();
        countNode.textContent = '0';

        const tick = (now) => {
            const elapsed = Math.min(1, (now - started) / duration);
            const eased = 1 - Math.pow(1 - elapsed, 3);
            const value = Math.round(68 * eased);
            countNode.textContent = String(value);

            if (elapsed < 1) {
                countNode._growthFrame = requestAnimationFrame(tick);
            } else {
                countNode.textContent = '68';
                badge.dataset.countComplete = 'true';
                countNode._growthFrame = null;
            }
        };

        countNode._growthFrame = requestAnimationFrame(tick);
    };

    const showReleaseSlide = (index) => {
        if (!releaseSlides.length) return;
        releaseIndex = (index + releaseSlides.length) % releaseSlides.length;

        releaseSlides.forEach((slide, i) => {
            const active = i === releaseIndex;
            slide.dataset.active = active ? 'true' : 'false';
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        releaseThumbs.forEach((thumb, i) => {
            const active = i === releaseIndex;
            thumb.dataset.active = active ? 'true' : 'false';
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
        });

        if (window.lucide) lucide.createIcons();

        if (releaseIndex === 0) {
            requestAnimationFrame(animateGrowthCount);
        }
    };

    const stopReleaseRotation = () => {
        if (releaseTimer) clearInterval(releaseTimer);
        releaseTimer = null;
    };

    const startReleaseRotation = () => {
        stopReleaseRotation();
        if (!releaseHero || releaseSlides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        releaseTimer = setInterval(() => showReleaseSlide(releaseIndex + 1), 8000);
    };

    releaseThumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => {
            showReleaseSlide(index);
            startReleaseRotation();
        });
    });

    releasePrev?.addEventListener('click', () => {
        showReleaseSlide(releaseIndex - 1);
        startReleaseRotation();
    });

    releaseNext?.addEventListener('click', () => {
        showReleaseSlide(releaseIndex + 1);
        startReleaseRotation();
    });

    releaseHero?.addEventListener('mouseenter', stopReleaseRotation);
    releaseHero?.addEventListener('mouseleave', startReleaseRotation);
    releaseHero?.addEventListener('focusin', stopReleaseRotation);
    releaseHero?.addEventListener('focusout', startReleaseRotation);

    showReleaseSlide(0);
    startReleaseRotation();

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

@extends('layouts.main')
@section('shell_top_spacing', '')

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
    $marketUrl = route('markets');
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

    /* Slide 1 growth illustration — R3 realism / atmosphere */

    /* hero atmosphere overrides — theme aware */
    .release-hero{
        padding-top:2rem;
        background:
            radial-gradient(circle at 15% 16%, color-mix(in srgb,var(--hero-primary) 8%, transparent) 0, transparent 30%),
            radial-gradient(circle at 78% 18%, color-mix(in srgb,var(--hero-primary) 13%, transparent) 0, transparent 36%),
            radial-gradient(circle at 82% 78%, color-mix(in srgb,var(--hero-secondary) 9%, transparent) 0, transparent 40%),
            linear-gradient(
                112deg,
                color-mix(in srgb,var(--hero-primary) 3%, hsl(var(--background))) 0%,
                hsl(var(--background)) 46%,
                color-mix(in srgb,var(--hero-secondary) 2%, hsl(var(--background))) 100%
            );
    }
    .dark .release-hero{
        background:
            radial-gradient(circle at 15% 16%, color-mix(in srgb,var(--hero-primary) 15%, transparent) 0, transparent 31%),
            radial-gradient(circle at 78% 18%, color-mix(in srgb,var(--hero-primary) 23%, transparent) 0, transparent 38%),
            radial-gradient(circle at 82% 78%, color-mix(in srgb,var(--hero-secondary) 14%, transparent) 0, transparent 42%),
            linear-gradient(
                112deg,
                color-mix(in srgb,var(--hero-primary) 7%, hsl(var(--background))) 0%,
                hsl(var(--background)) 44%,
                color-mix(in srgb,var(--hero-secondary) 4%, hsl(var(--background))) 100%
            );
    }
    .release-hero::before{
        content:"";
        position:absolute;
        inset:0;
        pointer-events:none;
        background-image:
            linear-gradient(hsl(var(--border)/.10) 1px,transparent 1px),
            linear-gradient(90deg,hsl(var(--border)/.10) 1px,transparent 1px);
        background-size:54px 54px;
        mask-image:linear-gradient(to bottom,rgba(0,0,0,.52),transparent 88%);
        opacity:.7;
    }
    .release-hero::after{
        content:"";
        position:absolute;
        inset:6% -8% -10% 46%;
        border-radius:50%;
        background:
            radial-gradient(circle at 36% 34%, color-mix(in srgb,var(--hero-primary) 15%, transparent) 0, transparent 28%),
            radial-gradient(circle at 64% 52%, color-mix(in srgb,var(--hero-primary) 10%, transparent) 0, transparent 34%),
            radial-gradient(circle at 52% 72%, color-mix(in srgb,var(--hero-secondary) 7%, transparent) 0, transparent 44%);
        filter:blur(74px);
        opacity:.72;
        pointer-events:none;
    }
    .dark .release-hero::after{
        background:
            radial-gradient(circle at 36% 34%, color-mix(in srgb,var(--hero-primary) 22%, transparent) 0, transparent 28%),
            radial-gradient(circle at 64% 52%, color-mix(in srgb,var(--hero-primary) 15%, transparent) 0, transparent 34%),
            radial-gradient(circle at 52% 72%, color-mix(in srgb,var(--hero-secondary) 11%, transparent) 0, transparent 44%);
        opacity:.94;
    }
    .release-hero-shell{
        position:relative;
        min-height:35.5rem;
        isolation:isolate;
        padding-top:.5rem;
    }
    .release-hero-shell::before{
        content:"";
        position:absolute;
        inset:20% 36% 8% -4%;
        background:
            radial-gradient(circle at 8% 42%, color-mix(in srgb,var(--hero-primary) 7%, transparent) 0, transparent 26%),
            radial-gradient(circle at 85% 18%, color-mix(in srgb,var(--hero-primary) 6%, transparent) 0, transparent 32%);
        filter:blur(58px);
        pointer-events:none;
        opacity:.9;
    }

    .release-growth-wrap{
        position:relative;
        display:flex;
        min-width:0;
        align-items:center;
        justify-content:center;
        min-height:33rem;
        padding:1.7rem 0 2.5rem;
        perspective:1850px;
        isolation:isolate;
    }
    .release-growth-aura{
        position:absolute;
        inset:12% 2% 4% 6%;
        border-radius:9999px;
        background:
            radial-gradient(circle at 62% 38%, color-mix(in srgb,var(--brand-primary) 22%, transparent), transparent 36%),
            radial-gradient(circle at 56% 84%, color-mix(in srgb,var(--brand-primary) 11%, transparent), transparent 48%),
            radial-gradient(circle at 86% 56%, color-mix(in srgb,var(--brand-primary) 10%, transparent), transparent 30%);
        filter:blur(74px);
        opacity:.88;
        pointer-events:none;
    }
    .release-growth-svg{
        position:relative;
        z-index:1;
        width:min(114%,50rem);
        height:auto;
        overflow:visible;
        transform:perspective(1850px) rotateY(-12deg) rotateX(5.2deg) rotateZ(-1.2deg) translate3d(8px,0,0);
        transform-origin:54% 52%;
        filter:drop-shadow(0 26px 34px hsl(var(--foreground)/.12));
        animation:growthDeviceFloat 6.8s ease-in-out infinite;
        will-change:transform;
    }
    .dark .release-growth-svg{
        filter:drop-shadow(0 34px 42px rgba(0,0,0,.38));
    }

    .growth-panel-stop-a{stop-color:hsl(var(--card)/.28)}
    .growth-panel-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 4%,hsl(var(--background)/.10))}
    .dark .growth-panel-stop-a{stop-color:rgba(17,26,40,.22)}
    .dark .growth-panel-stop-b{stop-color:rgba(6,11,20,.08)}

    .growth-rear-stop-a{stop-color:hsl(var(--card)/.17)}
    .growth-rear-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 4%,hsl(var(--background)/.06))}
    .dark .growth-rear-stop-a{stop-color:rgba(18,28,43,.14)}
    .dark .growth-rear-stop-b{stop-color:rgba(4,8,17,.05)}

    .growth-base-stop-a{stop-color:color-mix(in srgb,hsl(var(--foreground)) 12%,hsl(var(--card)))}
    .growth-base-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 8%,hsl(var(--muted)))}
    .dark .growth-base-stop-a{stop-color:#172232}
    .dark .growth-base-stop-b{stop-color:#090d15}

    .growth-bar-stop-a{stop-color:color-mix(in srgb,var(--brand-primary) 68%,white)}
    .growth-bar-stop-b{stop-color:var(--brand-primary)}

    .growth-tile-stop-a{stop-color:hsl(var(--card)/.22)}
    .growth-tile-stop-b{stop-color:color-mix(in srgb,var(--brand-primary) 3%,hsl(var(--muted)/.12))}
    .dark .growth-tile-stop-a{stop-color:rgba(25,35,52,.18)}
    .dark .growth-tile-stop-b{stop-color:rgba(8,13,23,.10)}

    .growth-rear-panel{
        fill:url(#growthRearFill);
        stroke:color-mix(in srgb,var(--brand-primary) 12%, hsl(var(--border)));
        stroke-width:1.3;
    }
    .growth-base-top{
        fill:url(#growthBaseFill);
        stroke:color-mix(in srgb,var(--brand-primary) 14%,hsl(var(--border)));
        stroke-width:1.4;
    }
    .growth-base-front{
        fill:color-mix(in srgb,var(--brand-primary) 6%,hsl(var(--muted)));
        stroke:color-mix(in srgb,var(--brand-primary) 12%,hsl(var(--border)));
        stroke-width:1.2;
    }
    .dark .growth-base-front{fill:#070b12}
    .growth-base-edge{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 55%,transparent);
        stroke-width:2;
        filter:url(#growthGlow);
    }

    .growth-panel-stroke{
        stroke:color-mix(in srgb,var(--brand-primary) 20%,hsl(var(--border)));
        stroke-width:1.6;
    }
    .growth-panel-inner-edge{
        fill:none;
        stroke:hsl(var(--foreground)/.07);
        stroke-width:1;
    }
    .dark .growth-panel-inner-edge{stroke:rgba(255,255,255,.08)}
    .growth-panel-highlight{
        fill:none;
        stroke:url(#growthEdgeHighlight);
        stroke-width:1.8;
        opacity:.78;
    }
    .growth-panel-highlight-soft{
        fill:none;
        stroke:url(#growthSoftHighlight);
        stroke-width:1.2;
        opacity:.44;
    }
    .growth-grid line{
        stroke:hsl(var(--foreground)/.08);
        stroke-width:1;
        stroke-dasharray:5 8;
    }
    .dark .growth-grid line{stroke:rgba(255,255,255,.055)}
    .growth-badge-bg{
        fill:hsl(var(--card)/.28);
        stroke:color-mix(in srgb,var(--brand-primary) 18%,hsl(var(--border)));
        stroke-width:1.2;
    }
    .dark .growth-badge-bg{fill:rgba(7,14,24,.24)}
    .growth-accent-fill{fill:var(--brand-primary)}
    .growth-accent-stroke{
        fill:none;
        stroke:var(--brand-primary);
        stroke-width:3.8;
        stroke-linecap:round;
        stroke-linejoin:round;
    }
    .growth-arrow-mark{
        fill:none;
        stroke:#fff;
        stroke-width:4;
        stroke-linecap:round;
        stroke-linejoin:round;
    }
    .growth-count-text{
        fill:hsl(var(--foreground));
        font-size:30px;
        font-weight:800;
        letter-spacing:-1.1px;
    }
    .growth-label-text{
        fill:hsl(var(--muted-foreground));
        font-size:17px;
        font-weight:500;
        font-style:italic;
    }
    .release-growth-line{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 74%,white);
        stroke-width:4;
        stroke-linecap:round;
        stroke-linejoin:round;
        filter:url(#growthGlow);
    }
    .growth-node{
        fill:hsl(var(--background));
        stroke:color-mix(in srgb,var(--brand-primary) 68%,white);
        stroke-width:3.8;
    }
    .growth-tile-stroke{
        stroke:color-mix(in srgb,var(--brand-primary) 14%,hsl(var(--border)));
        stroke-width:1.1;
    }
    .growth-icon-muted-fill{fill:hsl(var(--muted-foreground)/.48)}
    .growth-panel-icon-fill{fill:hsl(var(--card))}
    .growth-icon-muted-stroke{
        fill:none;
        stroke:hsl(var(--muted-foreground)/.76);
        stroke-width:3.8;
        stroke-linecap:round;
    }
    .release-growth-orbit{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 56%,transparent);
        stroke-width:1.8;
        stroke-linecap:round;
        filter:url(#growthGlow);
    }
    .release-growth-orbit-b{opacity:.26}
    .growth-orbit-dots{
        fill:color-mix(in srgb,var(--brand-primary) 78%,white);
        filter:url(#growthGlow);
    }
    .growth-ambient{
        fill:none;
        stroke:color-mix(in srgb,var(--brand-primary) 20%,transparent);
        stroke-width:1.2;
        opacity:.44;
    }

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
    @keyframes growthLineDraw{to{stroke-dashoffset:0}}
    @keyframes growthNodePop{
        0%{transform:scale(.2);opacity:0}
        72%{transform:scale(1.18);opacity:1}
        100%{transform:scale(1);opacity:1}
    }
    @keyframes growthBadgeBounce{
        0%{transform:translateY(0) scale(1)}
        38%{transform:translateY(-7px) scale(1.024)}
        68%{transform:translateY(2px) scale(.997)}
        100%{transform:translateY(0) scale(1)}
    }
    @keyframes growthOrbitPulse{
        0%,100%{opacity:.34}
        50%{opacity:1}
    }
    @keyframes growthDeviceFloat{
        0%,100%{transform:perspective(1850px) rotateY(-12deg) rotateX(5.2deg) rotateZ(-1.2deg) translate3d(8px,0,0)}
        50%{transform:perspective(1850px) rotateY(-12deg) rotateX(5.2deg) rotateZ(-1.2deg) translate3d(8px,-8px,0)}
    }

    @media(max-width:759px){
        .release-hero{padding-top:1rem}
        .release-hero-shell{min-height:auto;padding-top:0}
        .release-growth-wrap{min-height:auto;padding:1rem 0 1.3rem}
        .release-growth-svg{
            width:min(100%,40rem);
            transform:none;
            animation:growthDeviceFloatMobile 6.8s ease-in-out infinite;
        }
        @keyframes growthDeviceFloatMobile{
            0%,100%{transform:translateY(0)}
            50%{transform:translateY(-6px)}
        }
    }
    @media(prefers-reduced-motion:reduce){
        .release-hero-slide[data-active="true"] .growth-bar,
        .release-hero-slide[data-active="true"] .release-growth-line,
        .release-hero-slide[data-active="true"] .growth-node,
        .release-growth-badge[data-count-complete="true"],
        .release-growth-orbit-dot,
        .release-growth-svg{
            animation:none !important;
        }
    }

    /* Slide 2 — Electric automobile */
    .release-hero-secondary-text{color:var(--hero-primary)}

    .release-ev-wrap{
        position:relative;
        display:flex;
        min-width:0;
        min-height:33rem;
        align-items:center;
        justify-content:center;
        padding:1rem 0 2rem;
        isolation:isolate;
        perspective:1700px;
    }
    .release-ev-aura{
        position:absolute;
        inset:12% 2% 10% 8%;
        border-radius:9999px;
        background:
            radial-gradient(circle at 58% 46%,color-mix(in srgb,var(--hero-primary) 18%,transparent),transparent 36%),
            radial-gradient(circle at 72% 76%,color-mix(in srgb,var(--hero-secondary) 8%,transparent),transparent 38%);
        filter:blur(78px);
        opacity:.78;
        pointer-events:none;
    }
    .release-ev-svg{
        position:relative;
        z-index:1;
        width:min(112%,50rem);
        height:auto;
        overflow:visible;
        transform:perspective(1700px) rotateY(-7deg) rotateX(2deg) translate3d(10px,0,0);
        transform-origin:center;
        filter:drop-shadow(0 30px 42px hsl(var(--foreground)/.14));
        animation:evFloat 7s ease-in-out infinite;
    }
    .dark .release-ev-svg{
        filter:drop-shadow(0 34px 46px rgba(0,0,0,.36));
    }

    .ev-floor{
        fill:color-mix(in srgb,var(--hero-primary) 8%,transparent);
        filter:url(#evBlur);
    }
    .ev-orbit{
        fill:none;
        stroke:color-mix(in srgb,var(--hero-primary) 48%,transparent);
        stroke-width:2;
        stroke-linecap:round;
        filter:url(#evGlow);
    }
    .ev-orbit-soft{opacity:.28}
    .ev-dot{
        fill:color-mix(in srgb,var(--hero-primary) 78%,white);
        filter:url(#evGlow);
    }

    .ev-body-main{
        fill:url(#evBodyFill);
        stroke:color-mix(in srgb,var(--hero-primary) 17%,hsl(var(--border)));
        stroke-width:1.6;
    }
    .ev-body-highlight{
        fill:none;
        stroke:url(#evEdgeFill);
        stroke-width:2;
        opacity:.78;
    }
    .ev-glass{
        fill:url(#evGlassFill);
        stroke:hsl(var(--foreground)/.09);
        stroke-width:1.3;
    }
    .dark .ev-glass{stroke:rgba(255,255,255,.08)}
    .ev-glass-line{
        fill:none;
        stroke:hsl(var(--foreground)/.12);
        stroke-width:1.1;
    }
    .dark .ev-glass-line{stroke:rgba(255,255,255,.10)}

    .ev-wheel-outer{
        fill:hsl(var(--foreground)/.88);
        stroke:hsl(var(--background)/.72);
        stroke-width:5;
    }
    .dark .ev-wheel-outer{fill:#05080d}
    .ev-wheel-inner{
        fill:url(#evWheelFill);
        stroke:color-mix(in srgb,var(--hero-primary) 22%,hsl(var(--border)));
        stroke-width:2;
    }
    .ev-wheel-hub{
        fill:hsl(var(--muted));
        stroke:hsl(var(--border));
        stroke-width:1.5;
    }

    .ev-light{
        fill:color-mix(in srgb,var(--hero-primary) 78%,white);
        filter:url(#evGlowStrong);
    }
    .ev-tail{
        fill:var(--hero-primary);
        filter:url(#evGlowStrong);
    }
    .ev-line{
        fill:none;
        stroke:color-mix(in srgb,var(--hero-primary) 52%,transparent);
        stroke-width:2.2;
        stroke-linecap:round;
    }
    .ev-energy-line{
        fill:none;
        stroke:var(--hero-primary);
        stroke-width:3.1;
        stroke-linecap:round;
        stroke-dasharray:9 10;
        filter:url(#evGlow);
        animation:evEnergyFlow 2.1s linear infinite;
    }
    .ev-battery-shell{
        fill:hsl(var(--card)/.42);
        stroke:color-mix(in srgb,var(--hero-primary) 22%,hsl(var(--border)));
        stroke-width:1.4;
    }
    .dark .ev-battery-shell{fill:rgba(9,15,25,.30)}
    .ev-battery-fill{fill:var(--hero-primary)}
    .ev-battery-text{
        fill:hsl(var(--foreground));
        font-size:19px;
        font-weight:800;
        letter-spacing:-.4px;
    }
    .ev-battery-label{
        fill:hsl(var(--muted-foreground));
        font-size:10px;
        font-weight:700;
        letter-spacing:1.1px;
        text-transform:uppercase;
    }
    .ev-road-line{
        fill:none;
        stroke:hsl(var(--foreground)/.09);
        stroke-width:1.2;
        stroke-dasharray:10 14;
    }
    .dark .ev-road-line{stroke:rgba(255,255,255,.06)}

    @keyframes evFloat{
        0%,100%{transform:perspective(1700px) rotateY(-7deg) rotateX(2deg) translate3d(10px,0,0)}
        50%{transform:perspective(1700px) rotateY(-7deg) rotateX(2deg) translate3d(10px,-7px,0)}
    }
    @keyframes evEnergyFlow{
        to{stroke-dashoffset:-38}
    }

    @media(max-width:759px){
        .release-ev-wrap{min-height:auto;padding:.8rem 0 1.25rem}
        .release-ev-svg{
            width:min(100%,41rem);
            transform:none;
            animation:evFloatMobile 7s ease-in-out infinite;
        }
        @keyframes evFloatMobile{
            0%,100%{transform:translateY(0)}
            50%{transform:translateY(-6px)}
        }
    }
    @media(prefers-reduced-motion:reduce){
        .release-ev-svg,
        .ev-energy-line{animation:none !important}
    }


    /* Slide 2 — actual Tesla video trial */
    .release-hero-secondary-text{color:var(--hero-primary)}

    .release-tesla-video-wrap{
        position:relative;
        display:flex;
        min-width:0;
        min-height:32rem;
        align-items:center;
        justify-content:center;
        padding:1rem 0 2rem;
        isolation:isolate;
    }
    .release-tesla-video-aura{
        position:absolute;
        inset:11% 2% 8% 7%;
        border-radius:9999px;
        background:
            radial-gradient(circle at 58% 45%,color-mix(in srgb,var(--hero-primary) 22%,transparent),transparent 38%),
            radial-gradient(circle at 74% 78%,color-mix(in srgb,var(--hero-secondary) 9%,transparent),transparent 42%);
        filter:blur(76px);
        opacity:.82;
        pointer-events:none;
    }
    .release-tesla-video-stage{
        position:relative;
        z-index:1;
        width:min(100%,47rem);
        aspect-ratio:16/10;
        overflow:hidden;
        border-radius:2rem;
        border:1px solid color-mix(in srgb,var(--hero-primary) 18%,hsl(var(--border)));
        background:hsl(var(--card));
        box-shadow:
            0 30px 70px hsl(var(--foreground)/.16),
            inset 0 1px 0 hsl(var(--background)/.70);
        transform:perspective(1500px) rotateY(-5deg) rotateX(1.5deg);
        transform-origin:center;
    }
    .dark .release-tesla-video-stage{
        box-shadow:
            0 34px 80px rgba(0,0,0,.38),
            inset 0 1px 0 rgba(255,255,255,.07);
    }
    .release-tesla-video-stage::before{
        content:"";
        position:absolute;
        inset:0;
        z-index:2;
        pointer-events:none;
        background:
            linear-gradient(90deg,hsl(var(--background)/.16),transparent 28%,transparent 72%,hsl(var(--background)/.12)),
            linear-gradient(180deg,transparent 56%,hsl(var(--background)/.44) 100%);
    }
    .release-tesla-video-stage::after{
        content:"";
        position:absolute;
        inset:0;
        z-index:3;
        pointer-events:none;
        border-radius:inherit;
        box-shadow:
            inset 0 0 0 1px rgba(255,255,255,.05),
            inset 0 -1px 0 color-mix(in srgb,var(--hero-primary) 26%,transparent);
    }
    .release-tesla-video{
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        object-fit:cover;
        object-position:center 55%;
        transform:scale(1.03);
        filter:saturate(.96) contrast(1.03);
    }
    .release-tesla-video-glowline{
        position:absolute;
        z-index:4;
        left:6%;
        right:6%;
        bottom:8%;
        height:1px;
        background:linear-gradient(90deg,transparent,var(--hero-primary),transparent);
        opacity:.62;
        box-shadow:0 0 18px color-mix(in srgb,var(--hero-primary) 62%,transparent);
        pointer-events:none;
    }
    .release-tesla-video-chip{
        position:absolute;
        z-index:5;
        top:1rem;
        right:1rem;
        display:flex;
        align-items:center;
        gap:.55rem;
        border:1px solid color-mix(in srgb,var(--hero-primary) 18%,hsl(var(--border)));
        border-radius:9999px;
        padding:.55rem .8rem;
        background:hsl(var(--card)/.54);
        color:hsl(var(--foreground));
        box-shadow:0 12px 32px rgba(0,0,0,.12);
        backdrop-filter:blur(16px);
        -webkit-backdrop-filter:blur(16px);
    }
    .dark .release-tesla-video-chip{background:rgba(9,15,25,.44)}
    .release-tesla-video-chip-dot{
        width:.48rem;
        height:.48rem;
        border-radius:9999px;
        background:var(--hero-primary);
        box-shadow:0 0 14px color-mix(in srgb,var(--hero-primary) 72%,transparent);
    }
    .release-tesla-video-chip span:last-child{
        font-size:.68rem;
        font-weight:700;
        letter-spacing:.04em;
    }
    .release-tesla-video-caption{
        position:absolute;
        z-index:5;
        left:1.15rem;
        bottom:1rem;
        max-width:70%;
        border:1px solid hsl(var(--border)/.65);
        border-radius:1rem;
        padding:.7rem .85rem;
        background:hsl(var(--card)/.46);
        backdrop-filter:blur(14px);
        -webkit-backdrop-filter:blur(14px);
    }
    .dark .release-tesla-video-caption{background:rgba(7,13,22,.42)}
    .release-tesla-video-caption p:first-child{
        color:hsl(var(--foreground));
        font-size:.72rem;
        font-weight:700;
    }
    .release-tesla-video-caption p:last-child{
        margin-top:.18rem;
        color:hsl(var(--muted-foreground));
        font-size:.58rem;
        line-height:1.35;
    }

    @media(max-width:759px){
        .release-tesla-video-wrap{min-height:auto;padding:.75rem 0 1.25rem}
        .release-tesla-video-stage{
            width:min(100%,42rem);
            transform:none;
            border-radius:1.5rem;
        }
    }


    /* Slide 2 — centered electric mobility showcase */
    .release-hero-secondary-text{color:var(--hero-primary)}

    .release-auto-showcase{
        position:relative;
        min-height:35.5rem;
        display:flex;
        flex-direction:column;
        justify-content:center;
        overflow:hidden;
        isolation:isolate;
    }
    .release-auto-showcase::before{
        content:"";
        position:absolute;
        inset:8% 7% 10%;
        border-radius:2.25rem;
        background:
            linear-gradient(90deg,
                transparent 0 16%,
                hsl(var(--foreground)/.035) 16% 32%,
                transparent 32% 49%,
                hsl(var(--foreground)/.028) 49% 65%,
                transparent 65% 100%);
        border:1px solid hsl(var(--border)/.45);
        pointer-events:none;
    }
    .dark .release-auto-showcase::before{
        background:
            linear-gradient(90deg,
                transparent 0 16%,
                rgba(255,255,255,.025) 16% 32%,
                transparent 32% 49%,
                rgba(255,255,255,.018) 49% 65%,
                transparent 65% 100%);
        border-color:rgba(255,255,255,.045);
    }
    .release-auto-showcase::after{
        content:"";
        position:absolute;
        inset:18% 15% 5%;
        border-radius:9999px;
        background:
            radial-gradient(circle at 50% 48%,color-mix(in srgb,var(--hero-primary) 13%,transparent),transparent 40%),
            radial-gradient(circle at 75% 72%,color-mix(in srgb,var(--hero-primary) 7%,transparent),transparent 38%);
        filter:blur(76px);
        pointer-events:none;
        opacity:.8;
    }

    .release-auto-copy{
        position:relative;
        z-index:2;
        max-width:52rem;
        margin-inline:auto;
        text-align:center;
    }
    .release-auto-title{
        margin-top:.75rem;
        font-size:clamp(2.4rem,4.3vw,4.45rem);
        line-height:.96;
        letter-spacing:-.055em;
        color:hsl(var(--foreground));
    }
    .release-auto-description{
        max-width:42rem;
        margin:1rem auto 0;
        color:hsl(var(--muted-foreground));
        font-size:.9rem;
        line-height:1.75;
    }

    .release-auto-cars-stage{
        position:relative;
        z-index:2;
        display:flex;
        justify-content:center;
        align-items:center;
        min-height:19rem;
        margin-top:.35rem;
    }
    .release-auto-cars-stage::before{
        content:"";
        position:absolute;
        left:14%;
        right:14%;
        bottom:6%;
        height:2.6rem;
        border-radius:9999px;
        background:color-mix(in srgb,var(--hero-primary) 10%,transparent);
        filter:blur(25px);
        opacity:.7;
    }
    .release-auto-cars{
        position:relative;
        z-index:1;
        display:block;
        width:min(92%,64rem);
        height:auto;
        object-fit:contain;
        transform:translateY(.35rem);
        filter:drop-shadow(0 24px 26px hsl(var(--foreground)/.17));
        transition:transform .45s ease,filter .45s ease;
    }
    .dark .release-auto-cars{
        filter:
            drop-shadow(0 26px 30px rgba(0,0,0,.42))
            drop-shadow(0 0 20px color-mix(in srgb,var(--hero-primary) 8%,transparent));
    }
    .release-hero-slide[data-active="true"] .release-auto-cars{
        animation:autoCarsArrive .85s cubic-bezier(.2,.75,.2,1) both;
    }

    .release-auto-bottom{
        position:relative;
        z-index:3;
        display:grid;
        grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);
        align-items:center;
        gap:1rem;
        margin-top:-.2rem;
    }
    .release-auto-features{
        display:flex;
        align-items:center;
        gap:1rem;
        justify-self:start;
    }
    .release-auto-feature{
        min-width:0;
    }
    .release-auto-feature-label{
        font-size:.58rem;
        text-transform:uppercase;
        letter-spacing:.14em;
        color:hsl(var(--muted-foreground));
    }
    .release-auto-feature-value{
        margin-top:.22rem;
        font-size:.8rem;
        font-weight:700;
        color:hsl(var(--foreground));
        white-space:nowrap;
    }
    .release-auto-divider{
        width:1px;
        height:2rem;
        background:color-mix(in srgb,var(--hero-primary) 48%,hsl(var(--border)));
    }
    .release-auto-cta{
        justify-self:center;
    }
    .release-auto-pager{
        justify-self:end;
        display:flex;
        align-items:center;
        gap:.55rem;
        color:hsl(var(--muted-foreground));
    }
    .release-auto-pager span{
        width:2.2rem;
        height:2.2rem;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:9999px;
        border:1px solid hsl(var(--border));
        background:hsl(var(--card)/.48);
    }

    @keyframes autoCarsArrive{
        from{opacity:0;transform:translateY(1.4rem) scale(.975)}
        to{opacity:1;transform:translateY(.35rem) scale(1)}
    }

    @media(max-width:900px){
        .release-auto-showcase{min-height:auto;padding:.75rem 0 1rem}
        .release-auto-cars-stage{min-height:16rem}
        .release-auto-cars{width:min(100%,55rem)}
        .release-auto-bottom{
            grid-template-columns:1fr;
            justify-items:center;
            margin-top:.5rem;
        }
        .release-auto-features{justify-self:center;flex-wrap:wrap;justify-content:center}
        .release-auto-cta{justify-self:center}
        .release-auto-pager{display:none}
    }
    @media(max-width:600px){
        .release-auto-title{font-size:clamp(2.25rem,11vw,3.35rem)}
        .release-auto-description{font-size:.82rem;line-height:1.65}
        .release-auto-cars-stage{min-height:12.5rem}
        .release-auto-cars{width:108%;max-width:none}
        .release-auto-feature-value{font-size:.72rem}
    }
    @media(prefers-reduced-motion:reduce){
        .release-hero-slide[data-active="true"] .release-auto-cars{animation:none}
    }


    /* Slide 3 — trade invest bot showcase */
    .release-bot-showcase{
        position:relative;
        min-height:34rem;
        display:grid;
        grid-template-columns:minmax(0, .96fr) minmax(0, 1.04fr);
        align-items:center;
        gap:2rem;
        isolation:isolate;
    }
    .release-bot-showcase::before{
        content:"";
        position:absolute;
        inset:8% 0 4%;
        background:
            radial-gradient(circle at 18% 28%,color-mix(in srgb,var(--hero-primary) 10%,transparent),transparent 26%),
            radial-gradient(circle at 82% 24%,color-mix(in srgb,var(--hero-secondary) 10%,transparent),transparent 30%),
            radial-gradient(circle at 76% 74%,color-mix(in srgb,var(--hero-primary) 7%,transparent),transparent 34%);
        filter:blur(70px);
        opacity:.86;
        pointer-events:none;
    }
    .release-bot-copy{
        position:relative;
        z-index:2;
        max-width:35rem;
    }
    .release-bot-title{
        margin-top:.85rem;
        font-size:clamp(2.5rem,4vw,4.7rem);
        line-height:.95;
        letter-spacing:-.055em;
        color:hsl(var(--foreground));
    }
    .release-bot-description{
        margin-top:1.15rem;
        max-width:34rem;
        color:hsl(var(--muted-foreground));
        font-size:.95rem;
        line-height:1.78;
    }
    .release-bot-pills{
        margin-top:1.35rem;
        display:flex;
        flex-wrap:wrap;
        gap:.65rem;
    }
    .release-bot-pill{
        display:inline-flex;
        align-items:center;
        gap:.5rem;
        padding:.55rem .8rem;
        border-radius:9999px;
        border:1px solid color-mix(in srgb,var(--hero-primary) 16%,hsl(var(--border)));
        background:hsl(var(--card)/.46);
        color:hsl(var(--foreground));
        backdrop-filter:blur(12px);
        -webkit-backdrop-filter:blur(12px);
        font-size:.72rem;
        font-weight:600;
        letter-spacing:.01em;
    }
    .dark .release-bot-pill{background:rgba(9,15,25,.36)}
    .release-bot-art{
        position:relative;
        z-index:2;
        display:flex;
        justify-content:center;
        align-items:center;
        min-height:22rem;
    }
    .release-bot-art::before{
        content:"";
        position:absolute;
        left:8%;
        right:8%;
        bottom:10%;
        height:2.7rem;
        border-radius:9999px;
        background:color-mix(in srgb,var(--hero-primary) 12%,transparent);
        filter:blur(28px);
        opacity:.72;
    }
    .release-bot-image{
        position:relative;
        z-index:1;
        display:block;
        width:min(100%,54rem);
        height:auto;
        object-fit:contain;
        filter:
            drop-shadow(0 26px 30px hsl(var(--foreground)/.16))
            drop-shadow(0 0 18px color-mix(in srgb,var(--hero-primary) 7%,transparent));
        transform:translateY(.15rem);
    }
    .dark .release-bot-image{
        filter:
            drop-shadow(0 30px 34px rgba(0,0,0,.38))
            drop-shadow(0 0 20px color-mix(in srgb,var(--hero-primary) 10%,transparent));
    }
    .release-hero-slide[data-active="true"] .release-bot-image{
        animation:tradeBotArrive .8s cubic-bezier(.2,.8,.2,1) both;
    }

    .release-bot-metrics{
        margin-top:1.6rem;
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:1rem;
        max-width:34rem;
    }
    .release-bot-metric{
        min-width:0;
        padding-right:.7rem;
        border-right:1px solid color-mix(in srgb,var(--hero-primary) 20%,hsl(var(--border)));
    }
    .release-bot-metric:last-child{border-right:none;padding-right:0}
    .release-bot-metric-label{
        font-size:.58rem;
        text-transform:uppercase;
        letter-spacing:.16em;
        color:hsl(var(--muted-foreground));
    }
    .release-bot-metric-value{
        margin-top:.28rem;
        font-size:.92rem;
        font-weight:700;
        color:hsl(var(--foreground));
        white-space:nowrap;
    }

    @keyframes tradeBotArrive{
        from{opacity:0;transform:translateY(1rem) scale(.985)}
        to{opacity:1;transform:translateY(.15rem) scale(1)}
    }

    @media(max-width:980px){
        .release-bot-showcase{
            min-height:auto;
            grid-template-columns:1fr;
            gap:1.25rem;
            padding:.3rem 0 1rem;
        }
        .release-bot-copy{
            max-width:none;
            text-align:center;
            margin-inline:auto;
        }
        .release-bot-description,
        .release-bot-metrics{margin-left:auto;margin-right:auto}
        .release-bot-pills{justify-content:center}
        .release-bot-art{min-height:17rem}
    }
    @media(max-width:600px){
        .release-bot-title{font-size:clamp(2.2rem,10vw,3.6rem)}
        .release-bot-description{font-size:.84rem;line-height:1.68}
        .release-bot-metrics{
            grid-template-columns:1fr;
            gap:.8rem;
        }
        .release-bot-metric{
            border-right:none;
            padding-right:0;
            text-align:center;
        }
    }
    @media(prefers-reduced-motion:reduce){
        .release-hero-slide[data-active="true"] .release-bot-image{animation:none}
    }


    /* FINAL HERO COMPACT NORMALIZATION R1
       One shared canvas for all 3 slides: equal height, compact spacing, stable nav gap. */
    @media(min-width:760px){
        .release-hero{
            padding-top:1.15rem !important;
        }

        .release-hero > .relative.mx-auto{
            padding-top:1.15rem !important;
            padding-bottom:1rem !important;
        }

        .release-hero-shell{
            height:29.75rem !important;
            min-height:29.75rem !important;
        }

        .release-hero-slide,
        .release-hero-slide[data-active="true"]{
            position:absolute !important;
            inset:0 !important;
            height:100% !important;
        }

        .release-hero-slide[data-active="true"]{
            display:block;
        }

        .release-hero-layout,
        .release-auto-showcase,
        .release-bot-showcase{
            height:100% !important;
            min-height:0 !important;
        }

        .release-hero-layout{
            grid-template-columns:minmax(0,.91fr) minmax(0,1.09fr) !important;
            gap:1.65rem !important;
            align-items:center !important;
        }

        .release-hero-copy,
        .release-bot-copy{
            align-self:center;
        }

        .release-hero-title,
        .release-auto-title,
        .release-bot-title{
            font-size:clamp(2.35rem,3.75vw,4rem) !important;
            line-height:.97 !important;
            letter-spacing:-.052em !important;
        }

        .release-hero-copy > p.mt-5,
        .release-bot-description{
            margin-top:.9rem !important;
            line-height:1.62 !important;
        }

        .release-hero-copy > .mt-7,
        .release-bot-copy > .mt-7{
            margin-top:1.05rem !important;
        }

        .release-hero-copy > .mt-6,
        .release-bot-pills{
            margin-top:.9rem !important;
        }

        /* Slide 1 */
        .release-growth-wrap{
            min-height:0 !important;
            height:100% !important;
            padding:.2rem 0 .45rem !important;
        }

        .release-growth-svg{
            width:min(100%,42.5rem) !important;
            max-height:28rem !important;
        }

        /* Slide 2 */
        .release-auto-showcase{
            padding:.1rem 0 .25rem !important;
            justify-content:center !important;
        }

        .release-auto-copy{
            max-width:46rem !important;
        }

        .release-auto-description{
            margin-top:.65rem !important;
            line-height:1.58 !important;
        }

        .release-auto-cars-stage{
            min-height:14.2rem !important;
            margin-top:-.1rem !important;
        }

        .release-auto-cars{
            width:min(86%,56rem) !important;
            max-height:15.5rem !important;
            object-fit:contain !important;
        }

        .release-auto-bottom{
            margin-top:-.65rem !important;
        }

        /* Slide 3 */
        .release-bot-showcase{
            grid-template-columns:minmax(0,.93fr) minmax(0,1.07fr) !important;
            gap:1.35rem !important;
        }

        .release-bot-description{
            font-size:.88rem !important;
        }

        .release-bot-pills{
            gap:.45rem !important;
        }

        .release-bot-pill{
            padding:.46rem .68rem !important;
            font-size:.67rem !important;
        }

        .release-bot-metrics{
            margin-top:1rem !important;
            gap:.7rem !important;
        }

        .release-bot-art{
            min-height:0 !important;
            height:100% !important;
        }

        .release-bot-image{
            width:min(100%,47rem) !important;
            max-height:25rem !important;
            object-fit:contain !important;
        }

        /* Shared selector strip */
        .release-hero-tabs{
            margin-top:.72rem !important;
            gap:.55rem !important;
        }

        .release-hero-tab{
            padding:.62rem .7rem !important;
            border-radius:.9rem !important;
        }

        .release-hero-number{
            height:1.85rem !important;
            width:1.85rem !important;
        }
    }

    @media(min-width:1180px){
        .release-hero-shell{
            height:30.25rem !important;
            min-height:30.25rem !important;
        }

        .release-growth-svg{
            width:min(103%,43.5rem) !important;
        }

        .release-auto-cars{
            width:min(88%,58rem) !important;
        }

        .release-bot-image{
            width:min(104%,49rem) !important;
        }
    }

    @media(max-width:759px){
        .release-hero{
            padding-top:.8rem !important;
        }

        .release-hero > .relative.mx-auto{
            padding-top:.8rem !important;
            padding-bottom:.8rem !important;
        }

        .release-hero-shell{
            height:auto !important;
            min-height:0 !important;
        }

        .release-hero-slide[data-active="true"]{
            position:relative !important;
            inset:auto !important;
            height:auto !important;
        }

        .release-hero-layout,
        .release-auto-showcase,
        .release-bot-showcase{
            height:auto !important;
        }

        .release-hero-tabs{
            margin-top:.8rem !important;
        }
    }


    /* FINAL HERO NAV SEPARATION R2
       External spacing belongs between the navigation and hero; do not inflate slide internals. */
    @media(min-width:760px){
        .release-hero{
            margin-top:1.65rem !important;
            padding-top:.35rem !important;
        }

        .release-hero > .relative.mx-auto{
            padding-top:.55rem !important;
            padding-bottom:.9rem !important;
        }

        .release-hero-shell{
            height:28.35rem !important;
            min-height:28.35rem !important;
        }

        .release-growth-wrap{
            padding:0 0 .3rem !important;
        }

        .release-auto-showcase{
            padding:0 0 .15rem !important;
        }

        .release-bot-showcase{
            padding:0 !important;
        }

        .release-hero-tabs{
            margin-top:.62rem !important;
        }
    }

    @media(min-width:1180px){
        .release-hero{
            margin-top:1.85rem !important;
        }

        .release-hero-shell{
            height:28.75rem !important;
            min-height:28.75rem !important;
        }
    }

    @media(max-width:759px){
        .release-hero{
            margin-top:.9rem !important;
            padding-top:.25rem !important;
        }

        .release-hero > .relative.mx-auto{
            padding-top:.5rem !important;
        }
    }


    /* HOME HERO SHELL OWNERSHIP R3
       The public layout no longer reserves the fixed-header spacer for this page.
       The hero owns its own navbar clearance so its background can begin at viewport top. */
    .release-hero{
        margin-top:0 !important;
        padding-top:5.15rem !important;
    }

    .release-hero > .relative.mx-auto{
        padding-top:.7rem !important;
        padding-bottom:.9rem !important;
    }

    @media(min-width:760px){
        .release-hero-shell{
            height:27.85rem !important;
            min-height:27.85rem !important;
        }
    }

    @media(min-width:1180px){
        .release-hero{
            padding-top:5.2rem !important;
        }

        .release-hero-shell{
            height:28.15rem !important;
            min-height:28.15rem !important;
        }
    }

    @media(max-width:759px){
        .release-hero{
            margin-top:0 !important;
            padding-top:4.75rem !important;
        }

        .release-hero > .relative.mx-auto{
            padding-top:.6rem !important;
            padding-bottom:.8rem !important;
        }

        .release-hero-shell{
            height:auto !important;
            min-height:0 !important;
        }
    }


    /* HERO CONTROLS RELOCATION R4 */
    .release-hero-controls{
        position:absolute;
        z-index:20;
        right:1.75rem;
        bottom:5.55rem;
        display:flex;
        align-items:center;
        gap:.55rem;
    }

    .release-hero-controls button{
        height:2.55rem;
        width:2.55rem;
        box-shadow:0 12px 28px hsl(var(--foreground)/.10);
        backdrop-filter:blur(14px);
        -webkit-backdrop-filter:blur(14px);
    }

    .release-hero-controls [data-release-prev]{
        background:hsl(var(--card)/.72);
    }

    .release-hero-controls [data-release-next]{
        box-shadow:
            0 12px 30px color-mix(in srgb,var(--hero-primary) 20%,transparent),
            inset 0 0 0 1px color-mix(in srgb,var(--hero-primary) 18%,transparent);
    }

    /* Old Slide 2 decorative arrows are intentionally removed from the visual system. */
    .release-auto-pager{
        display:none !important;
    }

    @media(max-width:759px){
        .release-hero-controls{
            position:relative;
            right:auto;
            bottom:auto;
            justify-content:flex-end;
            margin-top:.7rem;
        }
    }

</style>


<section class="release-hero border-b border-border" data-release-hero>
    <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-14 sm:px-6 sm:pb-10 sm:pt-16 lg:px-8 lg:pt-20 xl:pt-24">
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

                        <svg class="release-growth-svg" viewBox="0 0 840 590" role="img" aria-labelledby="releaseGrowthTitle releaseGrowthDesc">
                            <title id="releaseGrowthTitle">{{ localize('ui.release.hero.growth.title', 'Financial growth illustration') }}</title>
                            <desc id="releaseGrowthDesc">{{ localize('ui.release.hero.growth.desc', 'A glassmorphism growth display with stronger perspective and layered financial UI.') }}</desc>

                            <defs>
                                <linearGradient id="growthPanelFill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" class="growth-panel-stop-a"/>
                                    <stop offset="100%" class="growth-panel-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthRearFill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" class="growth-rear-stop-a"/>
                                    <stop offset="100%" class="growth-rear-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthBaseFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" class="growth-base-stop-a"/>
                                    <stop offset="100%" class="growth-base-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthBarFill" x1="0" y1="1" x2="0" y2="0">
                                    <stop offset="0%" class="growth-bar-stop-a"/>
                                    <stop offset="100%" class="growth-bar-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthTileFill" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" class="growth-tile-stop-a"/>
                                    <stop offset="100%" class="growth-tile-stop-b"/>
                                </linearGradient>

                                <linearGradient id="growthEdgeHighlight" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="white" stop-opacity=".68"/>
                                    <stop offset="34%" stop-color="white" stop-opacity=".10"/>
                                    <stop offset="72%" stop-color="var(--brand-primary)" stop-opacity=".12"/>
                                    <stop offset="100%" stop-color="var(--brand-primary)" stop-opacity=".60"/>
                                </linearGradient>

                                <linearGradient id="growthSoftHighlight" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0%" stop-color="white" stop-opacity=".30"/>
                                    <stop offset="40%" stop-color="white" stop-opacity=".05"/>
                                    <stop offset="100%" stop-color="white" stop-opacity="0"/>
                                </linearGradient>

                                <filter id="growthGlow" x="-40%" y="-40%" width="180%" height="180%">
                                    <feGaussianBlur stdDeviation="7" result="blur"/>
                                    <feMerge>
                                        <feMergeNode in="blur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>

                                <filter id="growthSoftShadow" x="-25%" y="-25%" width="160%" height="175%">
                                    <feDropShadow dx="0" dy="22" stdDeviation="18" flood-opacity=".24"/>
                                </filter>

                                <filter id="growthBaseShadow" x="-30%" y="-50%" width="180%" height="220%">
                                    <feGaussianBlur stdDeviation="14"/>
                                </filter>
                            </defs>

                            <g aria-hidden="true">
                                <path class="release-growth-orbit release-growth-orbit-a" d="M10 390 C168 519 603 510 824 202"/>
                                <path class="release-growth-orbit release-growth-orbit-b" d="M26 435 C276 574 677 454 826 264"/>
                                <circle cx="84" cy="421" r="4.5" class="growth-orbit-dots release-growth-orbit-dot"/>
                                <circle cx="766" cy="299" r="4.5" class="growth-orbit-dots release-growth-orbit-dot"/>
                                <path class="growth-ambient" d="M140 96 C226 58 365 39 502 45"/>
                                <path class="growth-ambient" d="M628 104 C700 124 746 159 789 214"/>
                            </g>

                            <ellipse cx="460" cy="505" rx="304" ry="30" fill="var(--brand-primary)" opacity=".12" filter="url(#growthBaseShadow)"/>

                            <g class="release-growth-base" filter="url(#growthSoftShadow)">
                                <path class="growth-base-top" d="M175 452 L706 420 L789 468 L246 506 Q214 506 193 492 L163 471 Q154 466 175 452 Z"/>
                                <path class="growth-base-front" d="M193 492 Q214 506 245 506 L789 470 L779 494 Q774 506 759 509 L252 545 Q220 546 198 528 L170 505 Q159 496 163 471 Z"/>
                                <path class="growth-base-edge" d="M193 492 Q214 506 245 506 L786 470"/>
                            </g>

                            <g class="release-growth-rear">
                                <path class="growth-rear-panel" d="M168 104 Q171 70 203 65 L717 34 Q749 31 753 63 L709 422 Q706 449 677 452 L141 482 Q112 482 116 450 Z"/>
                                <path class="growth-panel-inner-edge" d="M184 117 L706 88 L669 407 L137 439"/>
                            </g>

                            <g class="release-growth-card" filter="url(#growthSoftShadow)">
                                <path d="M141 82 Q145 50 177 46 L729 15 Q761 12 765 45 L720 414 Q717 443 686 447 L117 476 Q88 476 93 444 Z"
                                      fill="url(#growthPanelFill)" class="growth-panel-stroke"/>
                                <path class="growth-panel-highlight" d="M147 84 Q149 57 179 52 L724 22 Q751 20 754 44"/>
                                <path class="growth-panel-highlight-soft" d="M174 76 L607 50"/>
                                <path class="growth-panel-inner-edge" d="M123 111 L730 79 L694 414 L112 445"/>

                                <g class="growth-grid" opacity=".52">
                                    <line x1="163" y1="177" x2="693" y2="147"/>
                                    <line x1="156" y1="236" x2="687" y2="206"/>
                                    <line x1="149" y1="295" x2="680" y2="265"/>
                                    <line x1="142" y1="354" x2="673" y2="324"/>
                                    <line x1="217" y1="145" x2="194" y2="381"/>
                                    <line x1="310" y1="140" x2="287" y2="376"/>
                                    <line x1="403" y1="135" x2="380" y2="370"/>
                                    <line x1="496" y1="129" x2="473" y2="364"/>
                                    <line x1="589" y1="124" x2="566" y2="358"/>
                                </g>

                                <g class="release-growth-badge" data-growth-badge>
                                    <rect x="168" y="98" width="214" height="62" rx="20" class="growth-badge-bg"/>
                                    <circle cx="198" cy="129" r="19" class="growth-accent-fill"/>
                                    <path d="M190 137 L206 121 M197 121 H206 V130" class="growth-arrow-mark"/>
                                    <text x="226" y="136" class="growth-count-text">+<tspan data-growth-count>0</tspan>%</text>
                                    <text x="316" y="135" class="growth-label-text">growth</text>
                                </g>

                                <g class="release-growth-bars">
                                    <g transform="skewX(-8)">
                                        <rect class="growth-bar growth-bar-1" x="193" y="325" width="50" height="44" rx="10" fill="url(#growthBarFill)"/>
                                        <rect class="growth-bar growth-bar-2" x="274" y="291" width="50" height="78" rx="10" fill="url(#growthBarFill)"/>
                                        <rect class="growth-bar growth-bar-3" x="355" y="257" width="50" height="112" rx="10" fill="url(#growthBarFill)"/>
                                        <rect class="growth-bar growth-bar-4" x="436" y="218" width="50" height="151" rx="10" fill="url(#growthBarFill)"/>
                                        <rect class="growth-bar growth-bar-5" x="517" y="173" width="50" height="196" rx="10" fill="url(#growthBarFill)"/>
                                        <rect class="growth-bar growth-bar-6" x="598" y="116" width="50" height="253" rx="10" fill="url(#growthBarFill)"/>
                                    </g>
                                </g>

                                <path class="release-growth-line"
                                      d="M187 329 C226 309 250 289 282 297 C316 305 339 269 374 264 C410 258 427 281 463 245 C498 210 520 223 558 194 C590 169 609 146 640 119 C662 101 678 82 706 60"/>

                                <g class="release-growth-nodes">
                                    <circle class="growth-node growth-node-1" cx="187" cy="329" r="7"/>
                                    <circle class="growth-node growth-node-2" cx="282" cy="297" r="7"/>
                                    <circle class="growth-node growth-node-3" cx="374" cy="264" r="7"/>
                                    <circle class="growth-node growth-node-4" cx="463" cy="245" r="7"/>
                                    <circle class="growth-node growth-node-5" cx="558" cy="194" r="7"/>
                                    <circle class="growth-node growth-node-6" cx="640" cy="119" r="7"/>
                                    <path d="M698 72 L720 49 L713 81 Z" class="growth-accent-fill"/>
                                </g>

                                <g class="release-growth-tiles">
                                    <g transform="translate(139 383) skewX(-8)">
                                        <rect width="120" height="68" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <rect x="25" y="40" width="10" height="14" rx="3" class="growth-accent-fill"/>
                                        <rect x="44" y="31" width="10" height="23" rx="3" class="growth-accent-fill"/>
                                        <rect x="63" y="22" width="10" height="32" rx="3" class="growth-accent-fill"/>
                                    </g>

                                    <g transform="translate(271 375) skewX(-8)">
                                        <rect width="120" height="68" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <circle cx="60" cy="34" r="20" class="growth-icon-muted-fill"/>
                                        <path d="M60 34 L60 14 A20 20 0 0 1 80 34 Z" class="growth-panel-icon-fill"/>
                                    </g>

                                    <g transform="translate(403 367) skewX(-8)">
                                        <rect width="120" height="68" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <rect x="41" y="16" width="38" height="39" rx="7" fill="none" class="growth-accent-stroke"/>
                                        <line x1="50" y1="28" x2="70" y2="28" class="growth-accent-stroke"/>
                                        <line x1="50" y1="38" x2="70" y2="38" class="growth-accent-stroke"/>
                                        <line x1="50" y1="48" x2="64" y2="48" class="growth-accent-stroke"/>
                                    </g>

                                    <g transform="translate(535 359) skewX(-8)">
                                        <rect width="120" height="68" rx="18" fill="url(#growthTileFill)" class="growth-tile-stroke"/>
                                        <circle cx="60" cy="34" r="13" fill="none" class="growth-icon-muted-stroke"/>
                                        <path d="M60 12 V21 M60 47 V56 M37 34 H46 M74 34 H83 M43 18 L49 24 M71 44 L77 50 M77 18 L71 24 M49 44 L43 50" class="growth-icon-muted-stroke"/>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </div>
                </div>
            </article>

            {{-- 02 / AUTOMOTIVE --}}
                        {{-- 02 / ELECTRIC AUTOMOBILE --}}
            {{-- 02 / ELECTRIC AUTOMOBILE — VIDEO TRIAL --}}
            {{-- 02 / ELECTRIC MOBILITY SHOWCASE --}}
            <article class="release-hero-slide" data-release-slide="1" data-active="false">
                <div class="release-auto-showcase">
                    <div class="release-auto-copy">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">
                            {{ localize('ui.release.hero.auto.eyebrow', 'Electric mobility · connected automotive') }}
                        </p>

                        <h2 class="release-auto-title font-semibold">
                            {{ localize('ui.release.hero.auto.discover', 'Drive Into') }}
                            <span class="release-hero-primary-text">{{ localize('ui.release.hero.auto.premium', 'Tomorrow.') }}</span>
                        </h2>

                        <p class="release-auto-description">
                            {{ localize('ui.release.hero.auto.copy', 'Explore a connected automotive experience built around electric mobility, modern vehicle technology and simpler access to the cars you want.') }}
                        </p>
                    </div>

                    <div class="release-auto-cars-stage">
                        <img
                            src="{{ asset('assets/hero/electric-mobility-trio.png') }}"
                            alt="{{ localize('ui.release.hero.auto.visual_alt', 'A white electric SUV with red and white electric sedans') }}"
                            class="release-auto-cars"
                            loading="eager"
                            decoding="async"
                        >
                    </div>

                    <div class="release-auto-bottom">
                        <div class="release-auto-features" aria-label="Electric mobility highlights">
                            <div class="release-auto-feature">
                                <p class="release-auto-feature-label">{{ localize('ui.release.hero.auto.highlight1_label', 'Mobility') }}</p>
                                <p class="release-auto-feature-value">{{ localize('ui.release.hero.auto.highlight1', 'Electric-first') }}</p>
                            </div>
                            <span class="release-auto-divider" aria-hidden="true"></span>
                            <div class="release-auto-feature">
                                <p class="release-auto-feature-label">{{ localize('ui.release.hero.auto.highlight2_label', 'Experience') }}</p>
                                <p class="release-auto-feature-value">{{ localize('ui.release.hero.auto.highlight2', 'Connected') }}</p>
                            </div>
                            <span class="release-auto-divider" aria-hidden="true"></span>
                            <div class="release-auto-feature">
                                <p class="release-auto-feature-label">{{ localize('ui.release.hero.auto.highlight3_label', 'Selection') }}</p>
                                <p class="release-auto-feature-value">{{ localize('ui.release.hero.auto.highlight3', 'Multiple classes') }}</p>
                            </div>
                        </div>

                        <a href="{{ $automotiveUrl }}" class="release-auto-cta release-hero-primary-button inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold">
                            {{ localize('ui.release.hero.auto.browse', 'Explore Vehicles') }}
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>

                        
                    </div>
                </div>
            </article>

            {{-- 03 / TRADE · INVEST · BOTS --}}
            <article class="release-hero-slide" data-release-slide="2" data-active="false">
                <div class="release-bot-showcase">
                    <div class="release-bot-copy">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-muted-foreground">
                            {{ localize('ui.release.hero.bots.eyebrow', 'Trading · investing · automation') }}
                        </p>

                        <h2 class="release-bot-title font-semibold">
                            {{ localize('ui.release.hero.bots.titleA', 'Trade.') }}
                            <span class="release-hero-primary-text">{{ localize('ui.release.hero.bots.titleB', 'Invest.') }}</span>
                            <span class="block">{{ localize('ui.release.hero.bots.titleC', 'Automate With') }} <span class="release-hero-primary-text">{{ localize('ui.release.hero.bots.titleD', 'Confidence.') }}</span></span>
                        </h2>

                        <p class="release-bot-description">
                            {{ localize('ui.release.hero.bots.copy', 'Combine live market experience, connected investment tools and intelligent automation inside one premium fintech environment built for modern decision-making.') }}
                        </p>

                        <div class="release-bot-pills" aria-label="Capabilities">
                            <span class="release-bot-pill"><i data-lucide="candlestick-chart" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>{{ localize('ui.release.hero.bots.pill1', 'Trade global assets') }}</span>
                            <span class="release-bot-pill"><i data-lucide="wallet" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>{{ localize('ui.release.hero.bots.pill2', 'Invest with clarity') }}</span>
                            <span class="release-bot-pill"><i data-lucide="bot" class="h-3.5 w-3.5" style="color:var(--brand-primary)"></i>{{ localize('ui.release.hero.bots.pill3', 'Automation workflows') }}</span>
                        </div>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ $workspaceUrl ?? $registerUrl }}" class="release-hero-primary-button inline-flex h-11 items-center justify-center gap-2 rounded-xl px-5 text-sm font-semibold">
                                {{ auth()->check() ? localize('ui.common.open_workspace', 'Open workspace') : localize('ui.release.hero.register', 'Get started') }}
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                            <a href="{{ $marketsUrl ?? $tradingUrl ?? $workspaceUrl ?? $registerUrl }}" class="release-hero-secondary-button inline-flex h-11 items-center justify-center rounded-xl px-5 text-sm font-semibold">
                                {{ localize('ui.release.hero.bots.learn_more', 'Explore markets') }}
                            </a>
                        </div>

                        <div class="release-bot-metrics">
                            <div class="release-bot-metric">
                                <p class="release-bot-metric-label">{{ localize('ui.release.hero.bots.metric1_label', 'Coverage') }}</p>
                                <p class="release-bot-metric-value">{{ localize('ui.release.hero.bots.metric1_value', 'Trade · Invest · Automate') }}</p>
                            </div>
                            <div class="release-bot-metric">
                                <p class="release-bot-metric-label">{{ localize('ui.release.hero.bots.metric2_label', 'Experience') }}</p>
                                <p class="release-bot-metric-value">{{ localize('ui.release.hero.bots.metric2_value', 'Multi-device workflow') }}</p>
                            </div>
                            <div class="release-bot-metric">
                                <p class="release-bot-metric-label">{{ localize('ui.release.hero.bots.metric3_label', 'Intelligence') }}</p>
                                <p class="release-bot-metric-value">{{ localize('ui.release.hero.bots.metric3_value', 'Bot-assisted insight') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="release-bot-art">
                        <img
                            src="{{ asset('assets/hero/trade-invest-bot-showcase.png') }}"
                            alt="{{ localize('ui.release.hero.bots.visual_alt', 'Trading, investing and automation showcase') }}"
                            class="release-bot-image"
                            loading="eager"
                            decoding="async"
                        >
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
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-foreground">{{ localize('ui.release.hero.tab2', 'Electric Mobility') }}</p><p class="mt-1 truncate text-[8px] text-muted-foreground">{{ localize('ui.release.hero.tab2copy', 'Explore electric vehicles and connected mobility') }}</p></div>
                    <i data-lucide="car-front" class="ml-auto hidden h-4 w-4 text-muted-foreground sm:block"></i>
                </div>
            </button>
            <button type="button" class="release-hero-tab" data-release-thumb="2" data-active="false">
                <div class="flex items-center gap-3">
                    <span class="release-hero-number">03</span>
                    <div class="min-w-0"><p class="truncate text-xs font-semibold text-foreground">{{ localize('ui.release.hero.tab3', 'Trade · Invest · Bots') }}</p><p class="mt-1 truncate text-[8px] text-muted-foreground">{{ localize('ui.release.hero.tab3copy', 'Trade, invest and automation experience') }}</p></div>
                    <i data-lucide="layout-dashboard" class="ml-auto hidden h-4 w-4 text-muted-foreground sm:block"></i>
                </div>
            </button>
        </div>

        <div class="release-hero-controls" aria-label="Hero carousel controls">
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

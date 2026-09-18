@php
    $tabBase = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-semibold transition-colors';
    $tabActive = 'bg-foreground text-background shadow-sm';
    $tabIdle = 'text-muted-foreground hover:bg-muted hover:text-foreground';
@endphp

<div class="flex flex-wrap gap-1 rounded-xl border border-border bg-card p-1.5">
    <a href="{{ route('admin.signals.index') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.index') ? $tabActive : $tabIdle }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i>Overview</a>
    <a href="{{ route('admin.signals.candidates') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.candidates') ? $tabActive : $tabIdle }}"><i data-lucide="sparkles" class="h-4 w-4"></i>Candidates</a>
    <a href="{{ route('admin.signals.live') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.live') ? $tabActive : $tabIdle }}"><i data-lucide="radio-tower" class="h-4 w-4"></i>Live Signals</a>
    <a href="{{ route('admin.signals.recipients') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.recipients') ? $tabActive : $tabIdle }}"><i data-lucide="users-round" class="h-4 w-4"></i>Recipients</a>
    <a href="{{ route('admin.signals.history') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.history') ? $tabActive : $tabIdle }}"><i data-lucide="history" class="h-4 w-4"></i>History</a>
    <a href="{{ route('admin.signals.activity') }}" class="{{ $tabBase }} {{ request()->routeIs('admin.signals.activity') ? $tabActive : $tabIdle }}"><i data-lucide="activity" class="h-4 w-4"></i>Engine Activity</a>
</div>

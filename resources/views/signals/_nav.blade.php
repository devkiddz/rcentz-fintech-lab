<nav class="flex flex-wrap items-center gap-2" aria-label="Signals navigation">
    <a href="{{ route('signals.index') }}"
       class="ui-btn ui-btn-sm {{ request()->routeIs('signals.index','signals.show') && !request()->routeIs('signals.history') ? 'ui-btn-primary' : 'ui-btn-secondary' }}">
        <i data-lucide="radio-tower" class="h-3.5 w-3.5"></i>
        Current Signals
    </a>
    <a href="{{ route('signals.history') }}"
       class="ui-btn ui-btn-sm {{ request()->routeIs('signals.history') ? 'ui-btn-primary' : 'ui-btn-secondary' }}">
        <i data-lucide="history" class="h-3.5 w-3.5"></i>
        History
    </a>
</nav>

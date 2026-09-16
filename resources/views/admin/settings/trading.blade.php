<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">Trading Settings</h1>
            <p class="ui-lead max-w-3xl">A stable home for trading-wide policy. Current operational desks stay separate; future automation and account policy can attach here without reorganizing Settings.</p>
        </div>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="ui-kicker">Available now</p>
                    <h2 class="mt-1 text-sm font-semibold">Trading control surfaces</h2>
                </div>
                <div class="grid gap-px bg-border sm:grid-cols-2 xl:grid-cols-3">
                    @foreach([
                        [route('admin.settings.index', ['section' => 'market']),'chart-candlestick','Market Settings','Price source and automatic market behaviour.'],
                        [route('admin.trading.manual'),'mouse-pointer-click','Trading Desk','Strategy, direct admin and trade-for-user execution.'],
                        [route('admin.trading.positions'),'target','Positions','Open contract and risk visibility.'],
                        [route('admin.trading.history'),'history','Trade History','Contract lifecycle and realized outcomes.'],
                        [route('admin.copy-trading.strategies'),'users-round','Copy Trading','Current provider and strategy administration.'],
                        [route('admin.ai-bots.executions'),'bot','AI Bots','Current bot execution administration.'],
                    ] as [$href,$icon,$label,$description])
                        <a href="{{ $href }}" class="group bg-background p-4 transition hover:bg-muted/30">
                            <div class="flex items-center justify-between">
                                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-muted-foreground"></i>
                            </div>
                            <p class="mt-3 text-xs font-semibold">{{ $label }}</p>
                            <p class="mt-1 text-[9px] leading-4 text-muted-foreground">{{ $description }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="ui-panel mt-4 p-5">
                <p class="ui-kicker">Reserved settings contract</p>
                <h2 class="mt-1 text-sm font-semibold">Next policy layers</h2>
                <p class="mt-1 text-[10px] text-muted-foreground">These are deliberately reserved rather than represented by fake controls before their business rules are defined.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach(['Global execution defaults','Copy-trading policy','Bot automation policy','Approved trading accounts','Risk policy defaults','Account audit rules'] as $label)
                        <div class="rounded-xl border border-border bg-muted/10 p-3">
                            <p class="text-[10px] font-semibold">{{ $label }}</p>
                            <p class="mt-1 text-[8px] uppercase tracking-[.1em] text-muted-foreground">Reserved</p>
                        </div>
                    @endforeach
                </div>
            </section>
    </main>
</div>
</x-admin-layout>

<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Control Plane</p>
            <h1 class="ui-heading">Settings</h1>
            <p class="ui-lead max-w-3xl">One indexed home for platform configuration. Operational tools remain in their own product areas; configuration lives here.</p>
        </div>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    ['Site', $summary['site_name'] ?: 'Not set', 'globe-2'],
                    ['Market source', $summary['price_source'], 'chart-candlestick'],
                    ['KYC', $summary['kyc'] ? 'Enabled' : 'Disabled', 'badge-check'],
                    ['Email verification', $summary['email_verification'] ? 'Enabled' : 'Disabled', 'mail-check'],
                    ['Mail transport', strtoupper($summary['mail_transport']), 'send'],
                    ['Storage', $summary['storage_ready'] ? 'Available' : 'Needs attention', 'hard-drive'],
                ] as [$label,$value,$icon])
                    <div class="ui-panel p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                        </div>
                        <p class="mt-3 truncate text-sm font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="ui-panel mt-4 overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="ui-kicker">Available now</p>
                    <h2 class="mt-1 text-sm font-semibold">Configuration domains</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Each domain opens inside the same System Settings route, keeping admin navigation in one place.</p>
                </div>
                <div class="grid gap-px bg-border sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($settingsSections as $key => $section)
                        @if($section['available'] && $key !== 'overview')
                            <a href="{{ route('admin.settings.index', ['section' => $key]) }}" class="group bg-background p-4 transition hover:bg-muted/30">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-muted/20">
                                        <i data-lucide="{{ $section['icon'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-muted-foreground transition group-hover:text-foreground"></i>
                                </div>
                                <h3 class="mt-3 text-xs font-semibold">{{ $section['label'] }}</h3>
                                <p class="mt-1 text-[9px] leading-4 text-muted-foreground">{{ $section['description'] }}</p>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="ui-panel mt-4 overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="ui-kicker">Reserved architecture</p>
                    <h2 class="mt-1 text-sm font-semibold">Future settings domains</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">These slots are indexed now so future modules can join the control plane without restructuring it again.</p>
                </div>
                <div class="grid gap-px bg-border sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($settingsSections as $key => $section)
                        @if(!$section['available'])
                            <div class="bg-background p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-muted/20 text-muted-foreground">
                                        <i data-lucide="{{ $section['icon'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <span class="rounded-full border border-border px-2 py-1 text-[7px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Reserved</span>
                                </div>
                                <h3 class="mt-3 text-xs font-semibold">{{ $section['label'] }}</h3>
                                <p class="mt-1 text-[9px] leading-4 text-muted-foreground">{{ $section['description'] }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
    </main>
</div>
</x-admin-layout>

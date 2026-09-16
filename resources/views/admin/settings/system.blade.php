<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">System</h1>
            <p class="ui-lead max-w-3xl">Application maintenance and recovery controls. These actions are intentionally separated from everyday configuration.</p>
        </div>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['Environment', strtoupper($system['environment'])],
                    ['Laravel', $system['laravel']],
                    ['PHP', $system['php']],
                    ['Debug', $system['debug'] ? 'ON' : 'OFF'],
                    ['Storage', $system['storage_ready'] ? 'Available' : 'Needs attention'],
                ] as [$label,$value])
                    <div class="ui-panel p-4">
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-2 text-xs font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="ui-panel mt-4 overflow-hidden">
                <div class="border-b border-border px-4 py-3"><p class="ui-kicker">Application cache</p><h2 class="mt-1 text-sm font-semibold">Maintenance commands</h2></div>
                <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach([
                        ['admin.settings.system.cache.clear','Clear Cache','Clear application and settings cache.'],
                        ['admin.settings.system.config.clear','Clear Config','Clear cached configuration.'],
                        ['admin.settings.system.views.clear','Clear Views','Clear compiled Blade views.'],
                        ['admin.settings.system.routes.clear','Clear Routes','Clear cached route definitions.'],
                        ['admin.settings.system.optimize.clear','Clear All','Run Laravel optimize:clear and settings cache cleanup.'],
                    ] as [$routeName,$label,$description])
                        <form method="POST" action="{{ route($routeName) }}" class="rounded-xl border border-border p-3">
                            @csrf
                            <p class="text-[10px] font-semibold">{{ $label }}</p>
                            <p class="mt-1 min-h-8 text-[9px] leading-4 text-muted-foreground">{{ $description }}</p>
                            <button class="ui-btn ui-btn-secondary mt-3 w-full justify-center">Run</button>
                        </form>
                    @endforeach
                </div>
            </section>

            <section class="mt-4 grid gap-4 xl:grid-cols-2">
                <div class="ui-panel p-4">
                    <p class="ui-kicker">Storage</p>
                    <h2 class="mt-1 text-sm font-semibold">Public storage access</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Create or normalize the public storage link while preserving legacy files.</p>
                    <div class="mt-4 flex gap-2">
                        <form method="POST" action="{{ route('admin.settings.system.storage.link') }}" class="flex-1">@csrf<button class="ui-btn ui-btn-secondary w-full justify-center">Storage Link</button></form>
                        <form method="POST" action="{{ route('admin.settings.system.storage.repair') }}" class="flex-1">@csrf<button class="ui-btn ui-btn-secondary w-full justify-center">Repair Storage</button></form>
                    </div>
                </div>

                <div class="ui-panel border-red-500/20 p-4">
                    <p class="ui-kicker text-red-600">Reset</p>
                    <h2 class="mt-1 text-sm font-semibold">Restore default settings</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Rebuilds the generic settings table from SettingsSeeder. Market state and trading data are not stored in that table.</p>
                    <form method="POST" action="{{ route('admin.settings.system.defaults.reset') }}" class="mt-4">@csrf<button onclick="return confirm('Reset generic settings to defaults? This cannot be undone.')" class="ui-btn w-full justify-center border border-red-500/30 bg-red-500/10 text-red-600">Reset Defaults</button></form>
                </div>
            </section>
    </main>
</div>
</x-admin-layout>

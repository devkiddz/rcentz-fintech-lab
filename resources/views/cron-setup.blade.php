<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-foreground">Cron Setup</h2>
    </x-slot>

    @php($cronToken = config('app.cron_token'))

    <div class="mx-auto max-w-5xl space-y-6 py-4">
        <section class="ui-panel p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-tesla-600/10 text-tesla-600 dark:text-tesla-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-semibold text-foreground">Scheduled jobs</h1>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">Use cPanel Cron Jobs to call the secured endpoints below. Prefer the <code class="rounded bg-muted px-1 py-0.5">X-Cron-Token</code> request header when your command supports it.</p>
                </div>
            </div>
        </section>

        @if(!$cronToken)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                <strong>CRON_TOKEN is not configured.</strong> Cron endpoints intentionally fail closed until you add a strong token to <code>.env</code> and refresh the configuration cache.
            </div>
        @else
            <section class="ui-panel p-6">
                <h2 class="text-sm font-semibold text-foreground">Configured token</h2>
                <p class="mt-1 text-xs text-muted-foreground">Keep this secret. Rotate it if it is ever exposed.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input id="cron-token" type="password" readonly value="{{ $cronToken }}" class="ui-input font-mono text-xs">
                    <button type="button" class="ui-button-secondary" onclick="const input=document.getElementById('cron-token'); input.type=input.type==='password'?'text':'password'">Show / hide</button>
                </div>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2">
            @foreach([
                ['Run all jobs', 'cron.run-all'],
                ['Update stock quotes', 'cron.update-stock-quotes'],
                ['Fetch stock history', 'cron.fetch-stock-history'],
                ['Process stock news', 'cron.process-stock-news'],
                ['Process NAV updates', 'cron.process-nav-updates'],
                ['Cleanup old data', 'cron.cleanup-old-data'],
                ['Status check', 'cron.status'],
            ] as [$label, $routeName])
                <article class="ui-panel p-5">
                    <h3 class="text-sm font-semibold text-foreground">{{ $label }}</h3>
                    <p class="mt-2 break-all rounded-lg bg-muted p-3 font-mono text-xs text-muted-foreground">{{ route($routeName) }}?token=YOUR_CRON_TOKEN</p>
                </article>
            @endforeach
        </section>

        <section class="ui-panel p-6">
            <h2 class="text-sm font-semibold text-foreground">Recommended cPanel command</h2>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs leading-6 text-gray-200"><code>wget -q -O /dev/null "{{ route('cron.run-all') }}?token=YOUR_CRON_TOKEN"</code></pre>
            <p class="mt-3 text-xs text-muted-foreground">Run at the schedule appropriate for the data provider limits and workload. Queue workers must also be configured if <code>QUEUE_CONNECTION</code> is asynchronous.</p>
        </section>
    </div>
</x-admin-layout>

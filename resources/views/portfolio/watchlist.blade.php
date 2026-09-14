<x-user-layout>
    <x-slot name="header">Investment Watchlist</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-2xl bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-900 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Portfolio tools</p>
                    <h1 class="mt-1 text-2xl font-semibold">Investment watchlist</h1>
                    <p class="mt-2 text-sm text-white/75">Track plans you are watching and manage NAV alerts from one place.</p>
                </div>
                <a href="{{ route('investments.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-medium ring-1 ring-inset ring-white/20 transition hover:bg-white/20">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Browse investments
                </a>
            </div>
        </section>

        @if($watchlist->isEmpty())
            <div class="ui-panel p-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <i data-lucide="bookmark" class="h-5 w-5"></i>
                </div>
                <h2 class="mt-4 text-base font-semibold">Your watchlist is empty</h2>
                <p class="mt-1 text-sm text-muted-foreground">Add an investment plan to watch its NAV and optional alert level.</p>
                <a href="{{ route('investments.index') }}" class="ui-button-primary mt-5 inline-flex">
                    Explore plans
                </a>
            </div>
        @else
            <div class="ui-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">Plan</th>
                                <th class="px-5 py-3 font-medium">Current NAV</th>
                                <th class="px-5 py-3 font-medium">Alert</th>
                                <th class="px-5 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($watchlist as $item)
                                @php($plan = $item->investmentPlan)
                                @continue(!$plan)
                                <tr class="transition hover:bg-muted/30">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('investments.show', $plan) }}" class="font-medium text-foreground hover:text-tesla-600 dark:hover:text-tesla-400">{{ $plan->name }}</a>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ $plan->category }} · {{ ucfirst(str_replace('_', ' ', $plan->risk_level)) }} risk</p>
                                    </td>
                                    <td class="px-5 py-4 font-medium">${{ number_format((float) $plan->nav, 4) }}</td>
                                    <td class="px-5 py-4 text-muted-foreground">
                                        @if($item->alert_nav)
                                            {{ ucfirst($item->alert_type) }} ${{ number_format((float) $item->alert_nav, 4) }}
                                        @else
                                            No alert
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('investments.show', $plan) }}" class="ui-button-secondary px-3 py-2 text-xs">View</a>
                                            <form method="POST" action="{{ route('watchlist.remove', $plan) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-50 dark:border-red-900/60 dark:text-red-300 dark:hover:bg-red-950/40">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-user-layout>

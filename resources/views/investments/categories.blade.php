<x-user-layout>
    <x-slot name="header">Investment Categories</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-2xl bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-900 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Explore</p>
                    <h1 class="mt-1 text-2xl font-semibold">Investment categories</h1>
                    <p class="mt-2 max-w-2xl text-sm text-white/75">Browse the available investment groups, then continue to the filtered marketplace.</p>
                </div>
                <a href="{{ route('investments.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white ring-1 ring-inset ring-white/20 transition hover:bg-white/20">
                    <i data-lucide="layout-grid" class="h-4 w-4"></i>
                    All plans
                </a>
            </div>
        </section>

        @if($categories->isEmpty())
            <div class="ui-panel p-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <i data-lucide="layers" class="h-5 w-5"></i>
                </div>
                <h2 class="mt-4 text-base font-semibold">No categories available</h2>
                <p class="mt-1 text-sm text-muted-foreground">Active investment categories will appear here.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($categories as $category)
                    <a href="{{ route('investments.index', ['category' => $category->name]) }}" class="ui-panel group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tesla-50 text-tesla-700 dark:bg-tesla-950/50 dark:text-tesla-300">
                                    <i data-lucide="{{ $category->icon ?: 'pie-chart' }}" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="font-semibold text-foreground transition group-hover:text-tesla-600 dark:group-hover:text-tesla-400">{{ $category->name }}</h2>
                                    <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">{{ $category->description ?: 'View investment plans in this category.' }}</p>
                                </div>
                            </div>
                            <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-muted-foreground transition group-hover:text-foreground"></i>
                        </div>
                        <div class="mt-5 border-t border-border pt-3 text-xs text-muted-foreground">
                            {{ number_format($category->investment_plans_count) }} {{ \Illuminate\Support\Str::plural('plan', $category->investment_plans_count) }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-user-layout>

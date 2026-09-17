<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="ui-kicker">Investment lifecycle</p>
                <h1 class="text-xl font-semibold">{{ $instrument->name }}</h1>
                <p class="mt-1 text-xs text-muted-foreground">{{ $instrument->symbol }} · Complete lifecycle history</p>
            </div>

            <a href="{{ route('admin.investments.control.show', $instrument) }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to investment
            </a>
        </div>
    </x-slot>

    <div class="ui-page max-w-7xl">
        <section class="ui-panel p-0 overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="ui-kicker">Audit trail</p>
                    <h2 class="mt-1 text-lg font-semibold">Lifecycle events</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">
                        Distributions and deductions for this investment, newest first.
                    </p>
                </div>

                <span class="w-fit rounded-full bg-muted px-2.5 py-1 text-[10px] font-semibold text-muted-foreground">
                    {{ $events->total() }} total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-[10px]">
                    <thead class="border-b border-border text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">When</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Method</th>
                            <th class="py-3">Configured Value</th>
                            <th class="py-3">Customers</th>
                            <th class="py-3">Total</th>
                            <th class="py-3 pr-5">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($events as $event)
                            <tr>
                                <td class="px-5 py-3 whitespace-nowrap">{{ $event->effective_at?->format('M j, Y H:i') }}</td>
                                <td class="py-3 font-semibold">{{ ucfirst($event->type) }}</td>
                                <td class="py-3">{{ $event->calculation_mode === 'fixed_per_unit' ? 'Per unit' : '% of value' }}</td>
                                <td class="py-3">
                                    {{ $event->calculation_mode === 'percent_current_value'
                                        ? number_format((float)$event->value, 4).'%'
                                        : currency_symbol().number_format((float)$event->value, 4) }}
                                </td>
                                <td class="py-3">{{ $event->affected_holdings }}</td>
                                <td class="py-3 font-semibold">{{ currency_symbol() }}{{ number_format((float)$event->total_amount, 2) }}</td>
                                <td class="py-3 pr-5 text-muted-foreground">{{ $event->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-muted-foreground">
                                    No lifecycle distributions or deductions have been applied yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="border-t border-border px-5 py-4">
                    {{ $events->links() }}
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>

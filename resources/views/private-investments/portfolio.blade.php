<x-user-layout>
<x-slot name="header">My Investment Portfolio</x-slot>
<div class="mx-auto max-w-[1100px] px-3 py-5 sm:px-5">
    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Private investment portfolio</p>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">My Portfolio</h1>
        <p class="mt-2 text-sm text-zinc-500">
            Holdings here belong only to the private Investment Engine. Stock trading positions remain under Trading.
        </p>

        @if(auth()->user()->isAdmin())
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
                Admin audit mode: customer financial mutations are disabled. Use the Admin control plane to inspect or edit customer records.
            </div>
        @elseif($holdings->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800">
                No private investment holdings yet. Holdings will appear after the subscription engine is enabled.
            </div>
        @else
            <div class="mt-5 grid gap-3">
                @foreach($holdings as $holding)
                    <a href="{{ route('investments.show',$holding->instrument->slug) }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="font-semibold">{{ $holding->instrument->name }}</p><p class="text-xs text-zinc-500">{{ number_format((float)$holding->units,4) }} units</p></div>
                            <div class="text-right"><p class="font-semibold">{{ currency_symbol() }}{{ number_format((float)$holding->current_value,2) }}</p><p class="text-xs {{ (float)$holding->unrealized_profit_loss >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ (float)$holding->unrealized_profit_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format((float)$holding->unrealized_profit_loss,2) }}</p></div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
</x-user-layout>

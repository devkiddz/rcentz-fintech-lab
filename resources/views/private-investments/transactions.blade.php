<x-user-layout>
<x-slot name="header">Investment Transactions</x-slot>
<div class="mx-auto max-w-[1100px] px-3 py-5 sm:px-5">
<section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
<div class="flex items-end justify-between gap-4">
<div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Investment account</p><h1 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">Transactions</h1><p class="mt-2 text-sm text-zinc-500">Private Investment Engine subscription, redemption and distribution history.</p></div>
<a href="{{ route('account.investments') }}" class="text-xs font-semibold text-red-600">Account Overview</a>
</div>
@if(auth()->user()->isAdmin())
<div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Admin audit mode: customer-specific transactions are available through the Admin control plane or impersonation.</div>
@elseif($transactions->isEmpty())
<div class="mt-5 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800">No private investment transactions yet.</div>
@else
<div class="mt-5 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-xs"><thead class="border-b border-zinc-200 text-[9px] uppercase tracking-[.12em] text-zinc-400 dark:border-zinc-800"><tr><th class="py-3">Instrument</th><th>Type</th><th>Units</th><th>Price</th><th>Net Amount</th><th>Date</th></tr></thead><tbody class="divide-y divide-zinc-100 dark:divide-zinc-900">
@foreach($transactions as $transaction)<tr><td class="py-3 font-semibold">{{ $transaction->instrument?->symbol }} · {{ $transaction->instrument?->name }}</td><td>{{ ucwords(str_replace('_',' ',$transaction->type)) }}</td><td>{{ number_format((float)$transaction->units,4) }}</td><td>{{ currency_symbol() }}{{ number_format((float)$transaction->price_per_unit,2) }}</td><td class="font-semibold">{{ currency_symbol() }}{{ number_format((float)$transaction->net_amount,2) }}</td><td class="text-zinc-500">{{ optional($transaction->executed_at)->format('M j, Y H:i') }}</td></tr>@endforeach
</tbody></table></div><div class="mt-5">{{ $transactions->links() }}</div>
@endif
</section></div>
</x-user-layout>
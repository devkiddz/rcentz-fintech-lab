<x-user-layout>
<x-slot name="header">Investment Watchlist</x-slot>
<div class="mx-auto max-w-[1200px] space-y-5 px-3 py-5 sm:px-5 lg:px-6">
<section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"><div><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Private investment watchlist</p><h1 class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">Watch investments before you commit.</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500">Track our authoritative unit price, set your own target, add a note and prioritize what deserves another look.</p></div><a href="{{ route('investments.index') }}" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Browse Investments</a></div>
</section>
@if(auth()->user()->isAdmin())
<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Admin audit mode: personal watchlists belong to customer identities. Impersonate a customer to audit their exact watchlist experience.</div>
@elseif($items->isEmpty())
<div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-800 dark:bg-zinc-950"><p class="text-sm font-semibold text-zinc-900 dark:text-white">Your watchlist is empty.</p><p class="mt-2 text-xs text-zinc-500">Open any investment and add it with an optional target price and note.</p></div>
@else
<section class="grid gap-4 lg:grid-cols-2">
@foreach($items as $item)
@php
$instrument=$item->instrument;$current=(float)$instrument->current_price;$target=$item->target_price?(float)$item->target_price:null;$distance=$target&&$current>0?(($target-$current)/$current)*100:null;$move=(float)$instrument->change_percent;
@endphp
<article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
<div class="flex items-start justify-between gap-4"><div><div class="flex items-center gap-2"><span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-semibold text-red-600 dark:bg-red-950/30">{{ $instrument->symbol }}</span><span class="text-[9px] uppercase tracking-[.1em] text-zinc-400">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</span></div><a href="{{ route('investments.show',$instrument->slug) }}" class="mt-3 block text-sm font-semibold text-zinc-950 hover:text-red-600 dark:text-white">{{ $instrument->name }}</a></div><div class="text-right"><p class="text-lg font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($current,2) }}</p><p class="text-[10px] font-semibold {{ $move>=0?'text-emerald-600':'text-red-600' }}">{{ $move>=0?'+':'' }}{{ number_format($move,2) }}%</p></div></div>
<div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900"><div><p class="text-[9px] uppercase text-zinc-400">Target</p><p class="mt-1 text-xs font-semibold">{{ $target?currency_symbol().number_format($target,2):'Not set' }}</p></div><div><p class="text-[9px] uppercase text-zinc-400">Distance</p><p class="mt-1 text-xs font-semibold">{{ $distance!==null?(($distance>=0?'+':'').number_format($distance,2).'%'):'—' }}</p></div></div>
<form method="POST" action="{{ route('account.investments.watchlist.update',$instrument) }}" class="mt-4 grid gap-2 sm:grid-cols-2">@csrf @method('PATCH')
<div><label class="mb-1 block text-[9px] uppercase tracking-[.1em] text-zinc-400">Target Price</label><input class="ui-input w-full" type="number" step="0.000001" min="0.000001" name="target_price" value="{{ $item->target_price }}"></div>
<div><label class="mb-1 block text-[9px] uppercase tracking-[.1em] text-zinc-400">Priority</label><select class="ui-input w-full" name="priority">@foreach(['low','normal','high'] as $priority)<option value="{{ $priority }}" @selected($item->priority===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
<div class="sm:col-span-2"><label class="mb-1 block text-[9px] uppercase tracking-[.1em] text-zinc-400">Note</label><textarea class="ui-input w-full" rows="2" name="note">{{ $item->note }}</textarea></div>
<button class="rounded-xl border border-zinc-200 px-3 py-2 text-xs font-semibold hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">Update Watch</button>
</form>
<form method="POST" action="{{ route('account.investments.watchlist.destroy',$instrument) }}" class="mt-2">@csrf @method('DELETE')<button class="text-[10px] font-semibold text-red-600">Remove from watchlist</button></form>
</article>
@endforeach
</section>
@endif
</div>
</x-user-layout>
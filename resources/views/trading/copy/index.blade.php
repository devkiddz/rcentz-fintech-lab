<x-user-layout>
<x-slot name="header">Copy Trading</x-slot>
<div class="ui-page max-w-[1440px]">
 <section class="ui-page-header"><div><p class="ui-kicker">Trading Intelligence</p><h1 class="ui-heading">Copy trading</h1><p class="ui-lead">Follow a strategy provider with your own allocation, per-trade cap and copy ratio. Your wallet limits still apply to every mirrored order.</p></div><div class="ui-header-actions"><a href="{{ route('trading.copy.executions') }}" class="ui-btn ui-btn-secondary"><i data-lucide="history" class="h-4 w-4"></i>Execution History</a></div></section>
 <div class="grid gap-5 xl:grid-cols-[1.5fr_.8fr]">
  <section class="space-y-4">
   <div class="flex items-center justify-between"><div><h2 class="text-lg font-semibold">Strategy providers</h2><p class="text-sm text-muted-foreground">Public providers currently accepting copiers.</p></div></div>
   <div class="grid gap-4 lg:grid-cols-2">
    @forelse($providers as $provider)
    <article class="ui-panel p-5"><div class="flex items-start justify-between gap-4"><div><p class="text-xs uppercase tracking-wide text-muted-foreground">{{ ucfirst($provider->risk_level) }} risk</p><h3 class="mt-1 text-base font-semibold">{{ $provider->strategy_name }}</h3><p class="mt-1 text-xs text-muted-foreground">{{ $provider->user->name }} · {{ $provider->copier_count }} copier{{ $provider->copier_count===1?'':'s' }}</p></div><span class="ui-status {{ $provider->return_percent >= 0 ? 'ui-status-success' : 'ui-status-danger' }}">{{ $provider->return_percent >= 0 ? '+' : '' }}{{ number_format($provider->return_percent,2) }}%</span></div>
     @if($provider->bio)<p class="mt-4 text-sm leading-6 text-muted-foreground">{{ $provider->bio }}</p>@endif
     <form method="POST" action="{{ route('trading.copy.follow',$provider) }}" class="mt-5 grid gap-3 sm:grid-cols-3">@csrf
      <div><label class="ui-label">Allocation</label><input name="allocation_limit" type="number" min="100" step="0.01" value="1000" class="ui-input" required></div>
      <div><label class="ui-label">Max / trade</label><input name="max_trade_amount" type="number" min="10" step="0.01" value="200" class="ui-input" required></div>
      <div><label class="ui-label">Copy ratio %</label><input name="copy_ratio_percent" type="number" min="1" max="200" step="1" value="100" class="ui-input" required></div>
      <button class="ui-btn ui-btn-primary sm:col-span-3" type="submit">Start copying</button>
     </form>
    </article>
    @empty<div class="ui-panel p-8 text-center lg:col-span-2"><i data-lucide="users-round" class="mx-auto h-7 w-7 text-muted-foreground"></i><h3 class="mt-3 font-medium">No public providers yet</h3><p class="mt-1 text-sm text-muted-foreground">Create a provider profile from the panel on the right, or impersonate another verified customer to create the first strategy.</p></div>@endforelse
   </div>
   <div class="ui-panel overflow-hidden"><div class="border-b border-border px-5 py-4"><h2 class="font-semibold">My copied strategies</h2></div><div class="divide-y divide-border">
    @forelse($relationships as $relationship)<div class="p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-medium">{{ $relationship->provider->copyTraderProfile->strategy_name ?? $relationship->provider->name }}</p><p class="mt-1 text-xs text-muted-foreground">Used {{ format_currency($relationship->used_amount) }} of {{ format_currency($relationship->allocation_limit) }} · max {{ format_currency($relationship->max_trade_amount) }} / trade · {{ number_format($relationship->copy_ratio_percent,0) }}%</p></div><div class="flex items-center gap-2"><span class="ui-status {{ $relationship->status==='active'?'ui-status-success':($relationship->status==='paused'?'ui-status-warning':'') }}">{{ ucfirst($relationship->status) }}</span>@if($relationship->status!=='stopped')<form method="POST" action="{{ route('trading.copy.relationships.status',$relationship) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $relationship->status==='active'?'paused':'active' }}"><button class="ui-btn ui-btn-secondary" type="submit">{{ $relationship->status==='active'?'Pause':'Resume' }}</button></form><form method="POST" action="{{ route('trading.copy.relationships.status',$relationship) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="stopped"><button class="ui-btn ui-btn-secondary" type="submit">Stop</button></form>@endif</div></div></div>
    @empty<div class="p-6 text-sm text-muted-foreground">You are not copying a strategy yet.</div>@endforelse
   </div>
  </section>
  <aside class="ui-panel p-5 h-fit"><p class="ui-kicker">Provider profile</p><h2 class="mt-1 text-lg font-semibold">{{ $profile ? 'Manage your strategy' : 'Become a strategy provider' }}</h2><p class="mt-1 text-sm text-muted-foreground">Your existing stock trades become the source activity followers can mirror. No return is guaranteed.</p>
   <form method="POST" action="{{ route('trading.copy.profile') }}" class="mt-5 space-y-4">@csrf
    <div><label class="ui-label">Strategy name</label><input name="strategy_name" class="ui-input" maxlength="100" value="{{ old('strategy_name',$profile?->strategy_name) }}" placeholder="Balanced Growth" required></div>
    <div><label class="ui-label">Description</label><textarea name="bio" class="ui-input min-h-28" maxlength="800" placeholder="Describe your approach and risk discipline.">{{ old('bio',$profile?->bio) }}</textarea></div>
    <div><label class="ui-label">Risk level</label><select name="risk_level" class="ui-input">@foreach(['low','medium','high'] as $risk)<option value="{{ $risk }}" @selected(old('risk_level',$profile?->risk_level??'medium')===$risk)>{{ ucfirst($risk) }}</option>@endforeach</select></div>
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="is_public" value="1" class="rounded border-border" @checked(old('is_public',$profile?->is_public??true))>Visible to other customers</label>
    <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="is_accepting_copiers" value="1" class="rounded border-border" @checked(old('is_accepting_copiers',$profile?->is_accepting_copiers??true))>Accept new copiers</label>
    <button class="ui-btn ui-btn-primary w-full" type="submit">Save provider profile</button>
   </form>
  </aside>
 </div>
</div>
</x-user-layout>

<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="ui-kicker">Withdrawal gate</p>
            <h1 class="text-xl font-semibold">Token Requests</h1>
            <p class="mt-1 text-xs text-muted-foreground">A token request is permission to continue — it is not a withdrawal transaction.</p>
        </div>
    </x-slot>

    <div class="ui-page max-w-7xl">
        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700">{{ session('success') }}</div>@endif
        <section class="ui-surface overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-left text-xs">
                    <thead class="border-b border-border bg-muted/20 text-[9px] uppercase tracking-[.1em] text-muted-foreground"><tr><th class="px-4 py-3">Customer</th><th>Amount</th><th>Note</th><th>Status</th><th>Token</th><th>Created</th><th class="pr-4">Actions</th></tr></thead>
                    <tbody class="divide-y divide-border">
                    @forelse($requests as $requestItem)
                        <tr>
                            <td class="px-4 py-3"><p class="font-semibold">{{ $requestItem->user->name }}</p><p class="text-[10px] text-muted-foreground">{{ $requestItem->user->email }}</p></td>
                            <td class="font-semibold">{{ format_currency($requestItem->amount,'USD',$requestItem->user->currency,$requestItem->user) }}</td>
                            <td class="max-w-[260px] py-3 text-muted-foreground">{{ $requestItem->note ?: 'No note supplied' }}</td>
                            <td><span class="rounded-full bg-muted px-2 py-1 text-[9px] font-semibold uppercase">{{ str_replace('_',' ',$requestItem->status) }}</span></td>
                            <td>{{ $requestItem->token_last_four ? '••••'.$requestItem->token_last_four : '—' }} @if($requestItem->token_expires_at)<p class="text-[9px] text-muted-foreground">expires {{ $requestItem->token_expires_at->format('H:i') }}</p>@endif</td>
                            <td>{{ $requestItem->created_at->format('M j, H:i') }}</td>
                            <td class="pr-4">
                                <div class="flex flex-wrap gap-2">
                                    @if(in_array($requestItem->status,['pending','token_issued']))
                                        <form method="POST" action="{{ route('admin.withdrawal-token-requests.generate',$requestItem) }}">@csrf<button class="ui-btn ui-btn-primary ui-btn-sm">{{ $requestItem->status === 'token_issued' ? 'Regenerate Token' : 'Generate Token' }}</button></form>
                                        <form method="POST" action="{{ route('admin.withdrawal-token-requests.destroy-token',$requestItem) }}">@csrf @method('DELETE')<button class="ui-btn ui-btn-secondary ui-btn-sm" onclick="return confirm('Destroy this withdrawal token request?')">Destroy Token</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No withdrawal token requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-border p-4">{{ $requests->links() }}</div>
        </section>
    </div>
</x-admin-layout>

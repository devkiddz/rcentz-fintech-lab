<x-user-layout>
    <x-slot name="header">
        Stock Watchlist
    </x-slot>

    <div class="ui-page max-w-7xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Trading · Watchlist</p>
                <h1 class="ui-heading">Market watchlist</h1>
                <p class="ui-lead">Keep the stocks you care about close, monitor price moves, and manage your alerts from one place.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('trading.positions.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="briefcase-business" class="h-4 w-4"></i>
                    Positions
                </a>
                <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Browse stocks
                </a>
            </div>
        </section>

        <!-- Watchlist Table -->
        <div class="ui-table-shell overflow-hidden">
            <div class="p-6 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground">Your Watchlist</h2>
                <p class="text-sm text-muted-foreground mt-1">Monitor stocks and get price alerts</p>
            </div>
            
            @if($watchlist->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Current Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Change</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Alert Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Alert Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-card divide-y divide-border">
                            @foreach($watchlist as $item)
                                <tr class="hover:bg-muted/30">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($item->stock->logo_url)
                                                <img src="{{ $item->stock->logo_url }}" alt="{{ $item->stock->symbol }}" class="w-8 h-8 rounded mr-3">
                                            @else
                                                <div class="w-8 h-8 bg-muted rounded mr-3 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-muted-foreground">{{ substr($item->stock->symbol, 0, 2) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-foreground">{{ $item->stock->symbol }}</div>
                                                <div class="text-sm text-muted-foreground">{{ $item->stock->company_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-foreground">{{ currency_symbol() }}{{ number_format($item->stock->current_price, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm {{ $item->stock->change_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $item->stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($item->stock->change_percentage, 2) }}%
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ $item->stock->change_percentage >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($item->stock->change_amount, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                        {{ $item->alert_price ? '$' . number_format($item->alert_price, 2) : 'Not set' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->alert_type)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->alert_type === 'above' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($item->alert_type) }}
                                            </span>
                                        @else
                                            <span class="text-sm text-muted-foreground">Not set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->isAlertTriggered())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i data-lucide="bell" class="w-3 h-3 mr-1"></i>
                                                Alert Triggered
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-foreground">
                                                Monitoring
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('trading.buy', $item->stock) }}" 
                                               class="ui-icon-btn text-emerald-600"
                                               title="Buy Stock">
                                                <i data-lucide="plus" class="w-4 h-4"></i>
                                            </a>
                                            <button onclick="editAlert('{{ $item->stock->symbol }}', '{{ $item->alert_price }}', '{{ $item->alert_type }}')"
                                                    class="ui-icon-btn"
                                                    title="Edit Alert">
                                                <i data-lucide="bell" class="w-4 h-4"></i>
                                            </button>
                                            <form action="{{ route('trading.watchlist.remove', $item->stock) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="ui-icon-btn text-red-600"
                                                        onclick="return confirm('Remove {{ $item->stock->symbol }} from watchlist?')"
                                                        title="Remove from Watchlist">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="eye" class="w-8 h-8 text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No Stocks in Watchlist</h3>
                    <p class="text-muted-foreground mb-6">Start building your watchlist by adding stocks you're interested in.</p>
                    <a href="{{ route('stocks.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-foreground text-background rounded-lg hover:opacity-90 transition-colors duration-200">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Browse Stocks
                    </a>
                </div>
            @endif
        </div>

        <!-- Price Alert Modal -->
        <div id="alertModal" class="fixed inset-0 hidden h-full w-full overflow-y-auto bg-black/60 backdrop-blur-sm z-50">
            <div class="relative top-20 mx-auto w-96 max-w-[calc(100vw-2rem)] ui-panel p-5 shadow-2xl">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-foreground mb-4">Set Price Alert</h3>
                    <form id="alertForm" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="alert_price" class="block text-sm font-medium text-foreground mb-2">Alert Price</label>
                            <input type="number" 
                                   id="alert_price" 
                                   name="alert_price" 
                                   step="0.01" 
                                   min="0.01"
                                   class="ui-input"
                                   placeholder="0.00">
                        </div>
                        <div class="mb-6">
                            <label for="alert_type" class="block text-sm font-medium text-foreground mb-2">Alert Type</label>
                            <select id="alert_type" 
                                    name="alert_type" 
                                    class="ui-input">
                                <option value="">Select alert type</option>
                                <option value="above">Above this price</option>
                                <option value="below">Below this price</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" 
                                    onclick="closeAlertModal()"
                                    class="ui-btn ui-btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="ui-btn ui-btn-primary">
                                Set Alert
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editAlert(symbol, currentPrice, currentType) {
            const modal = document.getElementById('alertModal');
            const form = document.getElementById('alertForm');
            const priceInput = document.getElementById('alert_price');
            const typeSelect = document.getElementById('alert_type');
            
            // Set current values
            priceInput.value = currentPrice || '';
            typeSelect.value = currentType || '';
            
            // Set form action
            form.action = `/trading/watchlist/${symbol}`;
            
            // Show modal
            modal.classList.remove('hidden');
        }
        
        function closeAlertModal() {
            const modal = document.getElementById('alertModal');
            modal.classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('alertModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAlertModal();
            }
        });
    </script>
</x-user-layout>

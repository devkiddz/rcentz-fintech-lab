<?php

namespace App\Events;

use App\Models\Stock;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stock;
    public $priceData;

    /**
     * Create a new event instance.
     */
    public function __construct(Stock $stock, array $priceData)
    {
        $this->stock = $stock;
        $this->priceData = $priceData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('stock.' . $this->stock->symbol),
            new Channel('market.overview'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->stock->symbol,
            'name' => $this->stock->name,
            'current_price' => $this->priceData['current_price'] ?? $this->stock->current_price,
            'previous_close' => $this->priceData['previous_close'] ?? $this->stock->previous_close,
            'change' => $this->priceData['change'] ?? $this->stock->change,
            'change_percent' => $this->priceData['change_percent'] ?? $this->stock->change_percent,
            'volume' => $this->priceData['volume'] ?? $this->stock->volume,
            'high' => $this->priceData['high'] ?? $this->stock->high,
            'low' => $this->priceData['low'] ?? $this->stock->low,
            'open' => $this->priceData['open'] ?? $this->stock->open,
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'stock.price.updated';
    }
}

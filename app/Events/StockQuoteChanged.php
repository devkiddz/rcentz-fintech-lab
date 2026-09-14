<?php

namespace App\Events;

use App\Models\StockQuote;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockQuoteChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stockQuote;
    public $stock;

    /**
     * Create a new event instance.
     */
    public function __construct(StockQuote $stockQuote)
    {
        $this->stockQuote = $stockQuote;
        $this->stock = $stockQuote->stock;
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
            new Channel('stock.quotes'),
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
            'current_price' => $this->stockQuote->current_price,
            'previous_close' => $this->stockQuote->previous_close,
            'change' => $this->stockQuote->change,
            'change_percent' => $this->stockQuote->change_percent,
            'volume' => $this->stockQuote->volume,
            'high' => $this->stockQuote->high,
            'low' => $this->stockQuote->low,
            'open' => $this->stockQuote->open,
            'recommendations' => $this->stockQuote->recommendations,
            'updated_at' => $this->stockQuote->updated_at->toISOString(),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'stock.quote.changed';
    }
}

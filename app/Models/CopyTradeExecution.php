<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyTradeExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'copy_relationship_id',
        'market_instrument_id',
        'provider_stock_transaction_id',
        'provider_market_execution_transaction_id',
        'provider_broker_order_id',
        'follower_stock_transaction_id',
        'follower_market_execution_transaction_id',
        'follower_broker_order_id',
        'action',
        'requested_amount',
        'executed_amount',
        'status',
        'failure_reason',
        'executed_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'executed_amount' => 'decimal:2',
        'executed_at' => 'datetime',
    ];

    public function relationship(){ return $this->belongsTo(CopyRelationship::class, 'copy_relationship_id'); }
    public function marketInstrument(){ return $this->belongsTo(MarketInstrument::class); }
    public function providerTrade(){ return $this->belongsTo(StockTransaction::class, 'provider_stock_transaction_id'); }
    public function followerTrade(){ return $this->belongsTo(StockTransaction::class, 'follower_stock_transaction_id'); }
    public function providerMarketExecution(){ return $this->belongsTo(MarketExecutionTransaction::class, 'provider_market_execution_transaction_id'); }
    public function followerMarketExecution(){ return $this->belongsTo(MarketExecutionTransaction::class, 'follower_market_execution_transaction_id'); }
    public function providerBrokerOrder(){ return $this->belongsTo(BrokerOrder::class, 'provider_broker_order_id'); }
    public function followerBrokerOrder(){ return $this->belongsTo(BrokerOrder::class, 'follower_broker_order_id'); }

    public function getResolvedInstrumentAttribute(): ?MarketInstrument
    {
        return $this->marketInstrument
            ?? $this->followerMarketExecution?->marketInstrument
            ?? $this->providerMarketExecution?->marketInstrument
            ?? $this->followerTrade?->marketInstrument
            ?? $this->providerTrade?->marketInstrument
            ?? $this->followerTrade?->stock?->marketInstrument
            ?? $this->providerTrade?->stock?->marketInstrument;
    }

    public function getDisplaySymbolAttribute(): string
    {
        $instrument = $this->resolved_instrument;
        return (string) ($instrument?->display_symbol ?: $instrument?->symbol ?: '—');
    }

    public function getAssetClassAttribute(): string
    {
        return strtoupper((string) ($this->resolved_instrument?->asset_class ?: 'asset'));
    }
}

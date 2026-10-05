<?php
namespace App\Services;
use App\Models\ControlledMarketInstrument;
use App\Models\PrivateMarketReference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
final class InvestmentBaseBasketDriver {
    public function bound(int $id): bool {
        return Schema::hasTable('investment_base_basket_drivers') && DB::table('investment_base_basket_drivers')->where('reference_id',$id)->exists();
    }
    public function price(PrivateMarketReference $reference): float {
        $binding=DB::table('investment_base_basket_drivers')->where('reference_id',$reference->id)->first();
        if(!$binding || !$binding->enabled || $reference->status!=='active') throw new RuntimeException('Investment base driver is disabled.');
        $quote=ControlledMarketInstrument::with('marketInstrument')->where('market_instrument_id',$binding->market_instrument_id)->where('is_active',true)->first();
        if(!$quote || !$quote->marketInstrument?->is_active || !$quote->last_moved_at || $quote->last_moved_at->lt(now()->subMinutes(5)) || $quote->last_moved_at->gt(now()->addSeconds(30))) throw new RuntimeException('Investment base basket driver is stale or unavailable.');
        foreach([$binding->base_anchor,$binding->basket_anchor,$quote->current_price] as $p) if(!is_numeric($p)||!is_finite((float)$p)||(float)$p<=0) throw new RuntimeException('Invalid investment base anchor.');
        // Preserve the private asset's denomination. Only the basket's relative change is mirrored.
        $price=(float)$binding->base_anchor*(float)$quote->current_price/(float)$binding->basket_anchor;
        if(!is_finite($price)||$price<=0) throw new RuntimeException('Invalid derived investment base price.');
        return round($price,8);
    }
}

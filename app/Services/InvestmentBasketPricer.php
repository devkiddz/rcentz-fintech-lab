<?php
namespace App\Services;

use App\Models\ControlledMarketInstrument;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class InvestmentBasketPricer
{
    public function bound(int $id): bool
    {
        return Schema::hasTable('investment_basket_bindings')
            && DB::table('investment_basket_bindings')->where('instrument_id', $id)->exists();
    }

    public function sync(PrivateInvestmentInstrument $investment): PrivateInvestmentInstrument
    {
        return DB::transaction(function () use ($investment) {
            // Same first lock as subscription/redemption: prices and share ownership serialize.
            $investment = PrivateInvestmentInstrument::query()->lockForUpdate()->findOrFail($investment->id);
            $binding = DB::table('investment_basket_bindings')->where('instrument_id', $investment->id)->lockForUpdate()->first();
            if (!$binding || !$binding->enabled || $binding->marketplace !== 'controlled') {
                throw new RuntimeException('Investment basket pricing is unavailable.');
            }
            if ($investment->currency !== 'USD') throw new RuntimeException('This binding requires a USD-denominated investment.');
            $assets = $investment->assets()->with(['marketInstrument','publicBaseAsset','privateMarketReference'])->where('status', 'active')->where('is_reserve_backing', true)->orderBy('id')->lockForUpdate()->get();
            if ($assets->isEmpty()) throw new RuntimeException('Basket investment needs reserve assets.');
            $snapshot = [];
            // Validate every asset before writing any valuation; never silently use another source.
            foreach ($assets as $asset) {
                if ($asset->isPrivateLinked()) {
                    $reference=$asset->privateMarketReference;
                    if(!$reference || $reference->status!=='active' || $reference->currency!==$investment->currency) throw new RuntimeException('Private investment base is unavailable or has a different currency.');
                    $quantity=(float)$asset->reserve_quantity;
                    $price=(float)$reference->current_price;
                    $at=$reference->last_valued_at;
                    if(!is_finite($quantity)||$quantity<=0||!is_finite($price)||$price<=0||!$at||$at->lt(now()->subMinutes(5))||$at->gt(now()->addSeconds(30))) throw new RuntimeException('Private investment base valuation is stale or invalid.');
                    if(app(InvestmentBaseBasketDriver::class)->bound($reference->id)) app(InvestmentBaseBasketDriver::class)->price($reference);
                    $snapshot[$asset->id]=['price'=>$price,'quantity'=>$quantity];
                    continue;
                }
                if($asset->public_investment_base_asset_id && (!$asset->publicBaseAsset || $asset->publicBaseAsset->status!=='active' || $asset->publicBaseAsset->market_instrument_id!=$asset->market_instrument_id)) throw new RuntimeException('Approved public investment base is inactive or mismatched.');
                $market = $asset->marketInstrument;
                if (!$asset->isMarketLinked() || !$market || !$market->is_active || strtoupper((string)$market->quote_asset) !== 'USD') {
                    throw new RuntimeException('Every basket reserve must have an active USD-quoted instrument.');
                }
                $quote = ControlledMarketInstrument::query()->where('market_instrument_id', $market->id)->where('is_active', true)->first();
                $price = (float)($quote?->current_price ?? 0);
                $quantity = (float)$asset->reserve_quantity;
                if (!is_finite($price) || $price <= 0 || !is_finite($quantity) || $quantity <= 0) throw new RuntimeException('Invalid basket reserve quote or quantity.');
                $at = $quote->last_moved_at;
                if (!$at || $at->lt(now()->subMinutes(5)) || $at->gt(now()->addSeconds(30))) throw new RuntimeException('Basket movement is stale. Restart the scheduler before investing.');
                $snapshot[$asset->id] = ['market_instrument_id'=>$market->id, 'price'=>$price, 'quantity'=>$quantity, 'observed_at'=>$at->toIso8601String()];
            }
            foreach ($assets as $asset) {
                $point = $snapshot[$asset->id];
                $asset->update(['current_unit_price'=>$point['price'], 'current_valuation'=>round($point['quantity']*$point['price'], 2), 'last_valued_at'=>now()]);
            }
            $investment = app(PrivateInvestmentReserveService::class)->revalueFromReserves($investment);
            $nav = (float)$investment->current_price;
            if (!is_finite($nav) || $nav <= 0) throw new RuntimeException('Invalid basket investment NAV.');
            $at = now()->startOfMinute();
            $at->minute = intdiv($at->minute, 5)*5;
            $bar = PrivateInvestmentPrice::query()->firstOrNew(['instrument_id'=>$investment->id, 'timeframe'=>'5m', 'recorded_at'=>$at]);
            $open = $bar->exists ? (float)$bar->open : $nav;
            $bar->fill(['open'=>$open,'high'=>max($open,(float)($bar->high ?? $nav),$nav),'low'=>min($open,(float)($bar->low ?? $nav),$nav),'close'=>$nav,'change_amount'=>$nav-$open,'change_percent'=>$open>0?($nav/$open-1)*100:0,'source'=>'basket_reserve_nav']);
            $bar->save();
            DB::table('investment_basket_bindings')->where('instrument_id', $investment->id)->update(['last_synced_at'=>now(),'last_status'=>'saved','updated_at'=>now()]);
            return $investment;
        });
    }

    public function run(): array
    {
        $result=['checked'=>0,'updated'=>0,'failed'=>0,'failed_instrument_ids'=>[]];
        if (!Schema::hasTable('investment_basket_bindings')) return $result;
        foreach (DB::table('investment_basket_bindings')->where('enabled', true)->orderBy('instrument_id')->pluck('instrument_id') as $id) {
            $result['checked']++;
            try { $this->sync(PrivateInvestmentInstrument::findOrFail($id)); $result['updated']++; }
            catch (\Throwable $e) {
                $result['failed']++; $result['failed_instrument_ids'][]=$id;
                \Log::warning('Investment basket valuation failed',['instrument_id'=>$id,'error'=>$e->getMessage()]);
                DB::table('investment_basket_bindings')->where('instrument_id',$id)->update(['last_status'=>'valuation_unavailable','updated_at'=>now()]);
            }
        }
        return $result;
    }
}

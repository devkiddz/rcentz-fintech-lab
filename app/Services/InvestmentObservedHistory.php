<?php
namespace App\Services;
use App\Models\PrivateInvestmentInstrument;
use App\Models\ControlledMarketInstrument;
use App\Models\ControlledMarketTick;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Support\Facades\DB;
use RuntimeException;
final class InvestmentObservedHistory {
    public function rebuild(int $id): array {
        return DB::transaction(function()use($id){
            $i=PrivateInvestmentInstrument::lockForUpdate()->findOrFail($id);
            if(!app(InvestmentBasketPricer::class)->bound($id))throw new RuntimeException('Explicit investment pricing binding required.');
            if($i->holdings()->exists() || DB::table('private_investment_transactions')->where('instrument_id',$id)->exists())throw new RuntimeException('Customer history exists. Backing-history reconstruction skipped.');
            $assets=$i->assets()->where('status','active')->where('is_reserve_backing',true)->get();
            if($assets->count()!==1 || $i->reserveEvents()->whereNotIn('action',['baseline_backfill','private_reference_revalued'])->exists())throw new RuntimeException('Only untouched single-base test reserves can reconstruct backing history.');
            $a=$assets->first();$supply=(float)$i->unit_supply;$quantity=(float)$a->reserve_quantity;
            if($supply<=0||$quantity<=0)throw new RuntimeException('Invalid reserve supply.');
            $since=$a->created_at->gt($i->created_at)?$a->created_at:$i->created_at;
            $observations=[['at'=>$since->copy(),'price'=>(float)$a->acquisition_unit_price]];
            if($a->isPrivateLinked()) {
                foreach($a->privateMarketReference->prices()->where('recorded_at','>=',$since)->orderBy('recorded_at')->get() as $row)$observations[]=['at'=>$row->recorded_at,'price'=>(float)$row->price];
            } elseif($a->isMarketLinked() && $a->public_investment_base_asset_id) {
                $quote=ControlledMarketInstrument::where('market_instrument_id',$a->market_instrument_id)->firstOrFail();
                foreach(ControlledMarketTick::where('controlled_market_instrument_id',$quote->id)->where('ticked_at','>=',$since)->orderBy('ticked_at')->get() as $row)$observations[]=['at'=>$row->ticked_at,'price'=>(float)$row->close];
            } else throw new RuntimeException('Approved asset base required.');
            $bars=[];
            foreach($observations as $p){
                if(!$p['at']||$p['at']->gt(now())||!is_finite($p['price'])||$p['price']<=0)continue;
                $at=$p['at']->copy()->startOfMinute();$at->minute=intdiv($at->minute,5)*5;$key=$at->format('Y-m-d H:i:s');
                $nav=round($quantity*$p['price']/$supply,6);
                if(!isset($bars[$key]))$bars[$key]=['open'=>$nav,'high'=>$nav,'low'=>$nav,'close'=>$nav];
                else{$bars[$key]['high']=max($bars[$key]['high'],$nav);$bars[$key]['low']=min($bars[$key]['low'],$nav);$bars[$key]['close']=$nav;}
            }
            $written=0;
            foreach($bars as $at=>$bar){
                // Preserve actual scheduled NAV bars. Fill only missing recorded-observation buckets.
                if(PrivateInvestmentPrice::where('instrument_id',$id)->where('timeframe','5m')->where('recorded_at',$at)->exists())continue;
                PrivateInvestmentPrice::create(['instrument_id'=>$id,'timeframe'=>'5m','recorded_at'=>$at]+$bar+['change_amount'=>$bar['close']-$bar['open'],'change_percent'=>($bar['close']/$bar['open']-1)*100,'source'=>'basket_reserve_nav']);$written++;
            }
            return ['instrument_id'=>$id,'observations'=>count($observations),'bars_added'=>$written,'starts_at'=>$since->toIso8601String(),'prices_wallets_holdings_unchanged'=>true];
        });
    }
}

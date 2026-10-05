<?php
namespace App\Services;
use App\Models\MarketInstrument;
use App\Models\ControlledMarketInstrument;
use App\Models\PublicInvestmentBaseAsset;
use App\Models\PrivateMarketReference;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentAsset;
use Illuminate\Support\Facades\DB;

final class InvestmentListingSetup {
 public function run(): array {
return DB::transaction(function(){
    $legacy=PrivateInvestmentInstrument::where('symbol','UKSG24')->lockForUpdate()->first();
    if($legacy && ($legacy->holdings()->exists() || DB::table('private_investment_transactions')->where('instrument_id',$legacy->id)->exists()))throw new RuntimeException('UKSG24 has customer history. Existing listing retained; inspect before conversion.');
    $quotes=[];$markets=[];
    foreach(['GBP','XAU'] as $base){
        $markets[$base]=MarketInstrument::where('base_asset',$base)->where('quote_asset','USD')->where('is_active',true)->firstOrFail();
        $existing=ControlledMarketInstrument::where('market_instrument_id',$markets[$base]->id)->first();
        if(!$existing && $base==='XAU') {
            $stored=(float)$markets[$base]->canonicalCommodityInstrument?->current_price;
            if(!is_finite($stored)||$stored<=0)throw new RuntimeException('A stored Gold spot price is required before creating its basket.');
            $existing=app(ControlledMarketEngine::class)->registerMarketInstrument($markets[$base],$markets[$base]->name,$stored);
        }
        if(!$existing || !$existing->is_active)throw new RuntimeException('Required basket feed is missing or inactive: '.$base);
        $quotes[$base]=$existing;
        $q=$quotes[$base];
        if(!is_numeric($q->current_price)||!is_finite((float)$q->current_price)||(float)$q->current_price<=0)throw new RuntimeException('A positive stored GBP and Gold basket price is required.');
    }
    $ref=PrivateMarketReference::where('symbol','UKPROPBASE24')->first();
    if($ref && !DB::table('investment_base_basket_drivers')->where('reference_id',$ref->id)->exists())throw new RuntimeException('UK property base symbol already belongs to another setup.');
    if(!$ref){
        $ref=PrivateMarketReference::create(['symbol'=>'UKPROPBASE24','name'=>'UK & European Property Asset Base','category'=>'real_estate','location'=>'United Kingdom / Europe','reference_unit'=>'property value unit','currency'=>'USD','description'=>'Internal property portfolio test base.','current_price'=>100,'previous_price'=>100,'status'=>'active','last_valued_at'=>now()]);
        $ref->forceFill(['movement_mode'=>'auto','movement_behavior'=>'smart','movement_tick_seconds'=>30,'movement_strength'=>1,'movement_volatility_percent'=>0.01,'movement_anchor_price'=>100,'movement_last_moved_at'=>now()])->save();
        DB::table('investment_base_basket_drivers')->insert(['reference_id'=>$ref->id,'market_instrument_id'=>$markets['GBP']->id,'base_anchor'=>100,'basket_anchor'=>$quotes['GBP']->current_price,'enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
    }
    $public=PublicInvestmentBaseAsset::firstOrCreate(['market_instrument_id'=>$markets['XAU']->id],['status'=>'active','metadata'=>['source'=>'investment_base_fix']]);
    if($public->status!=='active')throw new RuntimeException('Existing Gold public base is inactive. Its settings were preserved.');
    $results=[];
    foreach(['UKPROP24'=>['UK Property Portfolio','uk-property-portfolio-test','real_estate','private',$ref], 'GOLD24'=>['Gold Portfolio','gold-portfolio-test','commodities','market_linked',$public]] as $symbol=>[$name,$slug,$category,$mode,$base]){
        $i=PrivateInvestmentInstrument::where('symbol',$symbol)->first();
        if($i){
            if(!DB::table('investment_basket_bindings')->where('instrument_id',$i->id)->exists())throw new RuntimeException('Listing symbol already exists without this price binding: '.$symbol);
            $a=$i->assets()->where('is_reserve_backing',true)->first();
            if(!$a || ($mode==='private'?$a->private_market_reference_id!=$ref->id:$a->public_investment_base_asset_id!=$public->id))throw new RuntimeException('Existing listing authority differs: '.$symbol);
            $results[]=['symbol'=>$symbol,'id'=>$i->id,'already_created'=>true];continue;
        }
        $price=$mode==='private'?(float)$ref->current_price:(float)$quotes['XAU']->current_price;
        $i=PrivateInvestmentInstrument::create(['symbol'=>$symbol,'slug'=>$slug,'name'=>$name,'category'=>$category,'description'=>$mode==='private'?'UK-focused property portfolio.':'Gold-focused commodity portfolio.','currency'=>'USD','risk_level'=>'medium','status'=>'active','opening_price'=>10,'current_price'=>10,'previous_price'=>10,'unit_supply'=>10000,'available_units'=>10000,'minimum_investment'=>100,'maximum_investment'=>1000,'lock_period_days'=>1,'duration_days'=>30,'return_interval_days'=>1,'subscription_fee_percent'=>1,'redemption_fee_percent'=>0.5,'management_fee_percent'=>0,'projected_return_min_percent'=>0,'projected_return_max_percent'=>0,'is_visible'=>true,'is_featured'=>true,'last_valued_at'=>now()]);
        $a=PrivateInvestmentAsset::create(['instrument_id'=>$i->id,'market_instrument_id'=>$mode==='private'?null:$markets['XAU']->id,'public_investment_base_asset_id'=>$mode==='private'?null:$public->id,'private_market_reference_id'=>$mode==='private'?$ref->id:null,'valuation_mode'=>$mode,'is_reserve_backing'=>true,'reserve_quantity'=>round(100000/$price,8),'reserve_unit'=>$mode==='private'?'property value unit':'troy ounce','acquisition_unit_price'=>$price,'current_unit_price'=>$price,'name'=>$mode==='private'?$ref->name:'Gold Commodity Asset Base','asset_type'=>$mode==='private'?'real_estate':'commodity','acquisition_value'=>100000,'current_valuation'=>100000,'ownership_percentage'=>100,'status'=>'active','last_valued_at'=>now(),'effective_at'=>now(),'notes'=>'Demonstration reserve ledger. No real asset purchase; property test price follows relative Sterling basket movement, not a property appraisal.']);
        $i->update(['reference_asset_id'=>$a->id]);
        \App\Models\PrivateInvestmentReserveEvent::create(['instrument_id'=>$i->id,'asset_id'=>$a->id,'action'=>'baseline_backfill','valuation_mode'=>$mode,'previous_quantity'=>0,'new_quantity'=>$a->reserve_quantity,'previous_unit_price'=>0,'new_unit_price'=>$price,'previous_valuation'=>0,'new_valuation'=>100000,'reason'=>'Create base-linked demonstration listing.','metadata'=>['real_asset_purchase'=>false],'effective_at'=>now()]);
        DB::table('investment_basket_bindings')->insert(['instrument_id'=>$i->id,'marketplace'=>'controlled','enabled'=>true,'last_status'=>'waiting','created_at'=>now(),'updated_at'=>now()]);
        $results[]=['symbol'=>$symbol,'id'=>$i->id,'category'=>$category,'base_id'=>$base->id,'base_type'=>$mode,'created'=>true];
    }
    if($legacy){$legacy->update(['status'=>'paused','is_visible'=>false]);DB::table('investment_basket_bindings')->where('instrument_id',$legacy->id)->update(['enabled'=>false,'updated_at'=>now()]);}
    return ['listings'=>$results,'retired_empty_listing'=>$legacy?->id,'wallet_unchanged'=>true,'lock_hours'=>24,'provider_calls'=>0];
});

 }
}

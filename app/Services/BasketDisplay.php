<?php
namespace App\Services;
final class BasketDisplay {
    public static function recordSymbol(?object $i,?string $marketplace,mixed $createdAt): string {
        if(!$createdAt || !\Illuminate\Support\Facades\Schema::hasTable('basket_presentation')) return self::symbol($i,'live');
        $cutoff=\Illuminate\Support\Facades\DB::table('basket_presentation')->where('id',1)->value('activated_at');
        $new=$cutoff && \Carbon\CarbonImmutable::parse($createdAt,'UTC')->gte(\Carbon\CarbonImmutable::parse($cutoff,'UTC'));
        return self::symbol($i,$new?$marketplace:'live');
    }
    public static function recordName(?object $i,?string $marketplace,mixed $createdAt): string {
        $symbol=self::recordSymbol($i,$marketplace,$createdAt);
        return self::name($i,$i && $symbol===self::symbol($i,'controlled') ? $marketplace : 'live');
    }
    public static function symbol(?object $instrument,?string $marketplace): string {
        if(!$instrument) return 'Instrument';
        if(!config('basket_engine.enabled',false) || $marketplace!=='controlled') return (string)$instrument->display_symbol;
        return strtoupper($instrument->base_asset ? (string)$instrument->base_asset.($instrument->quote_asset??'') : (string)$instrument->symbol).'-B';
    }
    public static function name(?object $instrument,?string $marketplace): string {
        if(!$instrument) return 'Instrument';
        if(!config('basket_engine.enabled',false) || $marketplace!=='controlled') return (string)$instrument->name;
        $names=['EUR'=>'Euro','USD'=>'Dollar','GBP'=>'Sterling','JPY'=>'Yen','AUD'=>'Australian Dollar','CAD'=>'Canadian Dollar','CHF'=>'Swiss Franc','NZD'=>'New Zealand Dollar','XAU'=>'Gold','XAG'=>'Silver','BTC'=>'Bitcoin','ETH'=>'Ethereum'];
        $base=strtoupper((string)$instrument->base_asset);
        if($base!=='') return ($names[$base]??$base).' Basket'.($instrument->asset_class==='forex' && $instrument->quote_asset!=='USD'?' / '.($names[$instrument->quote_asset]??$instrument->quote_asset):'');
        return (string)$instrument->name.' Index';
    }
}

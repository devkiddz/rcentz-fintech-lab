<?php
namespace App\Services;
use InvalidArgumentException;
final class BasketMovement {
    public static function score(array $prices): array {
        if(count($prices)<2) return ['momentum'=>0.0,'volatility'=>0.0,'samples'=>count($prices)];
        foreach($prices as $p) if(!is_numeric($p)||!is_finite((float)$p)||(float)$p<=0) throw new InvalidArgumentException('Invalid reference observation.');
        $returns=[];for($n=1;$n<count($prices);$n++) $returns[]=log($prices[$n]/$prices[$n-1])*100;
        $mean=array_sum($returns)/count($returns);$variance=0;
        foreach($returns as $r) $variance+=($r-$mean)**2;
        // Per-observation RMS includes directional movement, not just variance.
        $rms=sqrt($variance/count($returns)+$mean*$mean);
        return ['momentum'=>log(end($prices)/$prices[0])*100,'volatility'=>$rms,'samples'=>count($prices)];
    }
    public static function move(float $neutral,float $open,?array $reference,?float $usd,string $quote,int $now,array $config,string $base=''): float {
        if(!is_finite($neutral)||!is_finite($open)||$open<=0) throw new InvalidArgumentException('Invalid movement input.');
        $limit=max(0.0001,min(0.5,(float)$config['maximum_step_percent']));
        $usdSign=strtoupper($base)==='USD'?1:(strtoupper($quote)==='USD'?-1:0);
        $usdInfluence=$usd!==null && is_finite($usd) ? $usdSign*max(-1,min(1,$usd))*(float)$config['usd_weight'] : 0;
        $usdDrift=(float)$config['volatility_floor_percent']*$usdInfluence*0.4;
        if(!$reference) return max(-$limit,min($limit,$neutral+$usdDrift));
        foreach(['price','momentum_percent','volatility_percent','observed_timestamp'] as $field) if(!isset($reference[$field])||!is_numeric($reference[$field])||!is_finite((float)$reference[$field])) throw new InvalidArgumentException('Invalid market reference.');
        if($reference['price']<=0) throw new InvalidArgumentException('Invalid anchor.');
        $age=$now-(int)$reference['observed_timestamp'];
        $ttl=max(1,(int)$config['max_reference_age_seconds']);
        $fresh=$age < -30 ? 0.0 : max(0.0,1-max(0,$age)/$ttl);
        $momentum=max(-1,min(1,$reference['momentum_percent']/max(0.001,(float)$config['momentum_scale_percent'])));
        $trend=max(-1,min(1,$momentum+$usdInfluence));
        $anchor=max(-1,min(1,log($reference['price']/$open)*100/max(0.001,(float)$config['momentum_scale_percent'])));
        $vol=max((float)$config['volatility_floor_percent'],min((float)$config['volatility_ceiling_percent'],$reference['volatility_percent']));
        $drift=$vol*($trend*0.4+$anchor*(float)$config['anchor_weight']);
        $guidedNoise=max(-$vol*1.5,min($vol*1.5,$neutral));
        return max(-$limit,min($limit,$neutral*(1-$fresh)+$guidedNoise*0.6*$fresh+$drift*$fresh+$usdDrift*(1-$fresh)));
    }
}

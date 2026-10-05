<?php
namespace App\Services;
use InvalidArgumentException;
final class NeutralMarketStep
{
    /** Symmetric trend persistence and clustered volatility; no target price or admin bias. */
    public static function move(float $volatility, float $previousReturn, float $directionRoll, float $sizeRoll): float
    {
        foreach([$volatility,$previousReturn,$directionRoll,$sizeRoll] as $value) {
            if(!is_finite($value)) { throw new InvalidArgumentException('Neutral inputs must be finite.'); }
        }
        if($volatility<=0 || $directionRoll<0 || $directionRoll>1 || $sizeRoll<0 || $sizeRoll>1) {
            throw new InvalidArgumentException('Neutral volatility and random draws are invalid.');
        }
        $probability=$previousReturn>0 ? 0.54 : ($previousReturn<0 ? 0.46 : 0.5);
        $direction=$directionRoll<$probability ? 1 : -1;
        $cluster=0.6+min(1.5,abs($previousReturn)/$volatility)*0.4;
        $magnitude=$volatility*(0.1+$sizeRoll*1.1)*$cluster;
        if($sizeRoll>0.98) { $magnitude*=1.5; }
        return $magnitude*$direction;
    }
}

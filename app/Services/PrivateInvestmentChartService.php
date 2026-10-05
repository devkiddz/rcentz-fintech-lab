<?php

namespace App\Services;

use App\Models\PrivateInvestmentInstrument;
use Illuminate\Support\Collection;

class PrivateInvestmentChartService
{
    public function forInstrument(PrivateInvestmentInstrument $instrument): array
    {
        // Basket NAV intraday chart uses only this investment's recorded NAV.
        if(app(InvestmentBasketPricer::class)->bound((int)$instrument->id)) {
            $rows=$instrument->prices()->where('timeframe','5m')->where('source','basket_reserve_nav')->orderByDesc('recorded_at')->limit(2016)->get()->reverse()->values();
            $series=$this->normalize($rows);
            // The newest reserve valuation may arrive between scheduled NAV history writes.
            // Present it as the developing bar, without modifying recorded history.
            if($series && $instrument->last_valued_at && (float)$instrument->current_price>0) {
                $at=$instrument->last_valued_at->copy()->startOfMinute();$at->minute=intdiv($at->minute,5)*5;
                $last=count($series)-1;$nav=(float)$instrument->current_price;
                $lastTime=\Carbon\Carbon::parse($series[$last]['time']);
                if($at->equalTo($lastTime)) {
                    $series[$last]['close']=$nav;$series[$last]['high']=max($series[$last]['high'],$nav);$series[$last]['low']=min($series[$last]['low'],$nav);
                } elseif($at->gt($lastTime)) {
                    $series[]=['time'=>$at->toIso8601String(),'open'=>$nav,'high'=>$nav,'low'=>$nav,'close'=>$nav,'volume'=>0];
                }
            }
            return ['source'=>'basket_reserve_nav','has_chart'=>count($series)>=2,'current_price'=>(float)$instrument->current_price,'previous_close'=>(float)$instrument->previous_price,'series'=>$series,'timeframes'=>['1d'=>array_slice($series,-288),'1w'=>$series,'1m'=>$series,'3m'=>$series,'all'=>$series],'default_timeframe'=>'1d'];
        }
        $rows = $instrument->prices()
            ->where('timeframe', '1d')
            ->orderBy('recorded_at')
            ->get();

        $series = $this->normalize($rows);

        $timeframes = [
            '1w' => array_slice($series, -7),
            '1m' => array_slice($series, -30),
            '3m' => $series,
            'all' => $series,
        ];

        return [
            'source' => 'private_investment_price_history',
            'has_chart' => count($series) >= 2,
            'current_price' => (float) $instrument->current_price,
            'previous_close' => (float) $instrument->previous_price,
            'series' => $series,
            'timeframes' => $timeframes,
            'default_timeframe' => count($timeframes['1m']) >= 2 ? '1m' : 'all',
        ];
    }

    private function normalize(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'time' => optional($row->recorded_at)?->toIso8601String(),
            'open' => (float) $row->open,
            'high' => (float) $row->high,
            'low' => (float) $row->low,
            'close' => (float) $row->close,
            'volume' => 0,
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)
          ->values()
          ->all();
    }
}

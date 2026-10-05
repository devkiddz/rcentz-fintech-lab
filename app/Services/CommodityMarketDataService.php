<?php

namespace App\Services;

use App\Models\CommodityInstrument;
use App\Models\CommodityPricePoint;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class CommodityMarketDataService
{
    public function __construct(
        private readonly CommodityAlphaVantageService $alphaVantage
    ) {}

    public function refresh(CommodityInstrument $commodity): array
    {
        if (! $commodity->external_feed_enabled) {
            throw new RuntimeException("External commodity feed is disabled for {$commodity->display_symbol}.");
        }

        if ($commodity->provider_family !== 'precious_metal') {
            throw new RuntimeException("No commodity data adapter is registered for {$commodity->provider_family}.");
        }

        $history = $this->alphaVantage->metalHistory($commodity->provider_symbol, 'daily');
        if (count($history) < 2) {
            throw new RuntimeException("No real commodity history was returned for {$commodity->display_symbol}.");
        }

        $spot = null;
        try {
            $spot = $this->alphaVantage->metalSpot($commodity->provider_symbol);
        } catch (\Throwable $e) {
            Log::warning('Commodity spot refresh unavailable; retaining latest historical value', [
                'symbol' => $commodity->symbol,
                'error' => $e->getMessage(),
            ]);
        }

        $last = $history[count($history) - 1];
        $previous = $history[count($history) - 2];
        $current = ($spot && $spot > 0) ? (float) $spot : (float) $last['price'];
        $previousClose = ($spot && $spot > 0) ? (float) $last['price'] : (float) $previous['price'];

        DB::transaction(function () use ($commodity, $history, $current, $previousClose) {
            foreach ($history as $row) {
                CommodityPricePoint::query()->updateOrCreate(
                    [
                        'commodity_instrument_id' => $commodity->id,
                        'interval' => '1d',
                        'timestamp' => CarbonImmutable::parse($row['date'])->startOfDay(),
                    ],
                    [
                        'price' => (float) $row['price'],
                        'source' => 'alpha_vantage',
                    ]
                );
            }

            $commodity->update([
                'current_price' => $current,
                'previous_close' => $previousClose,
                'last_updated' => now(),
            ]);
        });

        return [
            'instrument' => $commodity->fresh(),
            'rows' => count($history),
            'latest_date' => $last['date'],
            'latest_price' => $current,
            'spot_used' => (bool) ($spot && $spot > 0),
        ];
    }

    public function context(CommodityInstrument $commodity): array
    {
        $points = $commodity->pricePoints()
            ->where('interval', '1d')
            ->where('source', 'alpha_vantage')
            ->orderByDesc('timestamp')
            ->limit(220)
            ->get()
            ->sortBy('timestamp')
            ->values();

        $series = $points->map(fn (CommodityPricePoint $point) => [
            'time' => $point->timestamp?->toIso8601String(),
            'price' => (float) $point->price,
            // Shared analysis-chart compatibility only. The provider supplies a
            // daily reference price point, not OHLC; point_series=true preserves
            // that distinction for consumers.
            'open' => (float) $point->price,
            'high' => (float) $point->price,
            'low' => (float) $point->price,
            'close' => (float) $point->price,
            'volume' => 0,
            'point_series' => true,
        ])->filter(fn ($row) => $row['time'] && $row['price'] > 0)->values()->all();

        $prices = collect($series)->pluck('price')->map(fn ($value) => (float) $value)->values();
        $storedLatest = (float) ($prices->last() ?? 0);
        $current = (float) ($commodity->current_price ?? 0) ?: $storedLatest;
        $previous = (float) ($commodity->previous_close ?? 0) ?: $storedLatest;

        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float) ($recent->min('price') ?? 0);
        $resistance = (float) ($recent->max('price') ?? 0);
        $sma20 = $this->sma($prices->all(), 20);
        $sma50 = $this->sma($prices->all(), 50);
        $sma200 = $this->sma($prices->all(), 200);
        $windowStart = (float) ($prices->take(-min(20, $prices->count()))->first() ?? $current);
        $momentum = $windowStart > 0 ? (($current - $windowStart) / $windowStart) * 100 : 0.0;

        $trend = 'Neutral';
        if ($sma20 && $current > $sma20 && $momentum > 0) {
            $trend = 'Bullish';
        } elseif ($sma20 && $current < $sma20 && $momentum < 0) {
            $trend = 'Bearish';
        }

        // Spot observations retain their provider timestamps and their own source.
        // They are price points, not exchange OHLC candles.
        $spotPoints=$commodity->pricePoints()->where('interval','spot')
            ->where('source','alpha_vantage_spot')->orderByDesc('timestamp')->limit(1440)
            ->get()->sortBy('timestamp')->values();
        $spot=$spotPoints->map(fn($point)=>[
            'time'=>$point->timestamp?->toIso8601String(), 'price'=>(float)$point->price,
            'open'=>(float)$point->price,'high'=>(float)$point->price,
            'low'=>(float)$point->price,'close'=>(float)$point->price,
            'volume'=>0,'point_series'=>true,
        ])->filter(fn($row)=>$row['time'] && $row['price']>0)->values()->all();
        $oneDay=$series;
        $lastSpot=end($spot);
        $lastDaily=end($series);
        if($lastSpot && (!$lastDaily || strtotime($lastSpot['time'])>strtotime($lastDaily['time']))) {
            $oneDay[]=$lastSpot;
        }
        $spotFrames=[];
        foreach(['5m'=>300,'15m'=>900,'1h'=>3600,'4h'=>14400] as $frame=>$seconds) {
            $spotFrames[$frame]=collect($spot)->groupBy(fn($row)=>intdiv(strtotime($row['time']),$seconds))
                ->map(fn($rows)=>$rows->last())->values()->all();
        }

        return [
            'asset_class' => 'commodity',
            'commodity_instrument_id' => $commodity->id,
            'symbol' => $commodity->symbol,
            'display_symbol' => $commodity->display_symbol,
            'label' => $commodity->name,
            'marketplace' => 'live',
            'current_price' => $current,
            'previous_close' => $previous,
            'analysis_source' => count($series) >= 2 ? 'commodity_daily_price_points' : 'commodity_spot_only',
            'source' => count($spot)>=1 ? 'alpha_vantage_history_and_spot' : (count($series) >= 2 ? 'commodity_daily_price_points' : 'commodity_spot_only'),
            'has_chart' => count($oneDay) >= 2,
            'point_series' => true,
            'trend' => $trend,
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 0.5 ? 'Bullish' : ($momentum < -0.5 ? 'Bearish' : 'Neutral'),
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $sma20,
            'sma50' => $sma50,
            'sma200' => $sma200,
            'risk_reward' => 'Market-derived',
            'volume_current' => 0,
            'volume_average' => 0,
            'volume_vs_average' => null,
            'series' => $oneDay,
            'timeframes' => [
                '5m' => $spotFrames['5m'],
                '15m' => $spotFrames['15m'],
                '1h' => $spotFrames['1h'],
                '4h' => $spotFrames['4h'],
                '1d' => $oneDay,
                '1w' => array_slice($oneDay, -5),
                '1m' => array_slice($oneDay, -22),
                '3m' => array_slice($oneDay, -66),
                '1y' => $oneDay,
            ],
            'default_timeframe' => count($spotFrames['5m'])>=2 ? '5m' : '1d',
            'market_session' => 'Global spot',
            'active_sessions' => [],
            'preferred_sessions' => [],
            'preferred_session_active' => true,
            'captured_at' => optional($commodity->last_updated)?->toIso8601String() ?: now()->toIso8601String(),
        ];
    }

    private function sma(array $values, int $period): ?float
    {
        $values = array_values(array_filter(array_map('floatval', $values), fn ($value) => $value > 0));
        if (count($values) < $period) return null;

        $slice = array_slice($values, -$period);
        return array_sum($slice) / count($slice);
    }
}

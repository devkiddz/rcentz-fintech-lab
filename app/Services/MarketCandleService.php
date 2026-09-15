<?php

namespace App\Services;

use App\Models\StockCandle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class MarketCandleService
{
    public const INTERVAL = '15m';
    private const BUCKET_MINUTES = 15;

    public function __construct(
        private readonly MarketSessionService $marketSession
    ) {}

    public function record(string $symbol, float $price, CarbonInterface $at): ?StockCandle
    {
        if ($price <= 0 || ! $this->marketSession->isOpen($at)) {
            return null;
        }

        $marketTime = $this->marketSession->marketTime($at);
        $bucketMinute = intdiv($marketTime->minute, self::BUCKET_MINUTES) * self::BUCKET_MINUTES;

        $startedAt = $marketTime->copy()
            ->setMinute($bucketMinute)
            ->setSecond(0)
            ->setMicrosecond(0)
            ->utc();

        return DB::transaction(function () use ($symbol, $price, $startedAt) {
            $candle = StockCandle::query()
                ->where('symbol', $symbol)
                ->where('interval', self::INTERVAL)
                ->where('started_at', $startedAt)
                ->lockForUpdate()
                ->first();

            if (! $candle) {
                return StockCandle::create([
                    'symbol' => $symbol,
                    'interval' => self::INTERVAL,
                    'started_at' => $startedAt,
                    'open' => $price,
                    'high' => $price,
                    'low' => $price,
                    'close' => $price,
                    'sample_count' => 1,
                ]);
            }

            $candle->update([
                'high' => max((float) $candle->high, $price),
                'low' => min((float) $candle->low, $price),
                'close' => $price,
                'sample_count' => (int) $candle->sample_count + 1,
            ]);

            return $candle->fresh();
        });
    }
}

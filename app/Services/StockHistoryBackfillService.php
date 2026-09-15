<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockPriceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockHistoryBackfillService
{
    public function __construct(
        private AlphaVantageService $alphaVantage,
    ) {}

    public function backfill(Stock $stock, bool $replaceExisting = true): array
    {
        if (! $this->alphaVantage->isAvailable()) {
            return [
                'ok' => false,
                'symbol' => $stock->symbol,
                'reason' => 'alpha_vantage_unavailable',
                'imported' => 0,
            ];
        }

        $payload = $this->alphaVantage->daily($stock->symbol, 'compact');

        if (! $payload || empty($payload['rows'])) {
            return [
                'ok' => false,
                'symbol' => $stock->symbol,
                'reason' => 'no_history_returned',
                'imported' => 0,
            ];
        }

        $rows = $payload['rows'];
        $latest = end($rows);
        $current = (float)($stock->current_price ?? 0);
        $latestClose = (float)($latest['close'] ?? 0);

        // Protect the chart from importing stale / wrong-regime history.
        // We only replace local history when the provider's latest close is
        // reasonably close to the stock's current stored price.
        if ($current > 0 && $latestClose > 0) {
            $deviation = abs($latestClose - $current) / $current;

            if ($deviation > 0.30) {
                Log::warning('Alpha Vantage history rejected by continuity guard', [
                    'symbol' => $stock->symbol,
                    'current_price' => $current,
                    'latest_history_close' => $latestClose,
                    'deviation' => $deviation,
                ]);

                return [
                    'ok' => false,
                    'symbol' => $stock->symbol,
                    'reason' => 'continuity_guard_failed',
                    'imported' => 0,
                    'current_price' => $current,
                    'latest_history_close' => $latestClose,
                    'deviation_percent' => round($deviation * 100, 2),
                ];
            }
        }

        $count = DB::transaction(function () use ($stock, $rows, $replaceExisting) {
            if ($replaceExisting) {
                StockPriceHistory::query()
                    ->where('symbol', $stock->symbol)
                    ->where('interval', '1D')
                    ->delete();
            }

            $imported = 0;

            foreach ($rows as $row) {
                $timestamp = Carbon::createFromFormat(
                    'Y-m-d',
                    $row['date'],
                    MarketSessionService::TIMEZONE
                )
                    ->setTime(16, 0)
                    ->utc();

                StockPriceHistory::updateOrCreate(
                    [
                        'symbol' => $stock->symbol,
                        'interval' => '1D',
                        'timestamp' => $timestamp,
                    ],
                    [
                        'open' => $row['open'],
                        'high' => $row['high'],
                        'low' => $row['low'],
                        'close' => $row['close'],
                        'volume' => $row['volume'],
                    ]
                );

                $imported++;
            }

            return $imported;
        });

        return [
            'ok' => true,
            'symbol' => $stock->symbol,
            'imported' => $count,
            'latest_close' => $latestClose,
        ];
    }

    public function refreshLatest(Stock $stock): array
    {
        return $this->backfill($stock, false);
    }
}

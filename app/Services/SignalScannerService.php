<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Collection;

class SignalScannerService
{
    public function __construct(
        private readonly SignalGenerationService $generation,
        private readonly MarketPriceRouter $marketPriceRouter
    ) {}

    public function scan(
        ?string $marketplace = null,
        int $limit = 25,
        bool $force = false,
        ?string $symbol = null,
        string $trigger = 'scheduled'
    ): array {
        $marketplace = $this->marketPriceRouter->normalizeMarketplace(
            $marketplace ?: $this->marketPriceRouter->activeMarketplace()
        );
        $limit = max(1, min(250, $limit));

        $stocks = $this->stocks($limit, $symbol);
        $items = [];
        $stats = [
            'scanned' => 0,
            'generated' => 0,
            'rejected' => 0,
            'duplicate_open' => 0,
            'cooldown' => 0,
            'locked' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($stocks as $stock) {
            $stats['scanned']++;

            try {
                $item = $this->generation->generateForStock($stock, $marketplace, $force, $trigger);
                $status = $item['status'];
                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                } else {
                    $stats['skipped']++;
                }
                $items[] = $item;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $items[] = [
                    'status' => 'failed',
                    'stock_id' => $stock->id,
                    'symbol' => strtoupper($stock->symbol),
                    'signal_id' => null,
                    'signal_status' => null,
                    'reason' => $e->getMessage(),
                    'analysis_run_id' => null,
                    'strength' => null,
                    'confluence_score' => null,
                    'direction' => null,
                ];
            }
        }

        return [
            'marketplace' => $marketplace,
            'stats' => $stats,
            'items' => $items,
        ];
    }

    private function stocks(int $limit, ?string $symbol): Collection
    {
        $query = Stock::query()->active();

        if ($symbol) {
            $query->where('symbol', strtoupper(trim($symbol)));
        }

        return $query->orderBy('id')->limit($limit)->get();
    }
}

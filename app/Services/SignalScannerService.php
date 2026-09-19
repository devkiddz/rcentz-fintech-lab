<?php

namespace App\Services;

use App\Models\MarketInstrument;
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
        string $trigger = 'scheduled',
        string $assetClass = 'stock'
    ): array {
        $assetClass = strtolower(trim($assetClass ?: 'stock'));
        if (! in_array($assetClass, ['stock', 'forex', 'crypto', 'all'], true)) {
            $assetClass = 'stock';
        }

        $marketplace = in_array($assetClass, ['forex', 'crypto'], true)
            ? 'live'
            : $this->marketPriceRouter->normalizeMarketplace($marketplace ?: $this->marketPriceRouter->activeMarketplace());
        $limit = max(1, min(250, $limit));

        $instruments = $this->instruments($limit, $symbol, $assetClass);
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

        foreach ($instruments as $instrument) {
            $stats['scanned']++;

            try {
                $item = $this->generation->generateForInstrument($instrument, $marketplace, $force, $trigger);
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
                    'market_instrument_id' => $instrument->id,
                    'stock_id' => $instrument->stock_id,
                    'asset_class' => $instrument->asset_class,
                    'symbol' => $instrument->display_symbol,
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
            'asset_class' => $assetClass,
            'stats' => $stats,
            'items' => $items,
        ];
    }

    private function instruments(int $limit, ?string $symbol, string $assetClass): Collection
    {
        $query = MarketInstrument::query()->active()->with(['stock', 'forexPair', 'canonicalCryptoPair']);

        if ($assetClass !== 'all') {
            $query->where('asset_class', $assetClass);
        }

        if ($symbol) {
            $needle = strtoupper(str_replace('/', '', trim($symbol)));
            $query->where(function ($q) use ($needle) {
                $q->where('symbol', $needle)
                    ->orWhereRaw("REPLACE(display_symbol, '/', '') = ?", [$needle]);
            });
        }

        return $query->orderBy('id')->limit($limit)->get();
    }
}

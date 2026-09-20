<?php

namespace App\Console\Commands;

use App\Models\BotProduct;
use App\Models\MarketInstrument;
use App\Models\TradingBot;
use Illuminate\Console\Command;

class InspectMultiAssetBotSurfaces extends Command
{
    protected $signature = 'bots:inspect-multi-asset-surfaces';
    protected $description = 'Inspect E7 bot product/runtime market-instrument surface authority';

    public function handle(): int
    {
        $this->info('E7 multi-asset bot surface authority');
        $this->newLine();

        $instrumentRows = MarketInstrument::query()
            ->whereIn('asset_class', ['stock','forex','crypto'])
            ->selectRaw('UPPER(asset_class) as asset_class, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->groupBy('asset_class')
            ->orderBy('asset_class')
            ->get()
            ->map(fn ($row) => [$row->asset_class, (int) $row->total, (int) $row->active])
            ->all();

        $this->table(['Asset class','Registered','Active'], $instrumentRows);

        $checks = [
            ['Bot products missing MarketInstrument', BotProduct::query()->whereNull('market_instrument_id')->count()],
            ['Trading bots missing MarketInstrument', TradingBot::query()->whereNull('market_instrument_id')->count()],
            ['Active products on inactive instruments', BotProduct::query()
                ->where('is_active', true)
                ->whereHas('marketInstrument', fn ($q) => $q->where('is_active', false))
                ->count()],
            ['Active products without instrument relation', BotProduct::query()
                ->where('is_active', true)
                ->whereDoesntHave('marketInstrument')
                ->count()],
        ];

        $this->newLine();
        $this->table(['Surface authority check','Count'], $checks);

        $products = BotProduct::query()
            ->with('marketInstrument')
            ->latest('id')
            ->get()
            ->map(fn (BotProduct $product) => [
                $product->id,
                $product->name,
                strtoupper((string) ($product->marketInstrument?->asset_class ?? 'missing')),
                $product->marketInstrument?->display_symbol ?? '—',
                $product->is_active ? 'YES' : 'NO',
            ])
            ->all();

        $this->newLine();
        $this->table(['Product','Name','Asset','Instrument','Active'], $products);

        $failed = collect($checks)->sum(fn ($row) => (int) $row[1]);
        if ($failed > 0) {
            $this->error('E7 bot surface authority has unresolved identity issues.');
            return self::FAILURE;
        }

        $this->info('E7 multi-asset bot surfaces are READY for browser/runtime acceptance.');
        return self::SUCCESS;
    }
}

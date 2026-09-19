<?php

namespace App\Console\Commands;

use App\Models\CryptoPair;
use App\Models\MarketInstrument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectCryptoMarket extends Command
{
    protected $signature = 'crypto:inspect {symbol? : Optional crypto symbol such as BTCUSD}';
    protected $description = 'Inspect the C1 crypto market foundation and canonical MarketInstrument parent links.';

    public function handle(): int
    {
        foreach (['market_instruments', 'crypto_pairs', 'crypto_candles'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error($table.' is missing. Run migrations first.');
                return self::FAILURE;
            }
        }

        $query = CryptoPair::query()->with('marketInstrument')->withCount('candles')->orderBy('id');

        if ($symbol = $this->argument('symbol')) {
            $normalized = strtoupper(str_replace(['/', '-', '_', ' '], '', (string) $symbol));
            $query->where('symbol', $normalized);
        }

        $pairs = $query->get();
        $rows = [];

        foreach ($pairs as $pair) {
            $parent = $pair->marketInstrument;
            $latest = $pair->candles()->where('interval', '1d')->latest('timestamp')->first();
            $rows[] = [
                $parent?->id ?: '—',
                $pair->display_symbol,
                $parent?->is_active ? 'ACTIVE' : 'FOUNDATION',
                $pair->current_rate
                    ? number_format((float) $pair->current_rate, (int) $pair->price_precision, '.', '')
                    : '—',
                $pair->candles_count,
                $latest?->timestamp?->format('Y-m-d') ?: '—',
                $pair->candles_count >= 2 ? 'READY' : 'NEEDS_HISTORY',
            ];
        }

        $this->table(
            ['Parent', 'Pair', 'Parent State', 'Latest', 'Candles', 'Latest date', 'Feed'],
            $rows
        );

        $cryptoParents = MarketInstrument::query()->where('asset_class', 'crypto')->count();
        $activeCryptoParents = MarketInstrument::query()->where('asset_class', 'crypto')->where('is_active', true)->count();
        $cryptoPairs = CryptoPair::query()->count();
        $missingParent = CryptoPair::query()->whereNull('market_instrument_id')->count();
        $mismatches = DB::table('crypto_pairs as cp')
            ->leftJoin('market_instruments as mi', 'mi.id', '=', 'cp.market_instrument_id')
            ->where(function ($query) {
                $query->whereNull('mi.id')
                    ->orWhere('mi.asset_class', '!=', 'crypto')
                    ->orWhereColumn('mi.symbol', '!=', 'cp.symbol');
            })
            ->count();

        $this->newLine();
        $this->table(['Check', 'Count'], [
            ['Crypto MarketInstrument parents', $cryptoParents],
            ['Active crypto parents', $activeCryptoParents],
            ['Crypto pairs', $cryptoPairs],
            ['Pairs missing parent ID', $missingParent],
            ['Parent/child mismatches', $mismatches],
        ]);

        $green = $cryptoParents === 10
            && $cryptoPairs === 10
            && $missingParent === 0
            && $mismatches === 0;

        if (! $green) {
            $this->error('CRYPTO_C1_FOUNDATION_NOT_GREEN');
            return self::FAILURE;
        }

        $this->info('CRYPTO_C1_FOUNDATION_OK');
        $this->line('C1 identity/data foundation is green. Use markets:inspect-crypto-runtime for C2 runtime authority.');
        return self::SUCCESS;
    }
}

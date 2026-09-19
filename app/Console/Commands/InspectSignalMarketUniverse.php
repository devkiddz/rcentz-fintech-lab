<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Models\Signal;
use App\Services\SignalIntelligenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectSignalMarketUniverse extends Command
{
    protected $signature = 'signals:inspect-market-universe {symbol=EURUSD : Instrument symbol such as EURUSD or AAPL}';
    protected $description = 'Inspect generic multi-asset market-instrument wiring and run deterministic Signal intelligence without creating a Signal.';

    public function handle(SignalIntelligenceService $intelligence): int
    {
        $symbol = strtoupper(str_replace('/', '', (string) $this->argument('symbol')));
        $instrument = MarketInstrument::query()
            ->with(['stock', 'forexPair', 'canonicalCryptoPair'])
            ->where(function ($query) use ($symbol) {
                $query->where('symbol', $symbol)
                    ->orWhereRaw("REPLACE(display_symbol, '/', '') = ?", [$symbol]);
            })
            ->first();

        if (! $instrument) {
            $this->error("Market instrument {$symbol} was not found.");
            return self::FAILURE;
        }

        try {
            $result = $intelligence->analyzeInstrument($instrument, 'live');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $analysis = $result['analysis'];
        $qualification = $result['qualification'];
        $setup = $result['setup'];

        $this->table(['Instrument authority', 'Value'], [
            ['ID', $instrument->id],
            ['Instrument', $instrument->display_symbol],
            ['Asset class', strtoupper($instrument->asset_class)],
            ['Price', number_format((float) ($analysis['market_price'] ?? 0), $instrument->price_precision, '.', '')],
            ['Timeframe', strtoupper((string) ($analysis['timeframe'] ?? '-'))],
            ['Direction', strtoupper((string) ($analysis['direction'] ?? '-'))],
            ['Directional score', number_format((float) ($analysis['directional_score'] ?? 0), 2)],
            ['Confluence', number_format((float) ($analysis['confluence_score'] ?? 0), 2).'%'],
            ['Data quality', number_format((float) ($analysis['data_quality'] ?? 0), 2).'%'],
            ['Qualification', strtoupper((string) ($qualification['result'] ?? '-'))],
            ['Strength', strtoupper(str_replace('_', ' ', (string) ($qualification['strength'] ?? '-')))],
            ['Auto generation', ($qualification['eligible_for_auto_generation'] ?? false) ? 'YES' : 'NO'],
            ['Open Signals', Signal::query()->where('market_instrument_id', $instrument->id)->whereIn('status', Signal::OPEN_STATUSES)->count()],
        ]);

        if ($setup) {
            $this->table(['Setup', 'Value'], [
                ['Entry', number_format((float) $setup['entry_min'], $instrument->price_precision, '.', '').' - '.number_format((float) $setup['entry_max'], $instrument->price_precision, '.', '')],
                ['Stop', number_format((float) $setup['stop_loss'], $instrument->price_precision, '.', '')],
                ['TP1', number_format((float) data_get($setup, 'targets.0.price', 0), $instrument->price_precision, '.', '')],
                ['TP2', number_format((float) data_get($setup, 'targets.1.price', 0), $instrument->price_precision, '.', '')],
                ['TP3', number_format((float) data_get($setup, 'targets.2.price', 0), $instrument->price_precision, '.', '')],
                ['R:R', '1:'.number_format((float) $setup['risk_reward'], 2)],
            ]);
        }

        $missingBackfill = DB::table('signals')->whereNull('market_instrument_id')->count();
        $this->line('Signals missing market_instrument_id: '.$missingBackfill);

        if ($missingBackfill > 0) {
            $this->error('SIGNALS_FX2_MARKET_UNIVERSE_FAILED');
            return self::FAILURE;
        }

        $this->info('SIGNALS_FX2_MARKET_UNIVERSE_OK');
        return self::SUCCESS;
    }
}

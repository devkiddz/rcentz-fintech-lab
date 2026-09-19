<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Models\Signal;
use App\Services\SignalIntelligenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectCryptoSignalWiring extends Command
{
    protected $signature = 'signals:inspect-crypto-wiring {symbol=BTCUSD : Active Crypto instrument such as BTCUSD}';
    protected $description = 'Inspect Crypto Signal context, 24/7 timing inputs and READY-only authority without creating or publishing a Signal.';

    public function handle(SignalIntelligenceService $intelligence): int
    {
        $symbol = strtoupper(str_replace(['/', '-', '_', ' '], '', (string) $this->argument('symbol')));
        $instrument = MarketInstrument::query()
            ->with('canonicalCryptoPair')
            ->where('asset_class', MarketInstrument::ASSET_CRYPTO)
            ->where(function ($query) use ($symbol) {
                $query->where('symbol', $symbol)
                    ->orWhereRaw("REPLACE(display_symbol, '/', '') = ?", [$symbol]);
            })
            ->first();

        if (! $instrument) {
            $this->error("Crypto instrument {$symbol} was not found.");
            return self::FAILURE;
        }

        $pair = $instrument->canonicalCryptoPair;
        $history = $pair?->candles()->where('interval', '1d')->count() ?? 0;
        $beforeSignals = Signal::query()->count();
        $beforeRuns = DB::table('signal_analysis_runs')->count();

        try {
            $result = $intelligence->analyzeInstrument($instrument, 'live');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $context = $result['context'];
        $analysis = $result['analysis'];
        $qualification = $result['qualification'];
        $setup = $result['setup'];
        $signalState = (string) data_get($instrument->metadata, 'signal_runtime_state', 'disabled_until_crypto_signal_adapter');

        $checks = [
            'active_parent' => (bool) $instrument->is_active,
            'canonical_crypto_child' => $pair !== null,
            'real_history' => $history >= 2,
            'live_marketplace' => (string) ($context['marketplace'] ?? '') === 'live',
            'crypto_context' => (string) ($context['asset_class'] ?? '') === 'crypto',
            'parent_identity' => (int) ($context['market_instrument_id'] ?? 0) === (int) $instrument->id,
            'daily_source' => (string) ($context['analysis_source'] ?? '') === 'crypto_daily_history',
            '24_7_session' => (string) ($context['market_session'] ?? '') === '24/7',
            'preferred_24_7' => (bool) ($context['preferred_session_active'] ?? false),
            'valid_price' => (float) ($analysis['market_price'] ?? 0) > 0,
        ];

        $afterSignals = Signal::query()->count();
        $afterRuns = DB::table('signal_analysis_runs')->count();
        $checks['read_only_signal_count'] = $beforeSignals === $afterSignals;
        $checks['read_only_analysis_runs'] = $beforeRuns === $afterRuns;

        $this->table(['Crypto Signal authority', 'Value'], [
            ['Instrument ID', $instrument->id],
            ['Instrument', $instrument->display_symbol],
            ['Market runtime', $instrument->is_active ? 'ACTIVE' : 'INACTIVE'],
            ['Signal runtime', strtoupper($signalState)],
            ['Signal contract', 'READY ONLY'],
            ['Market hours', '24 / 7'],
            ['Analysis source', $context['analysis_source'] ?? '-'],
            ['Daily history', $history],
            ['Price', number_format((float) ($analysis['market_price'] ?? 0), (int) $instrument->price_precision, '.', '')],
            ['Timeframe', strtoupper((string) ($analysis['timeframe'] ?? '-'))],
            ['Direction', strtoupper((string) ($analysis['direction'] ?? '-'))],
            ['Confluence', number_format((float) ($analysis['confluence_score'] ?? 0), 2).'%'],
            ['Qualification', strtoupper((string) ($qualification['result'] ?? '-'))],
            ['Auto generation eligible', ($qualification['eligible_for_auto_generation'] ?? false) ? 'YES' : 'NO'],
            ['Setup available', $setup ? 'YES' : 'NO'],
            ['Open Crypto Signals', Signal::query()->where('market_instrument_id', $instrument->id)->whereIn('status', Signal::OPEN_STATUSES)->count()],
        ]);

        $failed = collect($checks)->filter(fn ($ok) => ! $ok);
        $this->table(['Check', 'State'], collect($checks)->map(fn ($ok, $name) => [str_replace('_', ' ', $name), $ok ? 'OK' : 'FAILED'])->values()->all());

        if ($failed->isNotEmpty()) {
            $this->error('CRYPTO_C3_SIGNAL_WIRING_FAILED');
            return self::FAILURE;
        }

        $this->info('CRYPTO_C3_SIGNAL_WIRING_OK');
        return self::SUCCESS;
    }
}

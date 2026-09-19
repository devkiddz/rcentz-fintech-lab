<?php

namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\Stock;
use RuntimeException;

class SignalIntelligenceService
{
    public function __construct(
        private readonly MarketInstrumentContextService $marketContext,
        private readonly SignalAnalysisEngine $analysisEngine,
        private readonly SignalQualificationService $qualification,
        private readonly SignalSetupBuilder $setupBuilder
    ) {}

    public function analyzeInstrument(MarketInstrument $instrument, ?string $marketplace = null, ?string $timeframe = null): array
    {
        $context = $this->marketContext->forInstrument($instrument, $marketplace);
        $analysis = $this->analysisEngine->analyze($context, $timeframe);
        $qualification = $this->qualification->qualify($analysis);
        $setup = $this->setupBuilder->build($context, $analysis, $qualification);

        return [
            'context' => $context,
            'analysis' => $analysis,
            'qualification' => $qualification,
            'setup' => $setup,
        ];
    }

    public function analyzeStock(Stock $stock, ?string $marketplace = null, ?string $timeframe = null): array
    {
        $instrument = MarketInstrument::query()->where('stock_id', $stock->id)->first();

        if (! $instrument) {
            throw new RuntimeException("MarketInstrument registry is missing stock {$stock->symbol}.");
        }

        return $this->analyzeInstrument($instrument, $marketplace, $timeframe);
    }
}

<?php

namespace App\Services;

use App\Models\Stock;

class SignalIntelligenceService
{
    public function __construct(
        private readonly SignalMarketContextService $marketContext,
        private readonly SignalAnalysisEngine $analysisEngine,
        private readonly SignalQualificationService $qualification,
        private readonly SignalSetupBuilder $setupBuilder
    ) {}

    public function analyzeStock(Stock $stock, ?string $marketplace = null, ?string $timeframe = null): array
    {
        $context = $this->marketContext->forStock($stock, $marketplace);
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
}

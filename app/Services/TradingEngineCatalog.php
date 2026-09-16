<?php

namespace App\Services;

use App\Services\Legacy\LegacyStockExecutionEngine;
use App\Services\Simulation\ManualPerformanceEngine;
use App\Services\Simulation\OutcomeScenarioEngine;

final class TradingEngineCatalog
{
    public function all(): array
    {
        return [
            [
                'key' => 'market_contract',
                'name' => 'Market Contract Engine',
                'status' => 'ACTIVE',
                'execution' => StockTradeExecutor::class,
                'position' => TradePositionService::class,
                'price_source' => 'stocks.current_price (API-fed persisted quote)',
                'financial_mutation' => 'YES',
                'purpose' => 'Canonical user/admin/bot/copy trading path.',
            ],
            [
                'key' => 'legacy_trade_plan',
                'name' => 'Legacy Trade Plan Engine',
                'status' => 'ISOLATED',
                'execution' => LegacyStockExecutionEngine::class,
                'position' => 'none',
                'price_source' => 'stocks.current_price (legacy stored-price path)',
                'financial_mutation' => 'YES',
                'purpose' => 'Backward compatibility for historical StockTradePlan rows only.',
            ],
            [
                'key' => 'manual_presentation',
                'name' => 'Manual Performance Engine',
                'status' => 'PRESENTATION ONLY',
                'execution' => ManualPerformanceEngine::class,
                'position' => 'none',
                'price_source' => 'none',
                'financial_mutation' => 'NO',
                'purpose' => 'Overrides displayed bot/strategy metrics without changing ledger truth.',
            ],
            [
                'key' => 'scenario_outcome',
                'name' => 'Scenario Outcome Engine',
                'status' => 'ISOLATED',
                'execution' => OutcomeScenarioEngine::class,
                'position' => 'virtual only',
                'price_source' => 'scenario input',
                'financial_mutation' => 'NO',
                'purpose' => 'Models WIN / LOSS / FLAT outcomes for demos and tests.',
            ],
        ];
    }
}

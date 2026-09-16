<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\TradingController;
use App\Services\CopyTradingService;
use App\Services\Legacy\LegacyStockExecutionEngine;
use App\Services\MarketTradeContractEngine;
use App\Services\StockTradePlanService;
use App\Services\TradingBotService;
use Illuminate\Console\Command;
use ReflectionMethod;
use ReflectionNamedType;

class InspectTradingWiring extends Command
{
    protected $signature = 'trading:wiring';

    protected $description = 'Verify that real trading surfaces and legacy compatibility are wired to the intended engines.';

    public function handle(): int
    {
        $checks = [
            ['Customer BUY', TradingController::class, 'executeBuy', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Customer SELL', TradingController::class, 'executeSell', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Admin strategy', AdminStockController::class, 'executeStrategyTrade', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Direct admin', AdminStockController::class, 'executeAdminTrade', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Trade for user', AdminStockController::class, 'executeUserTrade', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Copy trading', CopyTradingService::class, '__construct', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Trading bot', TradingBotService::class, '__construct', MarketTradeContractEngine::class, 'MARKET CONTRACT'],
            ['Historical trade plan', StockTradePlanService::class, 'processDuePlans', LegacyStockExecutionEngine::class, 'LEGACY ONLY'],
        ];

        $rows = [];
        $ok = true;

        foreach ($checks as [$surface,$class,$method,$dependency,$lane]) {
            $wired = $this->hasTypedParameter($class,$method,$dependency);
            $ok = $ok && $wired;
            $rows[] = [
                $surface,
                $lane,
                class_basename($dependency),
                $wired ? 'WIRED' : 'MISMATCH',
            ];
        }

        $this->info('Rcentz trading traffic lanes');
        $this->newLine();
        $this->table(['Surface', 'Lane', 'Engine boundary', 'Status'], $rows);
        $this->newLine();
        $this->line('Presentation/manual P/L and OutcomeScenarioEngine remain outside all financial lanes.');
        $this->line('V5.11 routes canonical execution prices through the Live / Controlled marketplace authority.');

        if (! $ok) {
            $this->error('One or more trading surfaces are not wired to the expected engine.');
            return self::FAILURE;
        }

        $this->info('All trading lanes are wired correctly.');
        return self::SUCCESS;
    }

    private function hasTypedParameter(string $class,string $method,string $dependency): bool
    {
        $reflection = new ReflectionMethod($class,$method);

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && $type->getName() === $dependency) {
                return true;
            }
        }

        return false;
    }
}

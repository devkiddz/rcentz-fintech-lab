<?php

namespace App\Console\Commands;

use App\Services\Simulation\OutcomeScenarioEngine;
use Illuminate\Console\Command;

class SimulateTradingOutcome extends Command
{
    protected $signature = 'trading:scenario
        {outcome : win, loss or flat}
        {--entry=100 : Entry price}
        {--qty=1 : Quantity}
        {--move=1 : Scenario move percent}
        {--direction=long : long or short}';

    protected $description = 'Run the isolated outcome simulator without touching wallets, holdings or trades.';

    public function handle(OutcomeScenarioEngine $engine): int
    {
        try {
            $result = $engine->resolve(
                (float) $this->option('entry'),
                (float) $this->option('qty'),
                strtolower((string) $this->argument('outcome')),
                (float) $this->option('move'),
                strtolower((string) $this->option('direction')),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Scenario only - no financial records were changed.');
        $this->table(
            ['Outcome', 'Direction', 'Entry', 'Exit', 'Qty', 'P/L', 'Return %'],
            [[
                strtoupper($result['outcome']),
                strtoupper($result['direction']),
                number_format($result['entry_price'], 4),
                number_format($result['exit_price'], 4),
                number_format($result['quantity'], 6),
                number_format($result['profit_loss'], 4),
                number_format($result['return_percent'], 4).'%',
            ]]
        );

        return self::SUCCESS;
    }
}

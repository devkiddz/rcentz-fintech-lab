<?php

namespace App\Console\Commands;

use App\Services\ControlledMarketEngine;
use Illuminate\Console\Command;

class TickControlledMarket extends Command
{
    protected $signature = 'market:controlled-tick {--mode= : Optional up, down or range override for this tick only}';
    protected $description = 'Advance every active Controlled Market instrument by one market tick.';

    public function handle(ControlledMarketEngine $engine): int
    {
        $mode = $this->option('mode') ?: null;
        $result = $engine->tickAll($mode);

        $this->info('Controlled Market tick complete.');
        $this->table(['Mode','Updated','Failed'], [[
            strtoupper($result['mode']),
            $result['updated'],
            $result['failed'],
        ]]);

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\InvestmentBasketPricer;
final class SyncInvestmentBaskets extends Command {
    protected $signature='private-investments:sync-baskets';
    protected $description='Update explicitly bound investment reserves, NAV and holdings from persisted basket prices';
    public function handle(InvestmentBasketPricer $pricer): int {
        $r=$pricer->run(); $this->line(json_encode($r));
        return $r['failed']>0 ? self::FAILURE : self::SUCCESS;
    }
}

<?php
namespace App\Console\Commands;
use App\Models\MarketInstrument;
use App\Services\CommodityExecutionQuoteService;
use Illuminate\Console\Command;
final class RefreshCommoditySpotQuotes extends Command
{
    protected $signature = 'commodities:refresh-spot';
    protected $description = 'Refresh executable USD precious-metal spot marks without submitting orders';
    public function handle(CommodityExecutionQuoteService $quotes): int
    {
        $failed=0;
        foreach (MarketInstrument::where('asset_class','commodity')->where('is_active',true)->get() as $instrument) {
            $metal=$instrument->canonicalCommodityInstrument;
            if (!$metal?->external_feed_enabled) { continue; }
            try {
                $price=$quotes->quote($instrument,'buy','live');
                $this->line($instrument->symbol.': '.$price);
            } catch (\Throwable $e) {
                $failed++;
                $this->warn($instrument->symbol.': '.$e->getMessage());
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

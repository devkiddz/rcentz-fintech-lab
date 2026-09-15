<?php
namespace App\Console\Commands;

use App\Models\TradingBot;
use App\Services\TradingBotService;
use Illuminate\Console\Command;

class RunTradingBots extends Command
{
    protected $signature='trading-bots:run';
    protected $description='Execute active subscribed trading bots that are due';

    public function handle(TradingBotService $service):int
    {
        $count=0;
        TradingBot::with(['user.kyc','stock','subscription.product'])
            ->where('status','active')
            ->whereHas('subscription',fn($q)=>$q->where('status','active')->where(function($x){$x->whereNull('ends_at')->orWhere('ends_at','>',now());}))
            ->where(function($q){$q->whereNull('next_run_at')->orWhere('next_run_at','<=',now());})
            ->chunkById(100,function($bots)use($service,&$count){
                foreach($bots as $bot){$service->run($bot);$count++;}
            });
        $this->info("Checked {$count} due subscribed trading bot(s).");
        return self::SUCCESS;
    }
}

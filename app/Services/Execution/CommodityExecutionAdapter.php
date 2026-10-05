<?php
namespace App\Services\Execution;

use App\Contracts\MarketExecutionAdapter;
use App\Models\MarketInstrument;
use App\Models\User;
use App\Services\CommodityExecutionQuoteService;
use App\Services\PaperTrading\PaperBrokerService;
use RuntimeException;

final class CommodityExecutionAdapter implements MarketExecutionAdapter
{
    public function assetClass(): string { return 'commodity'; }
    public function supports(MarketInstrument $instrument): bool
    {
        try { app(CommodityExecutionQuoteService::class)->assertInstrument($instrument); return true; }
        catch (\Throwable) { return false; }
    }
    public function capabilities(MarketInstrument $instrument): array
    {
        $ready = config('paper_trading.enabled',false) && $this->supports($instrument);
        return ['asset_class'=>'commodity','adapter'=>self::class,'executable'=>$ready,
            'quantity_unit'=>'troy_ounces','execution_model'=>'paper_v1',
            'reason'=>$ready ? 'Troy-ounce position trading; live fills require a fresh spot quote.' : 'Active USD Gold/Silver troy-ounce instrument and position engine required.'];
    }
    public function execute(User $user, MarketInstrument $instrument, string $side, float $quantity, array $context=[]): mixed
    {
        if (!$this->capabilities($instrument)['executable']) { throw new RuntimeException('Commodity execution is unavailable.'); }
        $key = (string)($context['idempotency_key'] ?? '');
        if ($key === '') { throw new RuntimeException('Commodity order requires an idempotency key.'); }
        return app(PaperBrokerService::class)->open($user,$instrument,$side,$quantity,'units',$key,[], $context);
    }
}

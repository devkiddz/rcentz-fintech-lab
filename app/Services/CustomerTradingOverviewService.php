<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\TradePosition;
use App\Models\User;

final class CustomerTradingOverviewService
{
    public function __construct(private MarketPriceRouter $prices) {}

    public function build(User $user): array
    {
        $marketplace=$this->prices->activeMarketplace();
        $positions=TradePosition::query()->where('user_id',$user->id)
            ->where('marketplace',$marketplace)->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0);
        $kycRequired=(bool)is_kyc_enabled();
        $kycApproved=(bool)$user->kyc?->isApproved();
        $readOnly=$user->isProductionDemo() && (bool)config('release.production_demo.read_only',true);
        return [
            'directional'=>(bool)config('paper_trading.enabled'),
            'marketplace'=>$marketplace,
            'verification_required'=>$kycRequired && !$kycApproved,
            'verification_status'=>$user->kyc?->status ?: 'not submitted',
            'read_only'=>$readOnly,
            'open_count'=>(clone $positions)->count(),
            'positions'=>(clone $positions)->with(['marketInstrument','stock.marketInstrument'])
                ->latest('opened_at')->limit(3)->get(),
            'orders'=>BrokerOrder::query()->where('user_id',$user->id)
                ->with(['marketInstrument','execution'])->latest('id')->limit(3)->get(),
        ];
    }
}

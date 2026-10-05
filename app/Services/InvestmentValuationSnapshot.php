<?php
namespace App\Services;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentHolding;
use Illuminate\Support\Facades\DB;
final class InvestmentValuationSnapshot {
    public function forPage(int $id, $user): array {
        // A consistent read prevents the instrument and reserve panels straddling a valuation commit.
        if(DB::getDriverName()==='mysql' && DB::transactionLevel()===0) DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        return DB::transaction(function() use($id,$user){
            $instrument=PrivateInvestmentInstrument::with([
                'assets'=>fn($q)=>$q->where('status','active')->orderByDesc('current_valuation'),
                'assets.marketInstrument','assets.privateMarketReference','assets.publicBaseAsset',
                'events'=>fn($q)=>$q->where('approval_state','approved')->latest('effective_at')->limit(12),
            ])->findOrFail($id);
            abort_unless($instrument->is_visible,404);
            $analysis=app(PrivateInvestmentChartService::class)->forInstrument($instrument);
            $viewerHolding=$user->isAdmin()?null:PrivateInvestmentHolding::where('user_id',$user->id)->where('instrument_id',$id)->first();
            $viewerWallet=$user->isAdmin()?null:$user->wallet()->first();
            return compact('instrument','analysis','viewerHolding','viewerWallet');
        });
    }
}

<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\TradePosition;

class CopyRelationshipLifecycleService
{
    public function __construct(private TradePositionService $positions) {}

    public function expireDue(): int
    {
        $completed=0;

        CopyRelationship::query()
            ->where(function($q){
                $q->where(function($active){
                    $active->where('status','active')
                        ->whereNotNull('ends_at')
                        ->where('ends_at','<=',now());
                })->orWhereIn('status',['settling','settlement_failed']);
            })
            ->chunkById(100,function($relationships) use (&$completed){
                foreach($relationships as $relationship){
                    $relationship->update(['status'=>'settling']);

                    $result=$this->positions->settleContext(
                        'copy_relationship',
                        $relationship->id,
                        'copy_contract_end'
                    );

                    $remaining=TradePosition::where(
                            'context_type',
                            'copy_relationship'
                        )
                        ->where('context_id',$relationship->id)
                        ->whereIn('status',['open','exit_queued'])
                        ->where('open_quantity','>',0)
                        ->exists();

                    if(! $remaining){
                        $relationship->update([
                            'status'=>'completed',
                            'used_amount'=>0,
                            'completed_at'=>$relationship->completed_at ?? now(),
                        ]);

                        $completed++;
                    } elseif(($result['failed']??0)>0){
                        $relationship->update([
                            'status'=>'settlement_failed',
                        ]);
                    }
                }
            });

        return $completed;
    }
}

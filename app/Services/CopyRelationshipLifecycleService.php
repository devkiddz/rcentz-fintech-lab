<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\CopyRelationship;
use App\Models\TradePosition;

class CopyRelationshipLifecycleService
{
    public function __construct(private BrokerOrderService $orders) {}

    public function expireDue(): int
    {
        $completed = 0;

        CopyRelationship::query()
            ->with('follower')
            ->where(function ($q) {
                $q->where(function ($active) {
                    $active->where('status', 'active')
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<=', now());
                })->orWhereIn('status', ['settling', 'settlement_failed']);
            })
            ->chunkById(100, function ($relationships) use (&$completed) {
                foreach ($relationships as $relationship) {
                    $relationship->update(['status' => 'settling']);

                    $positions = TradePosition::query()
                        ->with(['marketInstrument', 'stock.marketInstrument'])
                        ->where('user_id', $relationship->follower_id)
                        ->where('context_type', 'copy_relationship')
                        ->where('context_id', $relationship->id)
                        ->whereIn('status', ['open', 'exit_queued'])
                        ->where('open_quantity', '>', 0)
                        ->get();

                    $failed = 0;
                    foreach ($positions as $position) {
                        try {
                            $order = $this->orders->placePositionClose(
                                $relationship->follower,
                                $position,
                                null,
                                'copy-contract:'.$relationship->id.':position:'.$position->id.':'.now()->utc()->format('YmdHi'),
                                [
                                    'execution_source' => 'copy_trade',
                                    'execution_source_id' => $relationship->id,
                                    'context_type' => 'copy_relationship',
                                    'context_id' => $relationship->id,
                                    'actor_type' => 'system',
                                    'actor_id' => null,
                                    'exit_reason' => 'copy_contract_end',
                                    'metadata' => [
                                        'copy_relationship_id' => $relationship->id,
                                        'copy_strategy_id' => $relationship->copy_strategy_id,
                                        'settlement_reason' => 'copy_contract_end',
                                    ],
                                ]
                            );

                            if ($order->status !== BrokerOrder::STATUS_FILLED) {
                                $failed++;
                            }
                        } catch (\Throwable $e) {
                            \Log::warning('Copy contract BrokerOrder settlement failed', [
                                'copy_relationship_id' => $relationship->id,
                                'position_id' => $position->id,
                                'error' => $e->getMessage(),
                            ]);
                            $failed++;
                        }
                    }

                    $remaining = TradePosition::query()
                        ->where('context_type', 'copy_relationship')
                        ->where('context_id', $relationship->id)
                        ->whereIn('status', ['open', 'exit_queued'])
                        ->where('open_quantity', '>', 0)
                        ->exists();

                    if (! $remaining) {
                        $relationship->update([
                            'status' => 'completed',
                            'used_amount' => 0,
                            'completed_at' => $relationship->completed_at ?? now(),
                        ]);
                        $completed++;
                    } elseif ($failed > 0) {
                        $relationship->update(['status' => 'settlement_failed']);
                    }
                }
            });

        return $completed;
    }
}

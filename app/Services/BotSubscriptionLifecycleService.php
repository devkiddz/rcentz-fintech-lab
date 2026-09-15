<?php

namespace App\Services;

use App\Models\BotSubscription;
use Illuminate\Support\Facades\DB;

class BotSubscriptionLifecycleService
{
    public function expireDue(): int
    {
        $expired = 0;

        BotSubscription::query()
            ->whereIn('status', ['active','paused'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$expired) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, &$expired) {
                        $locked = BotSubscription::query()
                            ->whereKey($subscription->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked
                            || ! in_array($locked->status, ['active','paused'], true)
                            || ! $locked->ends_at
                            || $locked->ends_at->isFuture()) {
                            return;
                        }

                        $locked->update([
                            'status' => 'expired',
                            'expired_at' => now(),
                        ]);

                        if ($locked->bot) {
                            $locked->bot->update([
                                'status' => 'paused',
                                'next_run_at' => null,
                            ]);
                        }

                        $expired++;
                    });
                }
            });

        return $expired;
    }
}

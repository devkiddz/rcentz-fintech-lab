<?php

namespace App\Services;

use App\Models\CopyRelationship;

class CopyRelationshipLifecycleService
{
    public function expireDue(): int
    {
        $count = 0;

        CopyRelationship::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->chunkById(100, function ($relationships) use (&$count) {
                foreach ($relationships as $relationship) {
                    $relationship->update([
                        'status' => 'completed',
                        'completed_at' => $relationship->completed_at ?? now(),
                    ]);
                    $count++;
                }
            });

        return $count;
    }
}

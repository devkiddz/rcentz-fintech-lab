<?php

namespace App\Observers;

use App\Models\SignalEvent;
use App\Services\SignalCustomerNotificationService;
use Illuminate\Support\Facades\Log;

class SignalEventObserver
{
    public function created(SignalEvent $event): void
    {
        try {
            app(SignalCustomerNotificationService::class)->notifyForEvent($event);
        } catch (\Throwable $e) {
            // Customer alerting must never become lifecycle authority. The event
            // stays authoritative and the sync command can repair missed alerts.
            Log::warning('Signal event notification observer failed safely.', [
                'signal_event_id' => $event->id,
                'signal_id' => $event->signal_id,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

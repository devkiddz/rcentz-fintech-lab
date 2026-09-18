<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use App\Services\CustomerSignalService;
use App\Services\MarketInstrumentContextService;
use App\Services\SignalTimingService;
use Illuminate\Support\Facades\Auth;

class SignalController extends Controller
{
    public function index(CustomerSignalService $signals)
    {
        $user = Auth::user();

        return view('signals.index', [
            'deliveries' => $signals->current($user),
            'summary' => $signals->summary($user),
        ]);
    }

    public function history(CustomerSignalService $signals)
    {
        $user = Auth::user();

        return view('signals.history', [
            'deliveries' => $signals->history($user),
            'summary' => $signals->summary($user),
        ]);
    }

    public function show(
        Signal $signal,
        CustomerSignalService $signals,
        MarketInstrumentContextService $marketContext,
        SignalTimingService $timingService
    ) {
        $user = Auth::user();
        $delivery = $signals->deliveryFor($user, $signal);

        if ($delivery->read_at === null) {
            $delivery->forceFill(['read_at' => now()])->save();
        }

        $signal->loadMissing(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets', 'events', 'revisions']);
        $timeline = $signals->timeline($signal);

        try {
            $analysis = $marketContext->forSignal($signal);
        } catch (\Throwable) {
            $snapshot = (array) ($signal->analysis_snapshot ?? []);
            $analysis = [
                'has_chart' => false,
                'current_price' => $snapshot['current_price'] ?? $signal->entry_min,
                'source' => $signal->marketplace === 'controlled' ? 'controlled_signal_snapshot' : 'signal_snapshot',
                'timeframes' => [],
                'default_timeframe' => $signal->timeframe,
            ];
        }

        $timing = $timingService->forSignal($signal, $analysis);

        return view('signals.show', compact('signal', 'delivery', 'analysis', 'timeline', 'timing'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateMarketReference;
use App\Services\InvestmentBaseAssetPerformanceService;
use App\Services\PrivateBaseAssetMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrivateBaseAssetMovementController extends Controller
{
    public function update(
        Request $request,
        PrivateMarketReference $reference,
        PrivateBaseAssetMovementService $movement
    ) {
        $data = $request->validate([
            'movement_mode' => [
                'required',
                Rule::in(['manual', 'auto']),
            ],
            'movement_behavior' => [
                'required',
                Rule::in(['smart', 'up', 'down', 'range']),
            ],
            'movement_strength' => [
                'required',
                'numeric',
                'min:0.1',
                'max:3',
            ],
            'movement_volatility_percent' => [
                'required',
                'numeric',
                'min:0.001',
                'max:5',
            ],
            'movement_tick_seconds' => [
                'required',
                'integer',
                Rule::in([5, 10, 15, 30, 60, 120, 300]),
            ],
        ]);

        $wasAuto = $reference->movement_mode === 'auto';

        $reference = $movement->updateSettings(
            $reference,
            $data
        );

        if (
            $reference->movement_mode === 'auto'
            && ! $wasAuto
        ) {
            $reference = $movement->moveNow(
                $reference,
                $reference->movement_behavior,
                auth()->id()
            );
        }

        return back()->with(
            'success',
            $reference->movement_mode === 'auto'
                ? 'Automatic Private Base Asset movement enabled.'
                : 'Private Base Asset movement control updated.'
        );
    }

    public function move(
        Request $request,
        PrivateMarketReference $reference,
        PrivateBaseAssetMovementService $movement
    ) {
        $data = $request->validate([
            'behavior' => [
                'required',
                Rule::in(['smart', 'up', 'down', 'range']),
            ],
        ]);

        $movement->moveNow(
            $reference,
            $data['behavior'],
            auth()->id()
        );

        return back()->with(
            'success',
            'Private Base Asset movement recorded.'
        );
    }

    public function runtime(
        PrivateMarketReference $reference,
        PrivateBaseAssetMovementService $movement,
        InvestmentBaseAssetPerformanceService $performance
    ): JsonResponse {
        $tick = $movement->tickIfDue($reference);
        $reference = $tick['reference']->refresh();

        return response()->json([
            'moved' => (bool) $tick['moved'],
            'reason' => $tick['reason'],
            'settings' => $movement->settings($reference),
            'analysis' => $performance->forPrivate($reference),
        ]);
    }

    public function performance(
        PrivateMarketReference $reference,
        InvestmentBaseAssetPerformanceService $performance
    ): JsonResponse {
        $reference->refresh();

        return response()->json([
            'analysis' => $performance->forPrivate($reference),
        ]);
    }
}

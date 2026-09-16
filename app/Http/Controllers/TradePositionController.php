<?php

namespace App\Http\Controllers;

use App\Models\TradePosition;
use App\Services\MarketPriceRouter;
use App\Services\MarketSessionService;
use App\Services\TradePositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradePositionController extends Controller
{
    public function index(MarketSessionService $marketSession)
    {
        $positions=TradePosition::with([
                'stock',
                'events'=>fn($q)=>$q->latest()->limit(10),
            ])
            ->where('user_id',Auth::id())
            ->latest('opened_at')
            ->paginate(30);

        $marketStatus=$marketSession->status();
        $marketTime=$marketSession->now();

        return view(
            'trading.positions.index',
            compact('positions','marketStatus','marketTime')
        );
    }

    public function updateRisk(
        Request $request,
        TradePosition $position,
        TradePositionService $service
    ) {
        abort_unless($position->user_id===Auth::id(),403);

        $data=$request->validate([
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'duration_minutes'=>'nullable|integer|min:1|max:43200',
        ]);

        try{
            $service->updateRisk(
                $position,
                isset($data['stop_loss_percent'])
                    ? (float)$data['stop_loss_percent']
                    : null,
                isset($data['take_profit_percent'])
                    ? (float)$data['take_profit_percent']
                    : null,
                isset($data['duration_minutes'])
                    ? (int)$data['duration_minutes']
                    : null,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with(
            'success',
            'Trade management updated for this contract marketplace.'
        );
    }

    public function close(
        Request $request,
        TradePosition $position,
        TradePositionService $service
    ) {
        abort_unless($position->user_id===Auth::id(),403);

        try{
            $trade=$service->kill(
                $position,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        $position->refresh();

        return back()->with(
            'success',
            'Trade killed at CMP '.
            currency_symbol().number_format((float)$trade->price_per_share,2).
            '. Realized P/L: '.
            (($position->realized_profit_loss >= 0) ? '+' : '').
            currency_symbol().number_format((float)$position->realized_profit_loss,2).
            '.'
        );
    }

    public function partialClose(
        Request $request,
        TradePosition $position,
        TradePositionService $service,
        MarketSessionService $marketSession,
        MarketPriceRouter $prices
    ) {
        abort_unless($position->user_id===Auth::id(),403);

        if($prices->requiresRegularSession($position->marketplace ?: 'live') && ! $marketSession->isOpen()){
            return back()->withErrors([
                'position'=>'Partial close for a Live contract requires an open regular market session. Controlled contracts use the controlled marketplace session.'
            ]);
        }

        $data=$request->validate([
            'quantity'=>'required|numeric|min:0.000001',
        ]);

        $quantity=(float)$data['quantity'];
        $open=(float)$position->open_quantity;

        if($quantity >= $open){
            return back()->withErrors([
                'position'=>'Partial close must be lower than the remaining quantity. Use Kill Trade to terminate the whole contract.'
            ]);
        }

        try{
            $service->close(
                $position,
                'manual_partial_close',
                $quantity,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with(
            'success',
            'Partial close executed. Remaining exposure stays under the trade contract.'
        );
    }

    public function reenter(
        Request $request,
        TradePosition $position,
        TradePositionService $service
    ) {
        abort_unless($position->user_id===Auth::id(),403);

        $data=$request->validate([
            'quantity'=>'nullable|numeric|min:0.000001',
        ]);

        try{
            $new=$service->reenter(
                $position,
                isset($data['quantity'])
                    ? (float)$data['quantity']
                    : null,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with(
            'success',
            'New trade contract opened as position #'.$new->id.'.'
        );
    }
}

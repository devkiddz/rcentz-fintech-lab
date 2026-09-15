<?php

namespace App\Http\Controllers;

use App\Models\TradePosition;
use App\Services\TradePositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradePositionController extends Controller
{
    public function index()
    {
        $positions=TradePosition::with(['stock','events'=>fn($q)=>$q->latest()->limit(8)])
            ->where('user_id',Auth::id())
            ->latest('opened_at')
            ->paginate(30);

        return view('trading.positions.index',compact('positions'));
    }

    public function updateRisk(Request $request,TradePosition $position,TradePositionService $service)
    {
        abort_unless($position->user_id===Auth::id(),403);

        $data=$request->validate([
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'duration_minutes'=>'nullable|integer|min:1|max:43200',
        ]);

        try{
            $service->updateRisk(
                $position,
                isset($data['stop_loss_percent'])?(float)$data['stop_loss_percent']:null,
                isset($data['take_profit_percent'])?(float)$data['take_profit_percent']:null,
                isset($data['duration_minutes'])?(int)$data['duration_minutes']:null,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with('success','Position risk controls updated.');
    }

    public function close(Request $request,TradePosition $position,TradePositionService $service)
    {
        abort_unless($position->user_id===Auth::id(),403);

        try{
            $service->close($position,'manual_close',null,'user',Auth::id());
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with('success','Position close submitted.');
    }

    public function partialClose(Request $request,TradePosition $position,TradePositionService $service)
    {
        abort_unless($position->user_id===Auth::id(),403);
        $data=$request->validate(['quantity'=>'required|numeric|min:0.000001']);

        try{
            $service->close($position,'manual_partial_close',(float)$data['quantity'],'user',Auth::id());
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with('success','Partial close submitted.');
    }

    public function reenter(Request $request,TradePosition $position,TradePositionService $service)
    {
        abort_unless($position->user_id===Auth::id(),403);
        $data=$request->validate(['quantity'=>'nullable|numeric|min:0.000001']);

        try{
            $new=$service->reenter(
                $position,
                isset($data['quantity'])?(float)$data['quantity']:null,
                'user',
                Auth::id()
            );
        }catch(\Throwable $e){
            return back()->withErrors(['position'=>$e->getMessage()]);
        }

        return back()->with('success','Re-entry opened as position #'.$new->id.'.');
    }
}

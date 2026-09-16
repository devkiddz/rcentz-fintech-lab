<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopyStrategy;
use App\Models\Stock;
use App\Models\User;
use App\Services\CopyTradingService;
use App\Services\MarketTradeContractEngine;
use App\Services\StockAnalysisService;
use App\Models\TradePosition;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with(['holdings'=>fn($q)=>$q->with('user')])
            ->withCount('holdings')
            ->withCount('transactions')
            ->orderBy('symbol')
            ->get();

        $stats = [
            'total_stocks'=>$stocks->count(),
            'active_stocks'=>$stocks->where('is_active',true)->count(),
            'stocks_with_holdings'=>$stocks->where('holdings_count','>',0)->count(),
            'total_holdings'=>$stocks->sum('holdings_count'),
            'total_transactions'=>$stocks->sum('transactions_count'),
        ];

        return view('admin.stocks.index',compact('stocks','stats'));
    }

    public function show(Stock $stock, StockAnalysisService $analysisService)
    {
        $stock->load([
            'holdings.user',
            'holdings'=>fn($q)=>$q->orderBy('created_at','desc'),
            'transactions.user',
            'transactions'=>fn($q)=>$q->orderBy('created_at','desc'),
            'priceHistory'=>fn($q)=>$q->orderBy('timestamp','desc')->limit(30),
        ]);

        $stats = [
            'total_holdings'=>$stock->holdings->count(),
            'total_transactions'=>$stock->transactions->count(),
            'total_volume'=>$stock->transactions->sum('total_amount'),
            'total_shares'=>$stock->holdings->sum('quantity'),
            'total_value'=>$stock->holdings->sum('current_value'),
            'total_invested'=>$stock->holdings->sum('total_invested'),
            'total_gain_loss'=>$stock->holdings->sum('unrealized_gain_loss'),
        ];

        $analysis=$analysisService->forStock($stock);

        return view('admin.stocks.show',compact('stock','stats','analysis'));
    }

    public function trade(Stock $stock, StockAnalysisService $analysisService)
    {
        abort_unless($stock->is_active,404);

        $admin=auth()->user();
        $adminWallet=$admin->wallet()->firstOrCreate(
            ['user_id'=>$admin->id],
            ['balance'=>0,'reserved_balance'=>0,'currency'=>$admin->currency ?: 'USD']
        );

        $strategies=CopyStrategy::with([
                'profile.user.wallet',
                'profile.user.stockHoldings'=>fn($q)=>$q->where('stock_id',$stock->id),
            ])
            ->where('is_active',true)
            ->where('is_public',true)
            ->latest()
            ->get();

        $customers=User::query()
            ->where('is_admin',false)
            ->with([
                'wallet',
                'kyc',
                'stockHoldings'=>fn($q)=>$q->where('stock_id',$stock->id),
            ])
            ->orderBy('name')
            ->get();

        $adminHolding=$admin->stockHoldings()->where('stock_id',$stock->id)->first();
        $analysis=$analysisService->forStock($stock);

        return view('admin.stocks.trade',compact(
            'stock','analysis','strategies','customers','admin','adminWallet','adminHolding'
        ));
    }

    public function executeStrategyTrade(
        Request $request,
        Stock $stock,
        MarketTradeContractEngine $marketTrades,
        CopyTradingService $copyTrading
    ) {
        // V5.10 canonical market-contract wiring.
        $data=$request->validate([
            'strategy_id'=>'required|integer|exists:copy_strategies,id',
            'side'=>'required|in:buy,sell',
            'quantity'=>'required|numeric|min:0.000001|max:10000',
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'duration_minutes'=>'nullable|integer|min:1|max:43200',
        ]);

        $strategy=CopyStrategy::with('profile.user.wallet')->findOrFail($data['strategy_id']);

        if(! $strategy->is_active || ! $strategy->is_public){
            return back()->withErrors(['strategy_id'=>'This strategy is not currently executable.'])->withInput();
        }

        $provider=$strategy->profile?->user;
        if(! $provider || ! $provider->wallet){
            return back()->withErrors(['strategy_id'=>'The strategy provider does not have an operational wallet.'])->withInput();
        }

        $quantity=(float)$data['quantity'];

        try{
            if($data['side']==='buy'){
                $trade=$marketTrades->openLong(
                    $provider,
                    $stock,
                    $quantity,
                    'admin_strategy_trade',
                    'copy_strategy',
                    [
                        'stop_loss_percent'=>$data['stop_loss_percent']??null,
                        'take_profit_percent'=>$data['take_profit_percent']??null,
                        'duration_minutes'=>$data['duration_minutes']??null,
                    ],
                    $strategy->id,
                    $strategy->id,
                    $strategy->id,
                    'admin',
                    auth()->id()
                );
            }else{
                $position=TradePosition::where('user_id',$provider->id)->where('stock_id',$stock->id)
                    ->where('context_type','copy_strategy')->where('context_id',$strategy->id)
                    ->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)
                    ->oldest('opened_at')->firstOrFail();
                $trade=$marketTrades->closePosition($position,'strategy_manual_exit',$quantity,'admin',auth()->id());
            }
        }catch(\Throwable $e){
            return back()->withErrors(['trade'=>$e->getMessage()])->withInput();
        }

        try{$copyTrading->mirrorCompletedTrade($trade);}
        catch(\Throwable $e){\Log::warning('Strategy mirror failure',['trade_id'=>$trade->id,'error'=>$e->getMessage()]);}

        return redirect()->route('admin.stocks.transactions.show',$trade)
            ->with('success','Strategy trade executed.');
    }

    public function executeAdminTrade(
        Request $request,
        Stock $stock,
        MarketTradeContractEngine $marketTrades
    ) {
        // V5.10 canonical market-contract wiring.
        $data=$request->validate([
            'side'=>'required|in:buy,sell',
            'quantity'=>'required|numeric|min:0.000001|max:10000',
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'duration_minutes'=>'nullable|integer|min:1|max:43200',
        ]);

        $admin=auth()->user();
        $admin->wallet()->firstOrCreate(
            ['user_id'=>$admin->id],
            ['balance'=>0,'reserved_balance'=>0,'currency'=>$admin->currency ?: 'USD']
        );

        $quantity=(float)$data['quantity'];

        try{
            if($data['side']==='buy'){
                $trade=$marketTrades->openLong(
                    $admin,
                    $stock,
                    $quantity,
                    'admin_direct_trade',
                    'admin_direct',
                    [
                        'stop_loss_percent'=>$data['stop_loss_percent']??null,
                        'take_profit_percent'=>$data['take_profit_percent']??null,
                        'duration_minutes'=>$data['duration_minutes']??null,
                    ],
                    $admin->id,
                    null,
                    null,
                    'admin',
                    $admin->id
                );
            }else{
                $position=TradePosition::where('user_id',$admin->id)->where('stock_id',$stock->id)
                    ->where('context_type','admin_direct')->whereIn('status',['open','exit_queued'])
                    ->where('open_quantity','>',0)->oldest('opened_at')->firstOrFail();
                $trade=$marketTrades->closePosition($position,'admin_manual_exit',$quantity,'admin',$admin->id);
            }
        }catch(\Throwable $e){
            return back()->withErrors(['admin_trade'=>$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.stocks.transactions.show',$trade)
            ->with('success','Direct admin trade executed.');
    }

    public function executeUserTrade(
        Request $request,
        Stock $stock,
        MarketTradeContractEngine $marketTrades
    ) {
        // V5.10 canonical market-contract wiring.
        $data=$request->validate([
            'user_id'=>'required|integer|exists:users,id',
            'side'=>'required|in:buy,sell',
            'quantity'=>'required|numeric|min:0.000001|max:10000',
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'duration_minutes'=>'nullable|integer|min:1|max:43200',
            'reason'=>'required|string|max:1000',
        ]);

        $user=User::with(['wallet','kyc'])->findOrFail($data['user_id']);

        if($user->is_admin){
            return back()->withErrors(['user_id'=>'Use Direct Admin Trade for administrator accounts.'])->withInput();
        }

        if(! $user->kyc || ! $user->kyc->isApproved()){
            return back()->withErrors(['user_id'=>'Trade for User requires an approved KYC account.'])->withInput();
        }

        if(! $user->wallet){
            return back()->withErrors(['user_id'=>'The selected customer does not have an operational wallet.'])->withInput();
        }

        $quantity=(float)$data['quantity'];

        try{
            if($data['side']==='buy'){
                $trade=$marketTrades->openLong(
                    $user,
                    $stock,
                    $quantity,
                    'admin_user_trade',
                    'admin_user_trade',
                    [
                        'stop_loss_percent'=>$data['stop_loss_percent']??null,
                        'take_profit_percent'=>$data['take_profit_percent']??null,
                        'duration_minutes'=>$data['duration_minutes']??null,
                        'metadata'=>['admin_reason'=>$data['reason']],
                    ],
                    $user->id,
                    $user->id,
                    null,
                    'admin',
                    auth()->id()
                );
            }else{
                $position=TradePosition::where('user_id',$user->id)->where('stock_id',$stock->id)
                    ->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)
                    ->oldest('opened_at')->firstOrFail();
                $trade=$marketTrades->closePosition($position,'admin_user_exit',$quantity,'admin',auth()->id());
            }
        }catch(\Throwable $e){
            return back()->withErrors(['user_trade'=>$e->getMessage()])->withInput();
        }

        $activity=$user->financialActivities()->where('reference',$trade->walletTransaction?->reference_id)->latest()->first();
        if($activity){
            $meta=$activity->metadata ?: [];
            $meta['admin_reason']=$data['reason'];
            $activity->update(['metadata'=>$meta]);
        }

        return redirect()->route('admin.stocks.transactions.show',$trade)
            ->with('success','Trade for User executed.');
    }

}

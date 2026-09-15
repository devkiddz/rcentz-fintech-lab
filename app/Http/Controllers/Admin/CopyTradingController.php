<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CopyStrategy;
use App\Models\CopyTraderProfile;
use App\Models\StrategyProviderApplication;
use App\Services\NotificationService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CopyTradingController extends Controller
{
    public function applications()
    {
        $applications=StrategyProviderApplication::with(['user','reviewer'])->latest()->paginate(30);
        return view('admin.copy-trading.applications',compact('applications'));
    }

    public function approve(StrategyProviderApplication $application)
    {
        abort_unless($application->status==='pending',422,'Application is no longer pending.');

        DB::transaction(function() use($application){
            $application->update(['status'=>'approved','reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
            $profile=CopyTraderProfile::updateOrCreate(
                ['user_id'=>$application->user_id],
                [
                    'strategy_name'=>$application->display_name,
                    'bio'=>$application->strategy_summary,
                    'risk_level'=>$application->risk_level,
                    'is_public'=>true,
                    'is_accepting_copiers'=>true,
                    'approved_at'=>now(),
                    'approved_by'=>auth()->id(),
                ]
            );

            if(!$profile->strategies()->exists()){
                $profile->strategies()->create([
                    'name'=>$application->display_name,
                    'description'=>$application->strategy_summary,
                    'risk_level'=>$application->risk_level,
                    'minimum_allocation'=>100,
                    'is_public'=>true,
                    'is_active'=>true,
                ]);
            }
        });

        NotificationService::createSystemNotification($application->user,'Strategy provider approved','Your Copy Trading provider application has been approved.',['type'=>'copy_provider_approved']);
        return back()->with('success','Provider application approved.');
    }

    public function reject(Request $request, StrategyProviderApplication $application)
    {
        abort_unless($application->status==='pending',422,'Application is no longer pending.');
        $data=$request->validate(['admin_notes'=>'required|string|max:1500']);
        $application->update(['status'=>'rejected','admin_notes'=>$data['admin_notes'],'reviewed_by'=>auth()->id(),'reviewed_at'=>now()]);
        NotificationService::createSystemNotification($application->user,'Strategy provider application rejected',$data['admin_notes'],['type'=>'copy_provider_rejected']);
        return back()->with('success','Provider application rejected.');
    }

    public function providers()
    {
        $providers=CopyTraderProfile::with('user')->withCount(['strategies','activeRelationships'])->latest()->paginate(30);
        return view('admin.copy-trading.providers',compact('providers'));
    }

    public function strategies(TradingPerformanceService $performance)
    {
        $strategies=CopyStrategy::with('profile.user')->withCount('relationships')->latest()->paginate(30);
        foreach ($strategies as $strategy) { $strategy->performance_metrics = $performance->copyStrategy($strategy); }
        return view('admin.copy-trading.strategies',compact('strategies'));
    }


    public function editStrategy(CopyStrategy $strategy)
    {
        $strategy->load('profile.user');
        return view('admin.copy-trading.edit-strategy', compact('strategy'));
    }

    public function updateStrategy(Request $request, CopyStrategy $strategy)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'risk_level' => 'required|in:low,medium,high',
            'minimum_allocation' => 'required|numeric|min:50|max:1000000',
            'recommended_allocation' => 'nullable|numeric|min:50|max:1000000',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'use_manual_performance' => 'nullable|boolean',
            'manual_profit_loss' => 'nullable|numeric|min:-100000000|max:100000000',
            'manual_return_percent' => 'nullable|numeric|min:-10000|max:10000',
            'manual_performance_label' => 'nullable|string|max:60|required_if:use_manual_performance,1',
            'manual_performance_note' => 'nullable|string|max:255',
            'manual_performance_source' => 'nullable|in:profit_loss,return_percent',
        ]);

        $data['is_public'] = $request->boolean('is_public');
        $data['is_active'] = $request->boolean('is_active');
        $data['use_manual_performance'] = $request->boolean('use_manual_performance');

        if ($data['use_manual_performance']) {
            $base = (float) ($data['recommended_allocation'] ?? $data['minimum_allocation'] ?? 0);
            $source = $data['manual_performance_source'] ?? null;

            if ($base > 0 && $source === 'profit_loss' && $data['manual_profit_loss'] !== null) {
                $data['manual_return_percent'] = ((float) $data['manual_profit_loss'] / $base) * 100;
            } elseif ($base > 0 && $source === 'return_percent' && $data['manual_return_percent'] !== null) {
                $data['manual_profit_loss'] = $base * ((float) $data['manual_return_percent'] / 100);
            } elseif ($base > 0 && $data['manual_profit_loss'] !== null) {
                $data['manual_return_percent'] = ((float) $data['manual_profit_loss'] / $base) * 100;
            }

            unset($data['manual_performance_source']);

            $data['manual_performance_updated_by'] = auth()->id();
            $data['manual_performance_updated_at'] = now();
        } else {
            $data['manual_performance_updated_by'] = null;
            $data['manual_performance_updated_at'] = null;
        }

        $strategy->update($data);

        return redirect()->route('admin.copy-trading.strategies')
            ->with('success', 'Copy strategy updated.');
    }

    public function toggleStrategy(CopyStrategy $strategy)
    {
        $strategy->update(['is_active'=>!$strategy->is_active]);
        return back()->with('success','Strategy status updated.');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentInstrument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectPrivateInvestmentIntegrity extends Command
{
    protected $signature = 'investment:integrity';
    protected $description = 'Inspect private investment market foundation invariants';

    public function handle(): int
    {
        $tables = [
            'private_investment_instruments',
            'private_investment_assets',
            'private_investment_events',
            'private_investment_prices',
            'private_investment_holdings',
            'private_investment_transactions',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error('Missing table: '.$table);
                return self::FAILURE;
            }
        }

        $checks = [];
$checks['Available units outside supply bounds'] = DB::table('private_investment_instruments')
            ->where(function ($q) {
                $q->where('available_units','<',0)
                  ->orWhereColumn('available_units','>','unit_supply');
            })->count();

        $checks['Active holdings with non-positive units'] = DB::table('private_investment_holdings')
            ->where('status','active')
            ->where('units','<=',0)
            ->count();

        $checks['Transactions without holding'] = DB::table('private_investment_transactions')
            ->whereIn('type',['subscription','redemption'])
            ->whereNull('holding_id')
            ->count();

        $checks['Instruments without price history'] = DB::table('private_investment_instruments as i')
            ->leftJoin('private_investment_prices as p','p.instrument_id','=','i.id')
            ->whereNull('p.id')
            ->distinct()
            ->count('i.id');

        $checks['Assets without valid instrument'] = DB::table('private_investment_assets as a')
            ->leftJoin('private_investment_instruments as i','i.id','=','a.instrument_id')
            ->whereNull('i.id')->count();

        $checks['Events without valid instrument'] = DB::table('private_investment_events as e')
            ->leftJoin('private_investment_instruments as i','i.id','=','e.instrument_id')
            ->whereNull('i.id')->count();

        $checks['Non-positive authoritative prices'] = DB::table('private_investment_instruments')
            ->where('current_price','<=',0)->count();

        $checks['Invalid OHLC candles'] = DB::table('private_investment_prices')
            ->where(function($q){
                $q->whereColumn('high','<','low')
                  ->orWhereColumn('high','<','open')
                  ->orWhereColumn('high','<','close')
                  ->orWhereColumn('low','>','open')
                  ->orWhereColumn('low','>','close')
                  ->orWhere('close','<=',0)
                  ->orWhere('open','<=',0);
            })->count();

        $rows=[]; $failed=0;
        foreach($checks as $label=>$count){
            $count=(int)$count;
            if($count>0){$failed++;}
            $rows[]=[$label,$count,$count===0?'PASS':'VIOLATION'];
        }

        $this->table(['Invariant','Rows','Status'],$rows);

        $summary = [
            ['Instruments', PrivateInvestmentInstrument::count()],
            ['Underlying assets', DB::table('private_investment_assets')->count()],
            ['Events', DB::table('private_investment_events')->count()],
            ['OHLC price rows', DB::table('private_investment_prices')->count()],
            ['Categories', PrivateInvestmentInstrument::query()->distinct('category')->count('category')],
        ];
        $this->table(['Private investment inventory','Count'],$summary);

        $priceMismatch=0;
        foreach(PrivateInvestmentInstrument::query()->get() as $instrument){
            $latest=DB::table('private_investment_prices')
                ->where('instrument_id',$instrument->id)
                ->orderByDesc('recorded_at')
                ->first();
            if($latest && abs((float)$latest->close-(float)$instrument->current_price)>0.000001){
                $priceMismatch++;
            }
        }

        $this->line('Authoritative price mismatches: '.$priceMismatch);
        if($priceMismatch>0){$failed++;}

        if($failed>0){
            $this->error($failed.' private investment invariant(s) failed.');
            return self::FAILURE;
        }

        $this->info('Private investment foundation integrity PASS.');
        return self::SUCCESS;
    }
}

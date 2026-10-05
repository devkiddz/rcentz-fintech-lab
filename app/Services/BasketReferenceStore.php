<?php
namespace App\Services;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
final class BasketReferenceStore {
    public function ready(): bool {return Schema::hasTable('basket_market_references') && Schema::hasTable('basket_reference_observations');}
    public function ingest(int $id,string $symbol,string $asset,string $base,string $quote,float $price,string $at,string $source): bool {
        if($id<1 || !is_finite($price)||$price<=0) throw new RuntimeException('Invalid reference identity or price.');
        $time=CarbonImmutable::parse($at,'UTC');$now=now()->utc();
        if($time->timestamp>$now->timestamp+30) throw new RuntimeException('Future reference rejected.');
        if($now->timestamp-$time->timestamp>86400) return false;
        return DB::transaction(function() use($id,$symbol,$asset,$base,$quote,$price,$time,$source,$now) {
            // Lock the persistent parent to serialize first snapshot creation as well as later updates.
            $parent=DB::table('market_instruments')->where('id',$id)->lockForUpdate()->first();
            if(!$parent || strtoupper($parent->base_asset.'/'.$parent->quote_asset)!==$symbol || $parent->asset_class!==$asset) throw new RuntimeException('Reference parent identity mismatch.');
            DB::table('basket_reference_observations')->insertOrIgnore(['instrument_id'=>$id,'observed_at'=>$time->format('Y-m-d H:i:s'),'price'=>$price,'source'=>$source]);
            $old=DB::table('basket_market_references')->where('instrument_id',$id)->lockForUpdate()->first();
            if($old && ($old->symbol!==$symbol || $old->asset_class!==$asset || $old->base_asset!==$base || $old->quote_asset!==$quote)) throw new RuntimeException('Reference identity changed.');
            if($old && strcmp($time->format('Y-m-d H:i:s'),$old->observed_at)<=0) return false;
            $rows=DB::table('basket_reference_observations')->where('instrument_id',$id)
                ->where('observed_at','>=',$time->subSeconds((int)config('basket_engine.window_seconds',3600))->format('Y-m-d H:i:s'))
                ->where('observed_at','<=',$time->format('Y-m-d H:i:s'))->orderBy('observed_at')->get();
            $score=BasketMovement::score($rows->pluck('price')->all());
            $window=$rows->count()>1 ? $time->timestamp-CarbonImmutable::parse($rows->first()->observed_at,'UTC')->timestamp : 0;
            DB::table('basket_market_references')->updateOrInsert(['instrument_id'=>$id],[
                'symbol'=>$symbol,'asset_class'=>$asset,'base_asset'=>$base,'quote_asset'=>$quote,'price'=>$price,
                'observed_at'=>$time->format('Y-m-d H:i:s'),'received_at'=>$now->format('Y-m-d H:i:s'),'source'=>$source,
                'momentum_percent'=>$score['momentum'],'volatility_percent'=>$score['volatility'],'samples'=>$score['samples'],'window_seconds'=>$window]);
            DB::table('basket_reference_observations')->where('instrument_id',$id)->where('observed_at','<',$time->subDays(2)->format('Y-m-d H:i:s'))->delete();
            return true;
        });
    }
    public function importSaved(): array {
        if(!$this->ready()) return ['updated'=>0,'skipped'=>0];
        $stats=['updated'=>0,'skipped'=>0,'rejected'=>0];
        if(!Schema::hasTable('shared_live_quotes')) return $stats;
        foreach(DB::table('shared_live_quotes')->join('market_instruments','market_instruments.id','=','shared_live_quotes.instrument_id')
            ->where('market_instruments.is_active',true)->select('shared_live_quotes.*','market_instruments.asset_class','market_instruments.base_asset','market_instruments.quote_asset')->get() as $r) {
            try {
            if($r->symbol!==strtoupper($r->base_asset.'/'.$r->quote_asset)) { $stats['skipped']++;continue; }
            if(Schema::hasTable('shared_live_bars')) {
                $bars=DB::table('shared_live_bars')->where('instrument_id',$r->instrument_id)->where('interval','5m')
                    ->where('bar_at','>=',now()->utc()->subHours(2)->format('Y-m-d H:i:s'))->orderBy('bar_at')->get();
                foreach($bars as $bar) {
                    // A bar close is not known at its opening timestamp. Import only completed bars.
                    $end=CarbonImmutable::parse($bar->bar_at,'UTC')->addMinutes(5);
                    if($end->timestamp<=CarbonImmutable::parse($r->quoted_at,'UTC')->timestamp) $this->ingest($r->instrument_id,$r->symbol,$r->asset_class,$r->base_asset,$r->quote_asset,(float)$bar->close,$end->format('Y-m-d H:i:s'),'twelve_data_bar_close');
                }
            }
            $ok=$this->ingest($r->instrument_id,$r->symbol,$r->asset_class,$r->base_asset,$r->quote_asset,(float)$r->price,$r->quoted_at,'twelve_data_saved_quote');
            $stats[$ok?'updated':'skipped']++;
            } catch(\Throwable $error) {
                $stats['rejected']++;
                \Illuminate\Support\Facades\Log::warning('Basket reference rejected',['instrument_id'=>$r->instrument_id,'error_class'=>get_class($error)]);
            }
        }
        return $stats;
    }
    public function reference(int $id): ?array {
        if(!$this->ready()) return null;
        $r=DB::table('basket_market_references')->where('instrument_id',$id)->first();
        if(!$r) return null;$a=(array)$r;$a['observed_timestamp']=CarbonImmutable::parse($r->observed_at,'UTC')->timestamp;return $a;
    }
    public function usdStrength(): ?float {
        // CoinMarketCap basket USD feed: enabled feed owns freshness and warmup.
        if(config('basket_usd.enabled')) return app(BasketUsdFeed::class)->strength();
        if(!$this->ready()) return null;
        $scores=[];$now=now()->utc()->timestamp;
        foreach(DB::table('basket_market_references')->where('asset_class','forex')->get() as $r) {
            $age=$now-CarbonImmutable::parse($r->observed_at,'UTC')->timestamp;
            if($age<0 || $age>(int)config('basket_engine.max_usd_age_seconds',1800) || $r->samples<3 || $r->window_seconds<1800) continue;
            $other=$r->quote_asset==='USD'?$r->base_asset:($r->base_asset==='USD'?$r->quote_asset:null);
            if(!$other || $other==='USD') continue;
            $scores[$other]=($r->base_asset==='USD'?1:-1)*(float)$r->momentum_percent;
        }
        if(count($scores)<3) return null;
        return max(-1,min(1,array_sum($scores)/count($scores)/max(0.001,(float)config('basket_engine.momentum_scale_percent',0.25))));
    }
    public function move(int $id,float $neutral,float $open,string $quote,string $base=''): float {
        if(!config('basket_engine.enabled',false)) return $neutral;
        return BasketMovement::move($neutral,$open,$this->reference($id),$this->usdStrength(),$quote,now()->utc()->timestamp,config('basket_engine'),$base);
    }
}

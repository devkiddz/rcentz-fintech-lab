<?php
namespace App\Services;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
final class BasketUsdFeed {
 public const CURRENCIES=['EUR','GBP','JPY'];
 public function ready():bool {return Schema::hasTable('basket_usd_observations')&&Schema::hasTable('basket_usd_feed_state');}
 public function fetch(string $key):array {
  if(!preg_match('/^[a-zA-Z0-9_-]{16,128}$/D',$key)) throw new RuntimeException('Invalid local key format.');
  $out=[];
  foreach(self::CURRENCIES as $currency){
   try{$r=Http::timeout(12)->connectTimeout(5)->withHeaders(['X-CMC_PRO_API_KEY'=>$key])->get('https://pro-api.coinmarketcap.com/v2/tools/price-conversion',['amount'=>1,'id'=>'2781','convert'=>$currency]);}
   catch(\Throwable $e){throw new RuntimeException('Provider connection unavailable.');}
   $d=$r->json();
   if(!$r->successful()||!is_array($d)||(int)($d['status']['error_code']??-1)!==0)throw new RuntimeException('Provider rejected currency request.');
   $data=$d['data']??[];
   if(is_array($data)&&!isset($data['quote']))foreach($data as $item)if(is_array($item)&&(int)($item['id']??0)===2781){$data=$item;break;}
   if((int)($data['id']??0)!==2781||($data['symbol']??null)!=='USD')throw new RuntimeException('Provider source identity mismatch.');
   $quote=$data['quote'][$currency]??[];$rate=$quote['price']??null;$stamp=$quote['last_updated']??null;
   if(!is_numeric($rate)||!is_finite((float)$rate)||(float)$rate<=0||!is_string($stamp)||!preg_match('/^\d{4}-\d{2}-\d{2}T.*(?:Z|\+00:00)$/D',$stamp))throw new RuntimeException('Invalid provider rate or timestamp.');
   try{$at=CarbonImmutable::parse($stamp)->utc();}catch(\Throwable $e){throw new RuntimeException('Invalid provider timestamp.');}
   $age=CarbonImmutable::now('UTC')->timestamp-$at->timestamp;
   if($age < -30||$age>config('basket_usd.max_age_seconds',1800))throw new RuntimeException('Provider observation is stale or future dated.');
   $out[$currency]=['rate'=>(float)$rate,'observed_at'=>$at->format('Y-m-d H:i:s')];
  }
  return $out;
 }
 public function reserve():bool {
  return DB::transaction(function(){
   $s=DB::table('basket_usd_feed_state')->where('id',1)->lockForUpdate()->first();if(!$s)throw new RuntimeException('Feed state missing.');
   $now=CarbonImmutable::now('UTC');
   if($s->last_attempt_at&&$now->timestamp-CarbonImmutable::parse($s->last_attempt_at,'UTC')->timestamp < config('basket_usd.minimum_interval_seconds',840))return false;
   $day=$s->utc_day===$now->format('Y-m-d')?(int)$s->daily_reserved:0;
   $month=$s->utc_month===$now->format('Y-m')?(int)$s->monthly_reserved:0;
   if($day+3>config('basket_usd.daily_limit',360)||$month+3>config('basket_usd.monthly_limit',12000))return false;
   DB::table('basket_usd_feed_state')->where('id',1)->update(['utc_day'=>$now->format('Y-m-d'),'utc_month'=>$now->format('Y-m'),'daily_reserved'=>$day+3,'monthly_reserved'=>$month+3,'last_attempt_at'=>$now->format('Y-m-d H:i:s'),'last_status'=>'attempting']);return true;
  });
 }
 public function persist(array $rates):void {
  DB::transaction(function()use($rates){
   DB::table('basket_usd_feed_state')->where('id',1)->lockForUpdate()->first();
   foreach(self::CURRENCIES as $c){if(!isset($rates[$c]))throw new RuntimeException('Incomplete currency batch.');$r=$rates[$c];
    $last=DB::table('basket_usd_observations')->where('currency',$c)->max('observed_at');
    if($last&&$r['observed_at']<$last)throw new RuntimeException('Provider observation moved backwards.');
   }
   foreach(self::CURRENCIES as $c)DB::table('basket_usd_observations')->insertOrIgnore(['currency'=>$c,'rate'=>$rates[$c]['rate'],'observed_at'=>$rates[$c]['observed_at'],'received_at'=>CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),'source'=>'coinmarketcap_usd_conversion']);
   DB::table('basket_usd_feed_state')->where('id',1)->update(['last_success_at'=>CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),'last_status'=>'saved']);
   DB::table('basket_usd_observations')->where('observed_at','<',CarbonImmutable::now('UTC')->subDays(3)->format('Y-m-d H:i:s'))->delete();
  });
 }
 public function refresh(?string $key=null):string {
  if(!$this->ready())return 'schema_missing';
  if(!$this->reserve())return 'budget_or_cooldown';
  try{$rates=$this->fetch($key??(string)config('basket_usd.key'));$this->persist($rates);return 'saved';}
  catch(\Throwable $e){DB::table('basket_usd_feed_state')->where('id',1)->update(['last_status'=>'provider_unavailable']);return 'provider_unavailable';}
 }
 public function strength():?float {
  if(!config('basket_usd.enabled')||!$this->ready())return null;
  $now=CarbonImmutable::now('UTC')->timestamp;$sets=[];$latest=[];
  foreach(self::CURRENCIES as $c){
   $rows=DB::table('basket_usd_observations')->where('currency',$c)->where('observed_at','>=',gmdate('Y-m-d H:i:s',$now-5400))->orderBy('observed_at')->get();
   if($rows->count()<3)return null;
   $sets[$c]=$rows;$last=$rows->last();$t=CarbonImmutable::parse($last->observed_at,'UTC')->timestamp;
   if($now-$t< -30||$now-$t>=config('basket_usd.max_age_seconds',1800))return null;$latest[]=$t;
  }
  // Use the same end cutoff for all counterparts; no fresh/stale mixture.
  $end=min($latest);$start=max(array_merge([$end-config('basket_usd.window_seconds',3600)],array_values(array_map(fn($rows)=>CarbonImmutable::parse($rows->first()->observed_at,'UTC')->timestamp,$sets))));
  if($end-$start<config('basket_usd.minimum_window_seconds',1800))return null;
  $scores=[];
  foreach($sets as $rows){
   $at=function(int $target)use($rows):?float {
    $before=null;foreach($rows as $r){$t=CarbonImmutable::parse($r->observed_at,'UTC')->timestamp;
     if($t===$target)return (float)$r->rate;
     if($t>$target){if(!$before)return null;$bt=CarbonImmutable::parse($before->observed_at,'UTC')->timestamp;if($t-$bt>1200)return null;return exp(log((float)$before->rate)+(log((float)$r->rate)-log((float)$before->rate))*($target-$bt)/($t-$bt));}
     $before=$r;
    }return null;
   };
   $first=$at($start);$last=$at($end);if(!$first||!$last)return null;
   $scores[]=log($last/$first)*100*3600/($end-$start);
  }
  $strength=max(-1,min(1,array_sum($scores)/3/max(0.001,(float)config('basket_usd.scale_percent',0.25))));
  return $strength*max(0,1-($now-$end)/max(1,config('basket_usd.max_age_seconds',1800)));
 }
 public function inspect():array {
  $latest=[];foreach(self::CURRENCIES as $c)$latest[$c]=DB::table('basket_usd_observations')->where('currency',$c)->orderByDesc('observed_at')->first(['rate','observed_at','received_at']);
  return ['enabled'=>(bool)config('basket_usd.enabled'),'usd_strength'=>$this->strength(),'latest'=>$latest,'budget_state'=>DB::table('basket_usd_feed_state')->where('id',1)->first(),'warmup'=>'Three observations per currency spanning at least 30 minutes; every currency must be fresh.','provider_calls'=>0];
 }
}

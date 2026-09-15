<?php

$path = __DIR__.'/../app/Services/CopyTradingService.php';

if (! file_exists($path)) {
    throw new RuntimeException('CopyTradingService.php not found.');
}

$text = file_get_contents($path);

$newMirrorMethod = <<<'PHP'
 public function mirrorCompletedTrade(StockTransaction $providerTrade): void {
  if($providerTrade->status!=='completed')return;

  $query=CopyRelationship::with(['follower.kyc','provider.copyTraderProfile','strategy'])
   ->where('provider_id',$providerTrade->user_id)
   ->where('status','active')
   ->where(function($q){$q->whereNull('ends_at')->orWhere('ends_at','>',now());})
   ->whereHas('strategy',fn($q)=>$q->where('is_active',true)->where('is_public',true));

  if($providerTrade->copy_strategy_id){
   $query->where('copy_strategy_id',$providerTrade->copy_strategy_id);
  } else {
   Log::warning('Legacy provider trade has no strategy attribution; provider-wide mirroring remains enabled for this transaction.',[
    'provider_trade_id'=>$providerTrade->id,
    'provider_id'=>$providerTrade->user_id,
   ]);
  }

  $relationships=$query->get();
  foreach($relationships as $r){$this->mirrorOne($r,$providerTrade);}
 }
PHP;

$pattern = '/ public function mirrorCompletedTrade\(StockTransaction \$providerTrade\): void \{.*?\n \}\n private function mirrorOne/s';

if (! preg_match($pattern, $text)) {
    throw new RuntimeException('Could not locate mirrorCompletedTrade() in CopyTradingService.');
}

$text = preg_replace(
    $pattern,
    $newMirrorMethod."\n private function mirrorOne",
    $text,
    1,
    $count
);

if ($count !== 1) {
    throw new RuntimeException("Unexpected mirrorCompletedTrade replacement count: {$count}");
}

$old = "$trade=$providerTrade->type==='buy'?$this->executor->buy($f,$providerTrade->stock,$qty,'copy_trade',$r->id):$this->executor->sell($f,$providerTrade->stock,$qty,'copy_trade',$r->id);";
$new = "$trade=$providerTrade->type==='buy'?$this->executor->buy($f,$providerTrade->stock,$qty,'copy_trade',$r->id,$r->copy_strategy_id):$this->executor->sell($f,$providerTrade->stock,$qty,'copy_trade',$r->id,$r->copy_strategy_id);";

if (! str_contains($text, $old) && ! str_contains($text, $new)) {
    throw new RuntimeException('Could not locate Copy Trading executor call.');
}

$text = str_replace($old, $new, $text);

file_put_contents($path, $text);

echo "Copy Trading now supports exact strategy attribution for attributed provider trades.\n";

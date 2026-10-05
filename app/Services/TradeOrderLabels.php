<?php
namespace App\Services;
final class TradeOrderLabels {
 public static function realizedReturn(object $order): ?array {
  $execution=$order->execution??null;
  $intent=$execution->metadata['order_intent']??$order->metadata['order_intent']??null;
  if($intent!=='close' || !$execution || ($order->status??null)!=='filled' || ($execution->status??null)!=='completed') return null;
  $value=$execution->realized_profit_loss??null;
  if(!is_numeric($value)||!is_finite((float)$value)) return null;
  $pnl=(float)$value;
  $released=$execution->metadata['collateral_released_minor']??null;
  $collateral=is_numeric($released)?(float)$released/100:(float)($execution->settlement_amount??0);
  $percent=is_finite($collateral)&&$collateral>0?$pnl/$collateral*100:null;
  $currency=strtoupper((string)($execution->settlement_currency??$order->settlement_currency??'USD'));
  $currency=preg_match('/^[A-Z]{3,8}$/D',$currency)?$currency:'USD';
  return ['amount'=>$pnl,'percent'=>$percent,'positive'=>$pnl>=0,
   'formatted'=>$currency.' '.($pnl>0?'+':'').number_format($pnl,2),
   'formatted_percent'=>$percent!==null?($percent>0?'+':'').number_format($percent,2).'%':null];
 }
 public static function forOrder(object $order): array {
  $execution=$order->execution??null;$metadata=$execution->metadata??[];$orderMeta=$order->metadata??[];
  $position=$execution->tradePosition??null;
  $owned=$position && (int)$position->user_id===(int)$order->user_id;
  $intent=$metadata['order_intent']??$orderMeta['order_intent']??null;
  $direction=$metadata['direction']??($owned?$position->direction:null);
  if(!in_array($direction,['long','short'],true)) $direction=null;
  if(!$direction && $intent==='open') $direction=$order->side==='buy'?'long':($order->side==='sell'?'short':null);
  $side=strtoupper((string)$order->side);
  $primary=$direction==='long'?'BUY':($direction==='short'?'SELL':$side);
  $action=in_array($intent,['open','close'],true) ? ucfirst($intent).($direction?' '.ucfirst($direction):' Position') : 'Order';
  $reason=null;
  // A final position reason belongs only to its final exit, never earlier partial closes.
  if($intent==='close' && $owned && $execution && (int)($position->last_exit_market_execution_transaction_id??0)===(int)$execution->id) $reason=$position->exit_reason??null;
  $reason=match($reason){'take_profit'=>'Take Profit','stop_loss'=>'Stop Loss','customer_close'=>'Manual Close','time_expiry'=>'Time Expiry','margin_exhausted'=>'Margin Exhausted',default=>null};
  return ['primary'=>$primary,'direction'=>$direction?ucfirst($direction):null,'action'=>$action,'execution_side'=>$side,'reason'=>$reason];
 }
}

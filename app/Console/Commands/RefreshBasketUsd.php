<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\BasketUsdFeed;
final class RefreshBasketUsd extends Command {
 protected $signature='basket-usd:refresh';
 protected $description='Save three timestamped USD conversion observations for basket strength';
 public function handle(BasketUsdFeed $feed):int {
  if(!config('basket_usd.enabled')){$this->line('USD feed disabled.');return self::SUCCESS;}
  $status=$feed->refresh();$this->line(json_encode(['usd_feed'=>$status]));return self::SUCCESS;
 }
}

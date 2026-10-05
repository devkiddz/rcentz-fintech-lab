<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {
  Schema::create('basket_usd_observations',function(Blueprint $t){$t->id();$t->string('currency',3);$t->decimal('rate',30,12);$t->dateTime('observed_at');$t->dateTime('received_at');$t->string('source',40);$t->unique(['currency','observed_at']);});
  Schema::create('basket_usd_feed_state',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->string('utc_day',10)->nullable();$t->string('utc_month',7)->nullable();$t->unsignedInteger('daily_reserved')->default(0);$t->unsignedInteger('monthly_reserved')->default(0);$t->dateTime('last_attempt_at')->nullable();$t->dateTime('last_success_at')->nullable();$t->string('last_status',40)->default('not_started');});
  DB::table('basket_usd_feed_state')->insert(['id'=>1]);
 }
 public function down():void {Schema::dropIfExists('basket_usd_observations');Schema::dropIfExists('basket_usd_feed_state');}
};

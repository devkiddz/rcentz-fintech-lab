<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_subscriptions', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('cancelled_at');
        });

        DB::table('bot_products')
            ->where('billing_period', 'one_time')
            ->update(['billing_period' => 'monthly']);

        DB::table('bot_subscriptions')
            ->whereIn('status', ['active','paused'])
            ->whereNull('ends_at')
            ->orderBy('id')
            ->get(['id','starts_at','created_at'])
            ->each(function ($subscription) {
                $start = Carbon::parse($subscription->starts_at ?: $subscription->created_at ?: now());
                DB::table('bot_subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['ends_at' => $start->copy()->addMonth()]);
            });
    }

    public function down(): void
    {
        Schema::table('bot_subscriptions', function (Blueprint $table) {
            $table->dropColumn('expired_at');
        });
    }
};

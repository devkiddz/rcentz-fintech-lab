<?php

use App\Services\ReleaseBaselineInstaller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_production_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_production_demo')
                    ->default(false)
                    ->index()
                    ->after('is_admin');
            });
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereIn('email', [
                    ReleaseBaselineInstaller::RESERVED_AMARA_EMAIL,
                    ReleaseBaselineInstaller::RESERVED_DANIEL_EMAIL,
                    ReleaseBaselineInstaller::RESERVED_SOFIA_EMAIL,
                ])->orWhere(function ($query) {
                    $query->whereIn('name', [
                        'Amara Okafor',
                        'Daniel Brooks',
                        'Sofia Martinez',
                    ])->where('email', 'like', '%.test');
                });
            })
            ->where('is_admin', false)
            ->update(['is_production_demo' => true]);

        $demoIds = DB::table('users')
            ->where('is_production_demo', true)
            ->where('is_admin', false)
            ->pluck('id');

        if ($demoIds->isEmpty()) {
            return;
        }

        // Production demo accounts must never keep autonomous mutation paths.
        if (
            Schema::hasTable('automatic_investment_plans')
            && Schema::hasColumn('automatic_investment_plans', 'is_active')
        ) {
            $update = ['is_active' => false];

            if (Schema::hasColumn('automatic_investment_plans', 'next_investment_date')) {
                $update['next_investment_date'] = null;
            }

            DB::table('automatic_investment_plans')
                ->whereIn('user_id', $demoIds)
                ->update($update);
        }

        if (
            Schema::hasTable('stock_trade_plans')
            && Schema::hasColumn('stock_trade_plans', 'status')
        ) {
            DB::table('stock_trade_plans')
                ->whereIn('user_id', $demoIds)
                ->whereIn('status', ['active', 'due'])
                ->update([
                    'status' => 'cancelled',
                    'failure_reason' => 'Protected production demo account: autonomous financial mutation disabled.',
                ]);
        }

        if (
            Schema::hasTable('trading_bots')
            && Schema::hasColumn('trading_bots', 'status')
        ) {
            DB::table('trading_bots')
                ->whereIn('user_id', $demoIds)
                ->where('status', 'active')
                ->update(['status' => 'paused']);
        }

        if (
            Schema::hasTable('bot_subscriptions')
            && Schema::hasColumn('bot_subscriptions', 'status')
        ) {
            DB::table('bot_subscriptions')
                ->whereIn('user_id', $demoIds)
                ->where('status', 'active')
                ->update(['status' => 'paused']);
        }

        if (
            Schema::hasTable('copy_relationships')
            && Schema::hasColumn('copy_relationships', 'status')
        ) {
            DB::table('copy_relationships')
                ->where(function ($query) use ($demoIds) {
                    $query->whereIn('follower_id', $demoIds)
                        ->orWhereIn('provider_id', $demoIds);
                })
                ->whereNotIn('status', ['completed', 'stopped'])
                ->update(['status' => 'paused']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_production_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_production_demo');
            });
        }
    }
};

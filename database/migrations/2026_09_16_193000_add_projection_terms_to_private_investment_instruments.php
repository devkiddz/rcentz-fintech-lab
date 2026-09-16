<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_investment_instruments', function (Blueprint $table) {
            if (! Schema::hasColumn('private_investment_instruments','duration_days')) {
                $table->unsignedInteger('duration_days')->default(90)->after('lock_period_days');
            }
            if (! Schema::hasColumn('private_investment_instruments','return_interval_days')) {
                $table->unsignedInteger('return_interval_days')->default(30)->after('duration_days');
            }
            if (! Schema::hasColumn('private_investment_instruments','projected_return_min_percent')) {
                $table->decimal('projected_return_min_percent',8,4)->default(0)->after('return_interval_days');
            }
            if (! Schema::hasColumn('private_investment_instruments','projected_return_max_percent')) {
                $table->decimal('projected_return_max_percent',8,4)->default(0)->after('projected_return_min_percent');
            }
            if (! Schema::hasColumn('private_investment_instruments','subscription_fee_percent')) {
                $table->decimal('subscription_fee_percent',8,4)->default(0)->after('projected_return_max_percent');
            }
            if (! Schema::hasColumn('private_investment_instruments','redemption_fee_percent')) {
                $table->decimal('redemption_fee_percent',8,4)->default(0)->after('subscription_fee_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('private_investment_instruments', function (Blueprint $table) {
            foreach ([
                'duration_days','return_interval_days','projected_return_min_percent',
                'projected_return_max_percent','subscription_fee_percent','redemption_fee_percent'
            ] as $column) {
                if (Schema::hasColumn('private_investment_instruments',$column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

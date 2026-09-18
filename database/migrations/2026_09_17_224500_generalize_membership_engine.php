<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('membership_types')) {
            Schema::create('membership_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon', 64)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        $existingVipType = DB::table('membership_types')->where('slug', 'vip')->first();

        if ($existingVipType) {
            DB::table('membership_types')->where('id', $existingVipType->id)->update([
                'name' => 'VIP Membership',
                'description' => 'Premium membership access and entitlements.',
                'icon' => 'crown',
                'is_active' => true,
                'sort_order' => 10,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('membership_types')->insert([
                'name' => 'VIP Membership',
                'slug' => 'vip',
                'description' => 'Premium membership access and entitlements.',
                'icon' => 'crown',
                'is_active' => true,
                'sort_order' => 10,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vipTypeId = DB::table('membership_types')->where('slug', 'vip')->value('id');

        if (! $vipTypeId) {
            throw new \RuntimeException('VIP membership type could not be established.');
        }

        $this->renameTableIfNeeded('vip_plans', 'membership_plans');
        $this->renameTableIfNeeded('vip_entitlements', 'membership_entitlements');
        $this->renameTableIfNeeded('vip_memberships', 'memberships');

        if (! Schema::hasTable('membership_plans') || ! Schema::hasTable('membership_entitlements') || ! Schema::hasTable('memberships')) {
            throw new \RuntimeException('Membership table generalization could not resolve the legacy VIP tables.');
        }

        $this->renameColumnIfNeeded('membership_entitlements', 'vip_plan_id', 'membership_plan_id');
        $this->renameColumnIfNeeded('memberships', 'vip_plan_id', 'membership_plan_id');

        if (! Schema::hasColumn('membership_plans', 'membership_type_id')) {
            Schema::table('membership_plans', function (Blueprint $table) {
                $table->unsignedBigInteger('membership_type_id')->nullable()->after('id');
            });
        }

        DB::table('membership_plans')
            ->whereNull('membership_type_id')
            ->update(['membership_type_id' => $vipTypeId]);

        if (DB::table('membership_plans')->whereNull('membership_type_id')->exists()) {
            throw new \RuntimeException('One or more membership plans could not be assigned to a membership type.');
        }

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('membership_type_id')->nullable(false)->change();
        });

        if (! $this->foreignKeyExists('membership_plans', 'membership_type_id')) {
            Schema::table('membership_plans', function (Blueprint $table) {
                $table->foreign('membership_type_id')
                    ->references('id')
                    ->on('membership_types')
                    ->restrictOnDelete();
            });
        }

        if (DB::table('membership_plans')->where('membership_type_id', $vipTypeId)->count() !== DB::table('membership_plans')->count()) {
            throw new \RuntimeException('Legacy VIP plans were not fully attached to the VIP membership type.');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('membership_types')) {
            return;
        }

        $vipTypeId = DB::table('membership_types')->where('slug', 'vip')->value('id');

        if (! $vipTypeId) {
            throw new \RuntimeException('Cannot roll back membership generalization because the VIP type is missing.');
        }

        if (DB::table('membership_types')->where('id', '!=', $vipTypeId)->exists()) {
            throw new \RuntimeException('Cannot roll back membership generalization after additional membership types have been created.');
        }

        if (Schema::hasTable('membership_plans') && DB::table('membership_plans')->where('membership_type_id', '!=', $vipTypeId)->exists()) {
            throw new \RuntimeException('Cannot roll back membership generalization while non-VIP plans exist.');
        }

        if (Schema::hasTable('membership_plans') && Schema::hasColumn('membership_plans', 'membership_type_id')) {
            if ($this->foreignKeyExists('membership_plans', 'membership_type_id')) {
                Schema::table('membership_plans', function (Blueprint $table) {
                    $table->dropForeign(['membership_type_id']);
                });
            }

            Schema::table('membership_plans', function (Blueprint $table) {
                $table->dropColumn('membership_type_id');
            });
        }

        $this->renameColumnIfNeeded('membership_entitlements', 'membership_plan_id', 'vip_plan_id');
        $this->renameColumnIfNeeded('memberships', 'membership_plan_id', 'vip_plan_id');

        $this->renameTableIfNeeded('memberships', 'vip_memberships');
        $this->renameTableIfNeeded('membership_entitlements', 'vip_entitlements');
        $this->renameTableIfNeeded('membership_plans', 'vip_plans');

        Schema::dropIfExists('membership_types');
    }

    private function renameTableIfNeeded(string $from, string $to): void
    {
        if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
            Schema::rename($from, $to);
            return;
        }

        if (Schema::hasTable($from) && Schema::hasTable($to)) {
            throw new \RuntimeException("Both {$from} and {$to} exist; refusing an ambiguous membership migration.");
        }
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
                $blueprint->renameColumn($from, $to);
            });
            return;
        }

        if (Schema::hasColumn($table, $from) && Schema::hasColumn($table, $to)) {
            throw new \RuntimeException("Both {$from} and {$to} exist on {$table}; refusing an ambiguous membership migration.");
        }
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        try {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $columns = $foreignKey['columns'] ?? $foreignKey['foreign_columns'] ?? [];
                if (in_array($column, $columns, true)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // If the driver cannot enumerate foreign keys, let Laravel attempt
            // to create/drop the expected constraint normally.
        }

        return false;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('completed');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });

        // The original status enum did not include "rejected" even though the
        // admin controller uses that state. Expand it while we are touching the
        // wallet workflow. This project targets MySQL in local/cPanel installs.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY status ENUM('pending','processing','completed','failed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_transfers');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY status ENUM('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};

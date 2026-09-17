<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->text('withdrawal_purpose')->nullable()->after('description');
            $table->text('withdrawal_review_note')->nullable()->after('withdrawal_purpose');
            $table->string('withdrawal_token_hash')->nullable()->after('withdrawal_review_note');
            $table->string('withdrawal_token_last_four', 4)->nullable()->after('withdrawal_token_hash');
            $table->timestamp('withdrawal_token_generated_at')->nullable()->after('withdrawal_token_last_four');
            $table->timestamp('withdrawal_token_expires_at')->nullable()->after('withdrawal_token_generated_at');
            $table->timestamp('withdrawal_token_verified_at')->nullable()->after('withdrawal_token_expires_at');
            $table->foreignId('withdrawal_reviewed_by_user_id')->nullable()->after('withdrawal_token_verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawal_reviewed_at')->nullable()->after('withdrawal_reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_reviewed_by_user_id');
            $table->dropColumn([
                'withdrawal_purpose','withdrawal_review_note','withdrawal_token_hash','withdrawal_token_last_four',
                'withdrawal_token_generated_at','withdrawal_token_expires_at','withdrawal_token_verified_at','withdrawal_reviewed_at',
            ]);
        });
    }
};

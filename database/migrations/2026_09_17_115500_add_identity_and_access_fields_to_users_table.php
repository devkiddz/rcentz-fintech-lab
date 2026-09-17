<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('currency');
            $table->string('employment_class', 50)->nullable()->after('date_of_birth');
            $table->string('education_level', 50)->nullable()->after('employment_class');
            $table->string('account_status', 20)->default('active')->index()->after('education_level');
            $table->text('status_reason')->nullable()->after('account_status');
            $table->timestamp('status_until')->nullable()->after('status_reason');
            $table->timestamp('status_changed_at')->nullable()->after('status_until');
            $table->foreignId('status_changed_by_user_id')->nullable()->after('status_changed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_changed_by_user_id');
            $table->dropColumn([
                'date_of_birth','employment_class','education_level','account_status',
                'status_reason','status_until','status_changed_at',
            ]);
        });
    }
};

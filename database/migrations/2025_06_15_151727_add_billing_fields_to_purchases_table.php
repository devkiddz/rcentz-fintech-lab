<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Billing Information - making nullable for existing records
            $table->string('billing_name')->nullable()->after('amount');
            $table->string('billing_email')->nullable()->after('billing_name');
            $table->string('billing_phone')->nullable()->after('billing_email');
            $table->text('billing_address')->nullable()->after('billing_phone');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code')->nullable()->after('billing_state');
            $table->string('billing_country')->nullable()->after('billing_postal_code');
            
            // Additional billing fields
            $table->string('company_name')->nullable()->after('billing_country');
            $table->string('tax_id')->nullable()->after('company_name'); // VAT/Tax ID for businesses
            
            // Transaction details for crypto payments
            $table->string('transaction_hash')->nullable()->after('tax_id'); // Blockchain transaction hash
            $table->decimal('crypto_amount', 20, 8)->nullable()->after('transaction_hash'); // Amount in crypto
            $table->string('crypto_currency')->nullable()->after('crypto_amount'); // BTC, ETH, etc.
            $table->decimal('exchange_rate', 15, 8)->nullable()->after('crypto_currency'); // USD to crypto rate at time of purchase
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'billing_name',
                'billing_email',
                'billing_phone',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'company_name',
                'tax_id',
                'transaction_hash',
                'crypto_amount',
                'crypto_currency',
                'exchange_rate'
            ]);
        });
    }
};

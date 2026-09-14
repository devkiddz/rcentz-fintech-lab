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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->enum('type', ['traditional', 'cryptocurrency'])->default('traditional')->after('name');
            $table->string('logo')->nullable()->after('details'); // Path to logo image
            $table->text('barcode')->nullable()->after('logo'); // QR code or barcode data
            $table->string('wallet_address')->nullable()->after('barcode'); // Crypto wallet address
            $table->string('crypto_symbol')->nullable()->after('wallet_address'); // BTC, ETH, etc.
            $table->decimal('network_fee', 10, 8)->nullable()->after('crypto_symbol'); // Network transaction fee
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'logo',
                'barcode',
                'wallet_address',
                'crypto_symbol',
                'network_fee'
            ]);
        });
    }
};

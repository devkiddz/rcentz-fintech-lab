<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            // Traditional Payment Methods
            [
                'name' => 'Credit Card',
                'type' => 'traditional',
                'details' => 'Pay securely with your credit card (Visa, Mastercard, American Express). Instant processing with secure encryption.',
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => false,
            ],
            [
                'name' => 'Bank Transfer',
                'type' => 'traditional',
                'details' => 'Direct bank transfer with 2-3 business days processing time. No additional fees.',
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'PayPal',
                'type' => 'traditional',
                'details' => 'Pay with your PayPal account for instant processing. Buyer protection included.',
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Tesla Financing',
                'type' => 'traditional',
                'details' => 'Exclusive financing options with competitive rates. Pre-approval available.',
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => false,
            ],

            // Cryptocurrency Payment Methods
            [
                'name' => 'Bitcoin',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Bitcoin (BTC). Secure blockchain transactions with global acceptance.',
                'crypto_symbol' => 'BTC',
                'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'network_fee' => 0.00005000,
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Ethereum',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Ethereum (ETH). Fast and secure smart contract transactions.',
                'crypto_symbol' => 'ETH',
                'wallet_address' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
                'network_fee' => 0.00200000,
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Litecoin',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Litecoin (LTC). Faster transactions with lower fees than Bitcoin.',
                'crypto_symbol' => 'LTC',
                'wallet_address' => 'LTC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4',
                'network_fee' => 0.00100000,
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Cardano',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Cardano (ADA). Sustainable and energy-efficient blockchain payments.',
                'crypto_symbol' => 'ADA',
                'wallet_address' => 'addr1qx2fxv2umyhttkxyxp8x0dlpdt3k6cwng5pxj3jhsydzer3jcu5d8ps7zex2k2xt3uqxgjqnnj0vs2qd4a6gtmk4l3zcjqzda8xx',
                'network_fee' => 0.17000000,
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Polkadot',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Polkadot (DOT). Interoperable blockchain technology for secure payments.',
                'crypto_symbol' => 'DOT',
                'wallet_address' => '15oF4uVJwmo4TdGW7VfQxNLavjCXviqxT9S1MgbjMNHr6Sp5',
                'network_fee' => 0.01000000,
                'is_active' => true,
                'allow_deposit' => true,
                'allow_withdraw' => true,
            ],
            [
                'name' => 'Binance Coin',
                'type' => 'cryptocurrency',
                'details' => 'Pay with Binance Coin (BNB). Low fees and fast transactions on Binance Smart Chain.',
                'crypto_symbol' => 'BNB',
                'wallet_address' => '0x8ba1f109551bD432803012645Hac136c22C501e',
                'network_fee' => 0.00050000,
                'is_active' => false, // Disabled by default
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }

        $this->command->info('Payment methods seeded successfully!');
        $this->command->info('Traditional methods: 4');
        $this->command->info('Cryptocurrency methods: 6 (5 active, 1 inactive)');
    }
}

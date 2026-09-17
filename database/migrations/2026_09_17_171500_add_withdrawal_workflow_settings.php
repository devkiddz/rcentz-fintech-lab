<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key' => 'withdrawal_help_title',
                'value' => 'Withdrawal process',
                'type' => 'text',
                'group' => 'security',
                'label' => 'Withdrawal help title',
                'description' => 'Customer-facing heading shown beside the withdrawal request flow.',
                'is_public' => false,
            ],
            [
                'key' => 'withdrawal_step_1',
                'value' => 'Submit request|Enter the amount you want to withdraw.',
                'type' => 'textarea',
                'group' => 'security',
                'label' => 'Withdrawal step 1',
                'description' => 'Use Title|Description.',
                'is_public' => false,
            ],
            [
                'key' => 'withdrawal_step_2',
                'value' => 'Security review|Your request is reviewed before payout details are requested.',
                'type' => 'textarea',
                'group' => 'security',
                'label' => 'Withdrawal step 2',
                'description' => 'Use Title|Description.',
                'is_public' => false,
            ],
            [
                'key' => 'withdrawal_step_3',
                'value' => 'Verify withdrawal|Use the verification code sent to your account.',
                'type' => 'textarea',
                'group' => 'security',
                'label' => 'Withdrawal step 3',
                'description' => 'Use Title|Description.',
                'is_public' => false,
            ],
            [
                'key' => 'withdrawal_step_4',
                'value' => 'Payout processing|Track the request until it is approved or declined.',
                'type' => 'textarea',
                'group' => 'security',
                'label' => 'Withdrawal step 4',
                'description' => 'Use Title|Description.',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        Setting::clearCache();
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            'withdrawal_help_title',
            'withdrawal_step_1',
            'withdrawal_step_2',
            'withdrawal_step_3',
            'withdrawal_step_4',
        ])->delete();

        Setting::clearCache();
    }
};

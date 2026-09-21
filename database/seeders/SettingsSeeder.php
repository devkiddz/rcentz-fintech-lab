<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $productName = config('app.name', 'Financial Platform');

        $settings = [
            [
                'key' => 'site_name',
                'value' => $productName,
                'type' => 'text',
                'group' => 'general',
                'label' => 'Product Name',
                'description' => 'Public name of this platform.',
                'is_public' => true,
            ],
            [
                'key' => 'company_name',
                'value' => $productName,
                'type' => 'text',
                'group' => 'general',
                'label' => 'Company Name',
                'description' => 'Company or operator presented to customers.',
                'is_public' => true,
            ],
            [
                'key' => 'legal_company_name',
                'value' => $productName,
                'type' => 'text',
                'group' => 'general',
                'label' => 'Legal Company Name',
                'description' => 'Legal entity name used in company and footer copy.',
                'is_public' => true,
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Markets, intelligence and financial control.',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Tagline',
                'description' => 'Short positioning line used across public brand surfaces.',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => 'A modern financial platform for markets, portfolio management, intelligent signals, automation and private investments.',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Company Description',
                'description' => 'Primary public description of the platform and company offering.',
                'is_public' => true,
            ],
            [
                'key' => 'site_url',
                'value' => config('app.url'),
                'type' => 'text',
                'group' => 'general',
                'label' => 'Primary Website URL',
                'description' => 'Canonical public URL for this installation.',
                'is_public' => true,
            ],
            [
                'key' => 'site_email',
                'value' => config('mail.from.address', 'hello@example.com'),
                'type' => 'text',
                'group' => 'general',
                'label' => 'Support Email',
                'description' => 'Primary public support or company contact email.',
                'is_public' => true,
            ],
            [
                'key' => 'site_phone',
                'value' => '',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Support Phone',
                'description' => 'Primary public support or company contact number.',
                'is_public' => true,
            ],
            [
                'key' => 'site_keywords',
                'value' => 'financial markets, stocks, forex, crypto, investments, portfolio, signals, trading automation',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Site Keywords',
                'description' => 'SEO keywords for public company pages.',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'checkbox',
                'group' => 'general',
                'label' => 'Maintenance Mode',
                'description' => 'Put the public application into maintenance mode.',
                'is_public' => false,
            ],
            [
                'key' => 'brand_primary_color',
                'value' => '#c8102e',
                'type' => 'color',
                'group' => 'appearance',
                'label' => 'Primary Brand Color',
                'description' => 'Primary accent used on the public website, calls to action and selected brand surfaces.',
                'is_public' => true,
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Legacy Logo',
                'description' => 'Compatibility logo used when dedicated light/dark assets are not configured.',
                'is_public' => true,
            ],
            [
                'key' => 'site_logo_light',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Logo for Light Surfaces',
                'description' => 'Primary logo shown on light backgrounds.',
                'is_public' => true,
            ],
            [
                'key' => 'site_logo_dark',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Logo for Dark Surfaces',
                'description' => 'Primary logo shown on dark backgrounds.',
                'is_public' => true,
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Favicon',
                'description' => 'Browser icon for this platform.',
                'is_public' => true,
            ],
            [
                'key' => 'footer_text',
                'value' => '© '.date('Y').' '.$productName.'. All rights reserved.',
                'type' => 'textarea',
                'group' => 'appearance',
                'label' => 'Footer Text',
                'description' => 'Legal footer copy shown on public pages.',
                'is_public' => true,
            ],
            [
                'key' => 'developer_credit_enabled',
                'value' => '0',
                'type' => 'checkbox',
                'group' => 'appearance',
                'label' => 'Show Developer Credit',
                'description' => 'Optional developer attribution. Disabled by default.',
                'is_public' => false,
            ],
            [
                'key' => 'developer_name',
                'value' => '',
                'type' => 'text',
                'group' => 'appearance',
                'label' => 'Developer Name',
                'description' => 'Displayed only when developer credit is enabled.',
                'is_public' => false,
            ],
            [
                'key' => 'developer_url',
                'value' => '',
                'type' => 'text',
                'group' => 'appearance',
                'label' => 'Developer URL',
                'description' => 'Optional attribution URL when developer credit is enabled.',
                'is_public' => false,
            ],
            [
                'key' => 'enable_kyc',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'security',
                'label' => 'Enable KYC Verification',
                'description' => 'Require customers to complete KYC verification.',
                'is_public' => false,
            ],
            [
                'key' => 'enable_email_verification',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'security',
                'label' => 'Enable Email Verification',
                'description' => 'Require customers to verify their email address.',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            $existing = DB::table('settings')->where('key', $setting['key'])->first();
            $now = now();

            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'value' => $existing ? $existing->value : $setting['value'],
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}

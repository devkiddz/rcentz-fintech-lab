<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => config('app.name', 'Tesla Investment Platform'),
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Name',
                'description' => 'The name of your website',
                'is_public' => true,
            ],
            [
                'key' => 'site_url',
                'value' => config('app.url'),
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site URL',
                'description' => 'The main URL of your website',
                'is_public' => true,
            ],
            [
                'key' => 'site_email',
                'value' => config('bootstrap.admin.email') ?: 'admin@example.com',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Email',
                'description' => 'Primary contact email for the website',
                'is_public' => true,
            ],
            [
                'key' => 'site_phone',
                'value' => '',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Phone',
                'description' => 'Primary contact phone number',
                'is_public' => true,
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Site Logo',
                'description' => 'Upload your site logo (recommended: 200x60px)',
                'is_public' => true,
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'appearance',
                'label' => 'Site Favicon',
                'description' => 'Upload your site favicon (SVG, PNG, ICO supported)',
                'is_public' => true,
            ],
            [
                'key' => 'enable_kyc',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'security',
                'label' => 'Enable KYC Verification',
                'description' => 'Require users to complete KYC verification',
                'is_public' => false,
            ],
            [
                'key' => 'enable_email_verification',
                'value' => '1',
                'type' => 'checkbox',
                'group' => 'security',
                'label' => 'Enable Email Verification',
                'description' => 'Require users to verify their email address',
                'is_public' => false,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'checkbox',
                'group' => 'general',
                'label' => 'Maintenance Mode',
                'description' => 'Put the site in maintenance mode',
                'is_public' => false,
            ],
            [
                'key' => 'site_description',
                'value' => 'Your trusted platform for investments and financial growth',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Site Description',
                'description' => 'Brief description of your website',
                'is_public' => true,
            ],
            [
                'key' => 'site_keywords',
                'value' => 'investment, stocks, crypto, trading, finance',
                'type' => 'textarea',
                'group' => 'general',
                'label' => 'Site Keywords',
                'description' => 'SEO keywords for your website',
                'is_public' => true,
            ],
            [
                'key' => 'footer_text',
                'value' => '© '.date('Y').' '.config('app.name', 'Tesla Investment Platform').'. All rights reserved.',
                'type' => 'textarea',
                'group' => 'appearance',
                'label' => 'Footer Text',
                'description' => 'Text displayed in the footer',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
                ])
            );
        }
    }
}

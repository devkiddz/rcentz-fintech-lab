<?php

return [
    'groups' => [
        'core' => [
            'label' => 'Core',
            'sections' => ['overview', 'general', 'appearance', 'localization'],
        ],
        'markets' => [
            'label' => 'Markets & Trading',
            'sections' => ['market', 'trading'],
        ],
        'platform' => [
            'label' => 'Platform',
            'sections' => ['security', 'mail', 'integrations'],
        ],
        'operations' => [
            'label' => 'Operations',
            'sections' => ['system'],
        ],
        'reserved' => [
            'label' => 'Reserved',
            'sections' => ['bots', 'copy_trading', 'verification', 'approved_accounts', 'audit'],
        ],
    ],

    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'description' => 'Platform configuration health and shortcuts.',
            'icon' => 'layout-dashboard',
            'route' => 'admin.settings.index',
            'section' => 'overview',
            'available' => true,
        ],
        'general' => [
            'label' => 'General',
            'description' => 'Site identity, contact information and application defaults.',
            'icon' => 'settings-2',
            'route' => 'admin.settings.index',
            'section' => 'general',
            'available' => true,
        ],
        'appearance' => [
            'label' => 'Appearance',
            'description' => 'Logo, favicon and public presentation assets.',
            'icon' => 'palette',
            'route' => 'admin.settings.index',
            'section' => 'appearance',
            'available' => true,
        ],
        'localization' => [
            'label' => 'Localization',
            'description' => 'Languages, default locale, RTL direction and translation coverage.',
            'icon' => 'languages',
            'route' => 'admin.settings.index',
            'section' => 'localization',
            'available' => true,
        ],
        'market' => [
            'label' => 'Market',
            'description' => 'Price source, market direction, strength and movement interval.',
            'icon' => 'chart-candlestick',
            'route' => 'admin.settings.index',
            'section' => 'market',
            'available' => true,
        ],
        'trading' => [
            'label' => 'Trading',
            'description' => 'Trading control surfaces and settings boundaries.',
            'icon' => 'chart-no-axes-combined',
            'route' => 'admin.settings.index',
            'section' => 'trading',
            'available' => true,
        ],
        'security' => [
            'label' => 'Security',
            'description' => 'KYC and account verification controls currently available.',
            'icon' => 'shield-check',
            'route' => 'admin.settings.index',
            'section' => 'security',
            'available' => true,
        ],
        'mail' => [
            'label' => 'Mail & Notifications',
            'description' => 'SMTP transport, sender identity, credentials and delivery testing.',
            'icon' => 'mail',
            'route' => 'admin.settings.index',
            'section' => 'mail',
            'available' => true,
        ],
        'integrations' => [
            'label' => 'Integrations',
            'description' => 'External market, payment and communication providers.',
            'icon' => 'plug-zap',
            'route' => 'admin.settings.index',
            'section' => 'integrations',
            'available' => true,
        ],
        'system' => [
            'label' => 'System',
            'description' => 'Cache, storage and application maintenance controls.',
            'icon' => 'server-cog',
            'route' => 'admin.settings.index',
            'section' => 'system',
            'available' => true,
        ],

        // Reserved settings domains. They intentionally have no routes yet.
        // Enabling one later means adding its route/controller and changing
        // available + route here; the navigation and overview are already ready.
        'bots' => [
            'label' => 'Bot Automation',
            'description' => 'Global bot execution, approval and risk policy.',
            'icon' => 'bot',
            'route' => null,
            'available' => false,
        ],
        'copy_trading' => [
            'label' => 'Copy Trading',
            'description' => 'Provider, follower and replication policy settings.',
            'icon' => 'users-round',
            'route' => null,
            'available' => false,
        ],
        'verification' => [
            'label' => 'Verification & Approvals',
            'description' => 'Expanded verification workflows and approval policy.',
            'icon' => 'badge-check',
            'route' => null,
            'available' => false,
        ],
        'approved_accounts' => [
            'label' => 'Approved Trading Accounts',
            'description' => 'Trading-account eligibility, approval and restriction policy.',
            'icon' => 'user-round-check',
            'route' => null,
            'available' => false,
        ],
        'audit' => [
            'label' => 'Audit & Compliance',
            'description' => 'Account audit rules, review controls and compliance settings.',
            'icon' => 'scroll-text',
            'route' => null,
            'available' => false,
        ],
    ],
];

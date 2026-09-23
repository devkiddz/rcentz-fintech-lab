<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bootstrap Accounts
    |--------------------------------------------------------------------------
    |
    | These values are used only by AdminUserSeeder. Production credentials
    | must be supplied through the environment and are never committed.
    |
    */
    'admin' => [
        'name' => env('BOOTSTRAP_ADMIN_NAME', 'Platform Administrator'),
        'email' => env('BOOTSTRAP_ADMIN_EMAIL'),
        'password' => env('BOOTSTRAP_ADMIN_PASSWORD'),
    ],

    'demo_user' => [
        'enabled' => env('BOOTSTRAP_DEMO_USER', false),
        'name' => env('BOOTSTRAP_DEMO_NAME', 'Demo User'),
        'email' => env('BOOTSTRAP_DEMO_EMAIL'),
        'password' => env('BOOTSTRAP_DEMO_PASSWORD'),
    ],

    'installation_demo' => [
        // Set at runtime by the browser installer. An environment value is
        // supported only for intentional command-line demonstration seeding.
        'password' => env('INSTALLATION_DEMO_PASSWORD'),
    ],

    'live_test' => [
        'admin_name' => env('LIVE_TEST_ADMIN_NAME', 'Tesla Drives Test Admin'),
        'admin_email' => env('LIVE_TEST_ADMIN_EMAIL', 'admin@tesladrives.test'),
        'admin_password' => env('LIVE_TEST_ADMIN_PASSWORD', 'TeslaAdmin@2026!'),
        'user_password' => env('LIVE_TEST_USER_PASSWORD', 'TestUser@2026!'),
    ],
];

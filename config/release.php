<?php

return [
    'production_demo' => [
        /*
         * Production demonstration accounts are deliberately read-only by
         * default. Set PRODUCTION_DEMO_READ_ONLY=false only in a controlled
         * non-production environment when mutation testing is intentional.
         */
        'read_only' => env('PRODUCTION_DEMO_READ_ONLY', true),
    ],
];

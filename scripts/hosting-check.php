<?php

/**
 * Run from CLI before deployment:
 *   php scripts/hosting-check.php
 */

$requiredExtensions = [
    'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl',
    'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml',
];

$recommendedExtensions = ['bcmath', 'intl', 'zip'];
$minimumPhp = '8.2.0';
$ok = true;

function result(string $label, bool $passed, string $detail = ''): void
{
    $status = $passed ? '[OK]  ' : '[FAIL]';
    echo sprintf("%-7s %-30s %s\n", $status, $label, $detail);
}

result('PHP version', version_compare(PHP_VERSION, $minimumPhp, '>='), PHP_VERSION.' (requires >= '.$minimumPhp.')');
if (version_compare(PHP_VERSION, $minimumPhp, '<')) {
    $ok = false;
}

foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    result('ext-'.$extension, $loaded);
    $ok = $ok && $loaded;
}

foreach ($recommendedExtensions as $extension) {
    result('ext-'.$extension.' (recommended)', extension_loaded($extension));
}

foreach (['storage', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $directory) {
    $exists = is_dir(__DIR__.'/../'.$directory);
    $writable = $exists && is_writable(__DIR__.'/../'.$directory);
    result($directory.' writable', $writable, $exists ? '' : 'directory missing');
    $ok = $ok && $writable;
}

$envExists = file_exists(__DIR__.'/../.env');
result('.env exists', $envExists, $envExists ? '' : 'copy .env.example to .env on the server');

$buildExists = file_exists(__DIR__.'/../public/build/manifest.json');
result('Vite production build', $buildExists, $buildExists ? 'public/build/manifest.json found' : 'run npm run build before deployment');
$ok = $ok && $buildExists;

echo "\n".($ok ? 'Hosting baseline passed.' : 'Hosting baseline has required failures. Fix FAIL items before testing the application.')."\n";
exit($ok ? 0 : 1);

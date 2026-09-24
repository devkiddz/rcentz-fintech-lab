<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__);
$installLock = $basePath.'/storage/app/installed';
$envPath = $basePath.'/.env';
$envExamplePath = $basePath.'/.env.example';

/*
|--------------------------------------------------------------------------
| Fresh-install bootstrap
|--------------------------------------------------------------------------
|
| Laravel requires APP_KEY before the browser installer can safely use the
| normal web middleware stack. On an uninstalled copy only, create .env from
| .env.example when necessary and generate a unique application key.
|
| Installed applications never pass through this bootstrap authority.
|
*/
if (! file_exists($installLock)) {
    if (! file_exists($envPath)) {
        if (! file_exists($envExamplePath) || ! @copy($envExamplePath, $envPath)) {
            http_response_code(500);
            exit('Installation bootstrap could not create the environment file. Check application directory permissions.');
        }
    }

    $envContents = @file_get_contents($envPath);

    if ($envContents === false) {
        http_response_code(500);
        exit('Installation bootstrap could not read the environment file.');
    }

    preg_match(
        '/^APP_KEY[ \t]*=[ \t]*([^\r\n]*)$/m',
        $envContents,
        $keyMatch
    );

    $currentKey = isset($keyMatch[1])
        ? trim($keyMatch[1], " \t\n\r\0\x0B\"'")
        : '';

    if ($currentKey === '') {
        $currentKey = 'base64:'.base64_encode(random_bytes(32));

        if (preg_match('/^APP_KEY[ \t]*=[^\r\n]*$/m', $envContents)) {
            $envContents = preg_replace(
                '/^APP_KEY[ \t]*=[^\r\n]*$/m',
                'APP_KEY='.$currentKey,
                $envContents,
                1
            );
        } else {
            $envContents = rtrim($envContents).PHP_EOL.'APP_KEY='.$currentKey.PHP_EOL;
        }

        if (@file_put_contents($envPath, $envContents, LOCK_EX) === false) {
            http_response_code(500);
            exit('Installation bootstrap could not write the application key. Check environment-file permissions.');
        }
    }

    // Make the key available to Laravel during this same first request.
    putenv('APP_KEY='.$currentKey);
    $_ENV['APP_KEY'] = $currentKey;
    $_SERVER['APP_KEY'] = $currentKey;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
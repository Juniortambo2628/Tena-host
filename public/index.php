<?php

/**
 * Tena.host Production Entry Point
 * 
 * Target Location: /home/zhpebukm/tena.host/api/index.php
 * Points to the backend core located at: /home/zhpebukm/tena-core/
 */

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = '/home/zhpebukm/tena-core/storage/framework/maintenance.php')) {
    require $maintenance;
} elseif (file_exists($maintenance = __DIR__.'/../../tena-core/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
if (file_exists('/home/zhpebukm/tena-core/vendor/autoload.php')) {
    require '/home/zhpebukm/tena-core/vendor/autoload.php';
} else {
    require __DIR__.'/../../tena-core/vendor/autoload.php';
}

// Bootstrap Laravel
if (file_exists('/home/zhpebukm/tena-core/bootstrap/app.php')) {
    $app = require_once '/home/zhpebukm/tena-core/bootstrap/app.php';
} else {
    $app = require_once __DIR__.'/../../tena-core/bootstrap/app.php';
}

$app->handleRequest(\Illuminate\Http\Request::capture());

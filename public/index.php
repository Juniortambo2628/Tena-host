<?php

use Illuminate\Http\Request;

/**
 * Tena.host entry point.
 *
 * On the VPS (OKJT deploy-kit) the whole app, including public/, lives in
 * private/tenafi/current/, so the standard relative paths resolve.
 * On the old cPanel layout this file is copied to ~/tena.host/ and the core
 * lives in ~/tena-core/ — those absolute paths are the fallback below.
 */

define('LARAVEL_START', microtime(true));

$vendor = __DIR__.'/../vendor/autoload.php';
$bootstrap = __DIR__.'/../bootstrap/app.php';
$maintenance = __DIR__.'/../storage/framework/maintenance.php';

// Old cPanel layout fallback (~/tena.host/index.php -> ~/tena-core/)
$cpanelCore = '/home/zhpebukm/tena-core';
if (! file_exists($vendor) && file_exists($cpanelCore.'/vendor/autoload.php')) {
    $vendor = $cpanelCore.'/vendor/autoload.php';
    $bootstrap = $cpanelCore.'/bootstrap/app.php';
    $maintenance = $cpanelCore.'/storage/framework/maintenance.php';
}

// Maintenance mode check
if (file_exists($maintenance)) {
    require $maintenance;
}

// Register Composer autoloader
require $vendor;

// Bootstrap Laravel
$app = require_once $bootstrap;

$app->handleRequest(Request::capture());

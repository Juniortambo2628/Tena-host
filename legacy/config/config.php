<?php

/**
 * Tena Waitlist System Configuration
 * Handles environment detection, paths, and system settings
 */

// Environment Detection
function detectEnvironment()
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

    // Development environments
    if (strpos($host, 'localhost') !== false ||
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, 'dev') !== false ||
        strpos($host, 'test') !== false) {
        return 'development';
    }

    // Staging environments
    // Explicit mapping for known staging hosts
    if (strpos($host, 'stayawhile-rentals.com') !== false || strpos($host, 'okjtech.co.ke') !== false || strpos($host, '51.89.113.223') !== false) {
        return 'staging';
    }
    if (strpos($host, 'staging') !== false ||
        strpos($host, 'stage') !== false) {
        return 'staging';
    }

    // Production environment
    return 'production';
}

// Set environment
define('ENVIRONMENT', detectEnvironment());

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH.'/config');
define('AUTH_PATH', BASE_PATH.'/auth');
define('ADMIN_PATH', BASE_PATH.'/admin');
define('ASSETS_PATH', BASE_PATH.'/assets');
define('CSS_PATH', BASE_PATH.'/css');
define('JS_PATH', BASE_PATH.'/js');
define('DATA_PATH', BASE_PATH.'/data');
define('VENDOR_PATH', BASE_PATH.'/vendor');

// URL paths
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = $protocol.'://'.$host.rtrim($scriptName, '/');

define('BASE_URL', $baseUrl);
define('AUTH_URL', BASE_URL.'/auth');
define('ADMIN_URL', BASE_URL.'/admin');
define('ASSETS_URL', BASE_URL.'/assets');
define('CSS_URL', BASE_URL.'/css');
define('JS_URL', BASE_URL.'/js');

// Database configuration based on environment
$dbConfig = [
    'development' => [
        'host' => 'localhost',
        'dbname' => 'tena_waitlist',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'staging' => [
        'host' => 'localhost',
        'dbname' => 'zhpebukm_tena_waitlist',
        'username' => 'zhpebukm_dev',
        'password' => 'Tenahost_Dev',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'production' => [
        'host' => 'localhost',
        'dbname' => 'tena_waitlist_prod',
        'username' => 'prod_user',
        'password' => 'prod_password',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];

define('DB_CONFIG', $dbConfig[ENVIRONMENT]);

// Application settings
define('APP_NAME', 'Tena Waitlist System');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', ENVIRONMENT === 'development');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Pagination settings
define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE', 100);

// Export settings
define('MAX_EXPORT_RECORDS', 10000);
define('EXPORT_TIMEOUT', 300); // 5 minutes

// Notification settings
define('NOTIFICATION_TTL', 86400); // 24 hours
define('MAX_NOTIFICATIONS', 50);

// Mailer settings (optional)
define('MAILER_FROM', getenv('MAILER_FROM') ?: 'no-reply@localhost');
define('MAILER_FROM_NAME', getenv('MAILER_FROM_NAME') ?: 'Tena');
define('MAILER_SMTP_HOST', getenv('MAILER_SMTP_HOST') ?: '');
define('MAILER_SMTP_USER', getenv('MAILER_SMTP_USER') ?: '');
define('MAILER_SMTP_PASS', getenv('MAILER_SMTP_PASS') ?: '');
define('MAILER_SMTP_PORT', getenv('MAILER_SMTP_PORT') ?: '');
define('MAILER_SMTP_SECURE', getenv('MAILER_SMTP_SECURE') ?: '');

// Staging host SMTP defaults (stayawhile-rentals.com and okjtech staging)
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'stayawhile-rentals.com') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'okjtech.co.ke') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '51.89.113.223') !== false) {
    if (empty(MAILER_SMTP_HOST)) {
        define('MAILER_SMTP_HOST', 'p3plzcpnl508402.prod.phx3.secureserver.net');
    }
    if (empty(MAILER_SMTP_PORT)) {
        define('MAILER_SMTP_PORT', 465);
    }
    if (empty(MAILER_SMTP_USER)) {
        define('MAILER_SMTP_USER', 'tenahost_dev@stayawhile-rentals.com');
    }
    if (empty(MAILER_SMTP_PASS)) {
        define('MAILER_SMTP_PASS', 'Tenahost_Dev');
    }
    if (empty(MAILER_FROM)) {
        define('MAILER_FROM', 'tenahost_dev@stayawhile-rentals.com');
    }
    if (empty(MAILER_FROM_NAME)) {
        define('MAILER_FROM_NAME', 'Tena Staging');
    }
    if (empty(MAILER_SMTP_SECURE)) {
        define('MAILER_SMTP_SECURE', 'ssl');
    }
}

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv', 'xlsx', 'pdf']);

// Cache settings
define('CACHE_ENABLED', ENVIRONMENT === 'production');
define('CACHE_TTL', 300); // 5 minutes

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH.'/logs/error.log');
}

// Timezone
date_default_timezone_set('UTC');

// Session configuration (only if session not already started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);
}

// Autoloader for classes
spl_autoload_register(function ($class) {
    $classFile = BASE_PATH.'/classes/'.str_replace('\\', '/', $class).'.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Helper function to get config value
function config($key, $default = null)
{
    $keys = explode('.', $key);
    $value = $GLOBALS;

    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }

    return $value;
}

// Helper function to get environment
function isDevelopment()
{
    return ENVIRONMENT === 'development';
}

function isProduction()
{
    return ENVIRONMENT === 'production';
}

function isStaging()
{
    return ENVIRONMENT === 'staging';
}

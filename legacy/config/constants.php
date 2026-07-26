<?php

/**
 * Tena Waitlist System Constants
 * Centralized constants and reusable functions
 */

// Include configuration
require_once __DIR__.'/config.php';

// HTTP Status Codes
if (! defined('HTTP_OK')) {
    define('HTTP_OK', 200);
}
if (! defined('HTTP_CREATED')) {
    define('HTTP_CREATED', 201);
}
if (! defined('HTTP_BAD_REQUEST')) {
    define('HTTP_BAD_REQUEST', 400);
}
if (! defined('HTTP_UNAUTHORIZED')) {
    define('HTTP_UNAUTHORIZED', 401);
}
if (! defined('HTTP_FORBIDDEN')) {
    define('HTTP_FORBIDDEN', 403);
}
if (! defined('HTTP_NOT_FOUND')) {
    define('HTTP_NOT_FOUND', 404);
}
if (! defined('HTTP_METHOD_NOT_ALLOWED')) {
    define('HTTP_METHOD_NOT_ALLOWED', 405);
}
if (! defined('HTTP_INTERNAL_SERVER_ERROR')) {
    define('HTTP_INTERNAL_SERVER_ERROR', 500);
}

// Response Types
define('RESPONSE_JSON', 'application/json');
define('RESPONSE_HTML', 'text/html');
define('RESPONSE_CSV', 'text/csv');
define('RESPONSE_PDF', 'application/pdf');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Registration Status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_CONVERTED', 'converted');

// Property Types
define('PROPERTY_VACATION_RENTAL', 'vacation_rental');
define('PROPERTY_HOTEL', 'hotel');
define('PROPERTY_BB', 'b&b');
define('PROPERTY_OTHER', 'other');

// Notification Types
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_ERROR', 'error');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_INFO', 'info');

// Notification Categories
define('NOTIF_CAT_SYSTEM', 'system');
define('NOTIF_CAT_USER', 'user');
define('NOTIF_CAT_REGISTRATION', 'registration');
define('NOTIF_CAT_EXPORT', 'export');

// Export Formats
define('EXPORT_CSV', 'csv');
define('EXPORT_PDF', 'pdf');
define('EXPORT_EXCEL', 'xlsx');

// Sort Directions
if (! defined('SORT_ASC')) {
    define('SORT_ASC', 'ASC');
}
if (! defined('SORT_DESC')) {
    define('SORT_DESC', 'DESC');
}

// Common Sort Fields
define('SORT_ID', 'id');
define('SORT_NAME', 'first_name');
define('SORT_EMAIL', 'email');
define('SORT_CREATED', 'created_at');
define('SORT_UPDATED', 'updated_at');

// Pagination
define('PAGE_FIRST', 1);
define('PAGE_DEFAULT', 25);

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M j, Y');
define('DISPLAY_DATETIME_FORMAT', 'M j, Y g:i A');

// File Extensions
define('EXT_CSV', '.csv');
define('EXT_PDF', '.pdf');
define('EXT_EXCEL', '.xlsx');

// Common Messages
define('MSG_LOGIN_SUCCESS', 'Login successful');
define('MSG_LOGIN_FAILED', 'Invalid username or password');
define('MSG_LOGOUT_SUCCESS', 'Logged out successfully');
define('MSG_REGISTRATION_SUCCESS', 'Registration successful');
define('MSG_REGISTRATION_FAILED', 'Registration failed');
define('MSG_UPDATE_SUCCESS', 'Update successful');
define('MSG_DELETE_SUCCESS', 'Delete successful');
define('MSG_EXPORT_SUCCESS', 'Export completed');
define('MSG_EXPORT_FAILED', 'Export failed');
define('MSG_ACCESS_DENIED', 'Access denied');
define('MSG_INVALID_REQUEST', 'Invalid request');
define('MSG_DATABASE_ERROR', 'Database error occurred');
define('MSG_FILE_NOT_FOUND', 'File not found');
define('MSG_VALIDATION_ERROR', 'Validation error');

// Error Codes
define('ERR_VALIDATION', 'VALIDATION_ERROR');
define('ERR_DATABASE', 'DATABASE_ERROR');
define('ERR_AUTH', 'AUTHENTICATION_ERROR');
define('ERR_PERMISSION', 'PERMISSION_ERROR');
define('ERR_NOT_FOUND', 'NOT_FOUND');
define('ERR_EXPORT', 'EXPORT_ERROR');

// Common Functions
class Common
{
    /**
     * Send JSON response
     */
    public static function jsonResponse($data, $status = HTTP_OK, $message = '')
    {
        http_response_code($status);
        header('Content-Type: '.RESPONSE_JSON);

        $response = [
            'success' => $status < 400,
            'message' => $message,
            'data' => $data,
            'timestamp' => date(DATETIME_FORMAT),
        ];

        echo json_encode($response);
        exit;
    }

    /**
     * Send error response
     */
    public static function errorResponse($message, $status = HTTP_BAD_REQUEST, $code = null)
    {
        self::jsonResponse(null, $status, $message);
    }

    /**
     * Send success response
     */
    public static function successResponse($data = null, $message = 'Success')
    {
        self::jsonResponse($data, HTTP_OK, $message);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }

        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate random string
     */
    public static function generateRandomString($length = 32)
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } else {
            // Fallback for older PHP versions
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }

            return $randomString;
        }
    }

    /**
     * Format date for display
     */
    public static function formatDate($date, $format = DISPLAY_DATE_FORMAT)
    {
        if (empty($date)) {
            return 'N/A';
        }

        return date($format, strtotime($date));
    }

    /**
     * Format datetime for display
     */
    public static function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT)
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        return date($format, strtotime($datetime));
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get client IP address
     */
    public static function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Log activity
     */
    public static function logActivity($action, $details = '', $userId = null)
    {
        $logFile = DATA_PATH.'/activity.log';
        $timestamp = date(DATETIME_FORMAT);
        $ip = self::getClientIP();
        $user = $userId ?? ($_SESSION['user_id'] ?? 'anonymous');

        $logEntry = "[$timestamp] User: $user | Action: $action | IP: $ip | Details: $details".PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Redirect with message
     */
    public static function redirect($url, $message = '', $type = NOTIFICATION_INFO)
    {
        if (! empty($message)) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header("Location: $url");
        exit;
    }

    /**
     * Get flash message
     */
    public static function getFlashMessage()
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? NOTIFICATION_INFO;
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);

            return ['message' => $message, 'type' => $type];
        }

        return null;
    }

    /**
     * Pagination helper
     */
    public static function paginate($total, $page, $perPage)
    {
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'total_records' => $total,
            'offset' => $offset,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
        ];
    }

    /**
     * Build query string
     */
    public static function buildQueryString($params, $exclude = [])
    {
        foreach ($exclude as $key) {
            unset($params[$key]);
        }

        return http_build_query(array_filter($params));
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Validate property type
     */
    public static function validatePropertyType($type)
    {
        $validTypes = [PROPERTY_VACATION_RENTAL, PROPERTY_HOTEL, PROPERTY_BB, PROPERTY_OTHER];

        return in_array($type, $validTypes) ? $type : PROPERTY_OTHER;
    }

    /**
     * Validate status
     */
    public static function validateStatus($status)
    {
        $validStatuses = [STATUS_ACTIVE, STATUS_INACTIVE, STATUS_CONVERTED];

        return in_array($status, $validStatuses) ? $status : STATUS_ACTIVE;
    }

    /**
     * Get property type display name
     */
    public static function getPropertyTypeDisplay($type)
    {
        $types = [
            PROPERTY_VACATION_RENTAL => 'Vacation Rental',
            PROPERTY_HOTEL => 'Hotel',
            PROPERTY_BB => 'Bed & Breakfast',
            PROPERTY_OTHER => 'Other',
        ];

        return $types[$type] ?? 'Unknown';
    }

    /**
     * Get status display name
     */
    public static function getStatusDisplay($status)
    {
        $statuses = [
            STATUS_ACTIVE => 'Active',
            STATUS_INACTIVE => 'Inactive',
            STATUS_CONVERTED => 'Converted',
        ];

        return $statuses[$status] ?? 'Unknown';
    }

    /**
     * Get status CSS class
     */
    public static function getStatusClass($status)
    {
        $classes = [
            STATUS_ACTIVE => 'bg-success',
            STATUS_INACTIVE => 'bg-warning',
            STATUS_CONVERTED => 'bg-primary',
        ];

        return $classes[$status] ?? 'bg-secondary';
    }
}

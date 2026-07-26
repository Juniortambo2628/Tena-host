<?php

/**
 * AJAX Handler for Real-time Updates
 * Handles all AJAX requests for the Tena system
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

// Set JSON header
header('Content-Type: '.RESPONSE_JSON);

// Check if request is AJAX
if (! Common::isAjax()) {
    Common::errorResponse('Invalid request method', HTTP_METHOD_NOT_ALLOWED);
}

// Check authentication more carefully
$currentUser = null;
try {
    require_once '../auth/check_auth.php';
    $currentUser = getCurrentUser();
} catch (Exception $e) {
    // If authentication fails, return error instead of redirecting
    Common::errorResponse('Authentication required', HTTP_UNAUTHORIZED);
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $database = new Database;
    $db = $database->getConnection();
    $notificationManager = new \Tena\NotificationManager($db);

    switch ($action) {
        case 'get_notifications':
            handleGetNotifications($notificationManager, $currentUser);
            break;

        case 'mark_notification_read':
            handleMarkNotificationRead($notificationManager, $currentUser);
            break;

        case 'mark_all_read':
            handleMarkAllRead($notificationManager, $currentUser);
            break;

        case 'get_stats':
            handleGetStats($db, $currentUser);
            break;

        case 'get_registrations':
            handleGetRegistrations($db, $currentUser);
            break;

        case 'update_registration':
            handleUpdateRegistration($db, $currentUser);
            break;

        case 'export_data':
            handleExportData($db, $currentUser);
            break;

        case 'get_analytics':
            handleGetAnalytics($db, $currentUser);
            break;

        case 'realtime_updates':
            handleRealtimeUpdates($notificationManager, $currentUser);
            break;

        default:
            Common::errorResponse('Invalid action', HTTP_BAD_REQUEST);
    }

} catch (Exception $e) {
    Common::logActivity('ajax_error', $e->getMessage());
    Common::errorResponse('Server error occurred', HTTP_INTERNAL_SERVER_ERROR);
}

/**
 * Handle get notifications request
 */
function handleGetNotifications($notificationManager, $user)
{
    // Check both POST and GET parameters
    $limit = (int) ($_POST['limit'] ?? $_GET['limit'] ?? 20);
    $unreadOnly = filter_var($_POST['unread_only'] ?? $_GET['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $notifications = $notificationManager->getForUser($user['id'], $limit, $unreadOnly);
    $unreadCount = $notificationManager->getUnreadCount($user['id']);

    Common::successResponse([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}

/**
 * Handle mark notification as read
 */
function handleMarkNotificationRead($notificationManager, $user)
{
    $notificationId = (int) ($_POST['notification_id'] ?? 0);

    if (! $notificationId) {
        Common::errorResponse('Notification ID required');
    }

    if ($notificationManager->markAsRead($notificationId, $user['id'])) {
        Common::successResponse(['notification_id' => $notificationId]);
    } else {
        Common::errorResponse('Failed to mark notification as read');
    }
}

/**
 * Handle mark all notifications as read
 */
function handleMarkAllRead($notificationManager, $user)
{
    if ($notificationManager->markAllAsRead($user['id'])) {
        Common::successResponse();
    } else {
        Common::errorResponse('Failed to mark all notifications as read');
    }
}

/**
 * Handle get statistics
 */
function handleGetStats($db, $user)
{
    $query = "SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_registrations,
                COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals,
                COUNT(CASE WHEN property_type = 'hotel' THEN 1 END) as hotels,
                COUNT(CASE WHEN property_type = 'b&b' THEN 1 END) as bnb,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_registrations,
                COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted_registrations
              FROM registrations";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch();

    Common::successResponse($stats);
}

/**
 * Handle get registrations with filters
 */
function handleGetRegistrations($db, $user)
{
    $filters = [
        'search' => $_GET['search'] ?? '',
        'property_type' => $_GET['property_type'] ?? '',
        'status' => $_GET['status'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'sort_by' => $_GET['sort_by'] ?? 'created_at',
        'sort_order' => $_GET['sort_order'] ?? 'DESC',
        'page' => (int) ($_GET['page'] ?? 1),
        'per_page' => (int) ($_GET['per_page'] ?? 25),
    ];

    // Build where conditions
    $whereConditions = [];
    $params = [];

    if (! empty($filters['search'])) {
        $whereConditions[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
        $params[':search'] = '%'.$filters['search'].'%';
    }

    if (! empty($filters['property_type'])) {
        $whereConditions[] = 'property_type = :property_type';
        $params[':property_type'] = $filters['property_type'];
    }

    if (! empty($filters['status'])) {
        $whereConditions[] = 'status = :status';
        $params[':status'] = $filters['status'];
    }

    if (! empty($filters['date_from'])) {
        $whereConditions[] = 'DATE(created_at) >= :date_from';
        $params[':date_from'] = $filters['date_from'];
    }

    if (! empty($filters['date_to'])) {
        $whereConditions[] = 'DATE(created_at) <= :date_to';
        $params[':date_to'] = $filters['date_to'];
    }

    $whereClause = ! empty($whereConditions) ? 'WHERE '.implode(' AND ', $whereConditions) : '';

    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM registrations $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];

    // Get paginated results
    $pagination = Common::paginate($totalRecords, $filters['page'], $filters['per_page']);
    $query = "SELECT * FROM registrations $whereClause 
              ORDER BY {$filters['sort_by']} {$filters['sort_order']} 
              LIMIT :offset, :per_page";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $pagination['per_page'], PDO::PARAM_INT);
    $stmt->execute();
    $registrations = $stmt->fetchAll();

    Common::successResponse([
        'registrations' => $registrations,
        'pagination' => $pagination,
        'filters' => $filters,
    ]);
}

/**
 * Handle update registration
 */
function handleUpdateRegistration($db, $user)
{
    $registrationId = (int) ($_POST['registration_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    if (! $registrationId || ! $field) {
        Common::errorResponse('Registration ID and field required');
    }

    $allowedFields = ['status', 'property_type', 'first_name', 'last_name', 'email', 'phone', 'location'];
    if (! in_array($field, $allowedFields)) {
        Common::errorResponse('Invalid field');
    }

    $query = "UPDATE registrations SET $field = :value, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':value', $value);
    $stmt->bindParam(':id', $registrationId);

    if ($stmt->execute()) {
        Common::logActivity('registration_updated', "Updated $field for registration $registrationId");
        Common::successResponse(['registration_id' => $registrationId, 'field' => $field, 'value' => $value]);
    } else {
        Common::errorResponse('Failed to update registration');
    }
}

/**
 * Handle export data
 */
function handleExportData($db, $user)
{
    $format = $_POST['format'] ?? 'csv';
    $filters = json_decode($_POST['filters'] ?? '{}', true);

    // This would trigger the export process
    // For now, just return success
    Common::successResponse([
        'export_id' => uniqid(),
        'format' => $format,
        'status' => 'processing',
    ]);
}

/**
 * Handle get analytics data
 */
function handleGetAnalytics($db, $user)
{
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    // Get trends data
    $trendsQuery = 'SELECT 
                      DATE(created_at) as date,
                      COUNT(*) as registrations
                    FROM registrations 
                    WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC';

    $stmt = $db->prepare($trendsQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    $stmt->execute();
    $trends = $stmt->fetchAll();

    // Get property type distribution
    $propertyQuery = 'SELECT 
                       property_type,
                       COUNT(*) as count
                     FROM registrations 
                     WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                     GROUP BY property_type';

    $stmt = $db->prepare($propertyQuery);
    $stmt->bindParam(':date_from', $dateFrom);
    $stmt->bindParam(':date_to', $dateTo);
    $stmt->execute();
    $propertyTypes = $stmt->fetchAll();

    Common::successResponse([
        'trends' => $trends,
        'property_types' => $propertyTypes,
        'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
    ]);
}

/**
 * Handle real-time updates
 */
function handleRealtimeUpdates($notificationManager, $user)
{
    $lastCheck = (int) ($_GET['last_check'] ?? 0);

    $notifications = $notificationManager->getRealtimeNotifications($user['id'], $lastCheck);
    $unreadCount = $notificationManager->getUnreadCount($user['id']);

    Common::successResponse([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'timestamp' => time(),
    ]);
}

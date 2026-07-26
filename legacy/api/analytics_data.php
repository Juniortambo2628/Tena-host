<?php
/**
 * Analytics Data API
 * Returns JSON payload for charts (daily, property distribution, monthly)
 */

require_once __DIR__.'/../auth/check_auth.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/constants.php';

// Require admin access
requireAdmin();

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid request method', HTTP_METHOD_NOT_ALLOWED);
}

$start = Common::sanitize($_GET['start'] ?? date('Y-m-d', strtotime('-30 days')));
$end = Common::sanitize($_GET['end'] ?? date('Y-m-d'));
$propertyType = isset($_GET['property_type']) ? Common::sanitize($_GET['property_type']) : null;

try {
    $database = new Database;
    $db = $database->getConnection();

    // Normalize datetimes
    $startDateTime = $start.' 00:00:00';
    $endDateTime = $end.' 23:59:59';

    // Daily registrations
    $dailyQuery = 'SELECT DATE(created_at) as date, COUNT(*) as count
        FROM registrations
        WHERE created_at BETWEEN :start AND :end';
    if ($propertyType) {
        $dailyQuery .= ' AND property_type = :ptype';
    }
    $dailyQuery .= ' GROUP BY DATE(created_at) ORDER BY date ASC';

    $stmt = $db->prepare($dailyQuery);
    $stmt->bindParam(':start', $startDateTime);
    $stmt->bindParam(':end', $endDateTime);
    if ($propertyType) {
        $stmt->bindParam(':ptype', $propertyType);
    }
    $stmt->execute();
    $daily = $stmt->fetchAll();

    // Property distribution
    $propertyQuery = 'SELECT property_type, COUNT(*) as count
        FROM registrations
        WHERE created_at BETWEEN :start AND :end';
    if ($propertyType) {
        $propertyQuery .= ' AND property_type = :ptype';
    }
    $propertyQuery .= ' GROUP BY property_type ORDER BY count DESC';
    $stmt = $db->prepare($propertyQuery);
    $stmt->bindParam(':start', $startDateTime);
    $stmt->bindParam(':end', $endDateTime);
    if ($propertyType) {
        $stmt->bindParam(':ptype', $propertyType);
    }
    $stmt->execute();
    $property = $stmt->fetchAll();

    // Monthly growth (group by year-month)
    $monthlyQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM registrations
        WHERE created_at BETWEEN :start AND :end
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";
    $stmt = $db->prepare($monthlyQuery);
    $stmt->bindParam(':start', $startDateTime);
    $stmt->bindParam(':end', $endDateTime);
    $stmt->execute();
    $monthly = $stmt->fetchAll();

    $payload = [
        'daily' => $daily,
        'property' => $property,
        'monthly' => $monthly,
        'meta' => [
            'start' => $start,
            'end' => $end,
            'property_type' => $propertyType,
        ],
    ];

    Common::successResponse($payload);

} catch (Exception $e) {
    error_log('Analytics API error: '.$e->getMessage());
    Common::errorResponse('Server error occurred', HTTP_INTERNAL_SERVER_ERROR);
}

?>



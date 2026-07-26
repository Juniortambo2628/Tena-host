<?php
/**
 * Return registrations for a specific date (drilldown)
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid method', HTTP_METHOD_NOT_ALLOWED);
}

$date = Common::sanitize($_GET['date'] ?? '');
if (empty($date)) {
    Common::errorResponse('Date required', HTTP_BAD_REQUEST);
}

try {
    $db = (new Database)->getConnection();
    $query = 'SELECT id, first_name, last_name, email, property_type, created_at FROM registrations WHERE DATE(created_at) = :date ORDER BY created_at DESC';
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    Common::successResponse($rows);
} catch (Exception $e) {
    error_log('registrations_by_date error: '.$e->getMessage());
    Common::errorResponse('Server error', HTTP_INTERNAL_SERVER_ERROR);
}

?>



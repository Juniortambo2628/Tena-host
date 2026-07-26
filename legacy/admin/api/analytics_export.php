<?php
/**
 * Export analytics CSV for given date range and optional property type
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid method', HTTP_METHOD_NOT_ALLOWED);
}

$start = Common::sanitize($_GET['start'] ?? date('Y-m-d', strtotime('-30 days')));
$end = Common::sanitize($_GET['end'] ?? date('Y-m-d'));
$propertyType = isset($_GET['property_type']) ? Common::sanitize($_GET['property_type']) : null;

try {
    $db = (new Database)->getConnection();
    $startDate = $start.' 00:00:00';
    $endDate = $end.' 23:59:59';

    $query = 'SELECT id, first_name, last_name, email, property_type, created_at FROM registrations WHERE created_at BETWEEN :start AND :end';
    if ($propertyType) {
        $query .= ' AND property_type = :ptype';
    }
    $query .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDate);
    if ($propertyType) {
        $stmt->bindParam(':ptype', $propertyType);
    }
    $stmt->execute();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_export_'.$start.'_to_'.$end.'.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Property Type', 'Created At']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['property_type'], $row['created_at']]);
    }
    fclose($out);
    exit;

} catch (Exception $e) {
    error_log('analytics_export error: '.$e->getMessage());
    Common::errorResponse('Server error', HTTP_INTERNAL_SERVER_ERROR);
}

?>



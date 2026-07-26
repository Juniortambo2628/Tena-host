<?php

require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$database = new Database;
$db = $database->getConnection();

// Get export format
$format = $_GET['export'] ?? 'csv';

// Get filters (same as users.php)
$filters = [
    'search' => $_GET['search'] ?? '',
    'property_type' => $_GET['property_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'created_at',
    'sort_order' => $_GET['sort_order'] ?? 'DESC',
];

// Build query with filters
$where_conditions = [];
$params = [];

if (! empty($filters['search'])) {
    $where_conditions[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%'.$filters['search'].'%';
}

if (! empty($filters['property_type'])) {
    $where_conditions[] = 'property_type = :property_type';
    $params[':property_type'] = $filters['property_type'];
}

if (! empty($filters['status'])) {
    $where_conditions[] = 'status = :status';
    $params[':status'] = $filters['status'];
}

if (! empty($filters['date_from'])) {
    $where_conditions[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $filters['date_from'];
}

if (! empty($filters['date_to'])) {
    $where_conditions[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $filters['date_to'];
}

$where_clause = ! empty($where_conditions) ? 'WHERE '.implode(' AND ', $where_conditions) : '';

// Get all matching records
$query = "SELECT * FROM registrations $where_clause ORDER BY {$filters['sort_by']} {$filters['sort_order']}";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$registrations = $stmt->fetchAll();

if ($format === 'csv') {
    // CSV Export
    $filename = 'tena_registrations_'.date('Y-m-d_H-i-s').'.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $output = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($output, [
        'ID', 'First Name', 'Last Name', 'Email', 'Property Type',
        'Property Count', 'Country', 'Phone', 'Message', 'Referral Source',
        'Status', 'Registration Date', 'Last Updated',
    ]);

    // CSV Data
    foreach ($registrations as $reg) {
        fputcsv($output, [
            $reg['id'],
            $reg['first_name'],
            $reg['last_name'],
            $reg['email'],
            ucwords(str_replace('_', ' ', $reg['property_type'])),
            $reg['property_count'],
            $reg['country'] ?? '',
            ($reg['country_code'] ?? '').' '.($reg['phone_number'] ?? ''),
            $reg['message'] ?? '',
            $reg['referral_source'] ?? '',
            ucfirst($reg['status']),
            date('Y-m-d H:i:s', strtotime($reg['created_at'])),
            date('Y-m-d H:i:s', strtotime($reg['updated_at'])),
        ]);
    }

    fclose($output);
    exit();

} elseif ($format === 'pdf') {
    // Redirect to simple export for PDF
    $queryString = http_build_query(array_merge($_GET, ['export' => 'pdf']));
    header('Location: ../simple_export.php?'.$queryString);
    exit();
}

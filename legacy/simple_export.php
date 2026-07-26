<?php

/**
 * Simple Export Functions
 * Basic CSV and PDF export without external dependencies
 */

require_once 'config/constants.php';
require_once 'auth/check_auth.php';
require_once 'config/database.php';

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
            Common::getPropertyTypeDisplay($reg['property_type']),
            $reg['property_count'],
            $reg['country'] ?? '',
            ($reg['country_code'] ?? '').' '.($reg['phone_number'] ?? ''),
            $reg['message'] ?? '',
            $reg['referral_source'] ?? '',
            Common::getStatusDisplay($reg['status']),
            Common::formatDateTime($reg['created_at']),
            Common::formatDateTime($reg['updated_at']),
        ]);
    }

    fclose($output);
    exit();

} elseif ($format === 'pdf') {
    // Generate PDF using a simple approach
    $filename = 'tena_registrations_'.date('Y-m-d_H-i-s').'.pdf';

    // Generate HTML content for PDF
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Tena Registrations Report</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                font-size: 12px;
                line-height: 1.4;
            }
            h1 { 
                color: #FFD300; 
                text-align: center; 
                margin-bottom: 30px;
                font-size: 24px;
            }
            .report-info { 
                margin-bottom: 20px; 
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px; 
                font-size: 10px;
            }
            th, td { 
                border: 1px solid #333; 
                padding: 6px; 
                text-align: left; 
                vertical-align: top;
            }
            th { 
                background-color: #FFD300; 
                color: #000; 
                font-weight: bold; 
                font-size: 11px;
            }
            tr:nth-child(even) { 
                background-color: #f9f9f9; 
            }
            .summary { 
                background-color: #e9ecef; 
                padding: 15px; 
                border-radius: 5px; 
                margin-bottom: 20px; 
                font-size: 11px;
            }
            .summary h3 {
                margin-top: 0;
                color: #333;
            }
            .summary ul {
                margin-bottom: 0;
            }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
            }
        </style>
    </head>
    <body>
        <h1>Tena Waitlist Registrations Report</h1>
        <div class="report-info">
            <p><strong>Generated:</strong> '.date('Y-m-d H:i:s').'</p>
            <p><strong>Total Records:</strong> '.count($registrations).'</p>
        </div>';

    if (! empty($filters['search']) || ! empty($filters['property_type']) || ! empty($filters['status'])) {
        $html .= '<div class="summary">
            <h3>Applied Filters:</h3>
            <ul>';
        if (! empty($filters['search'])) {
            $html .= '<li>Search: '.htmlspecialchars($filters['search']).'</li>';
        }
        if (! empty($filters['property_type'])) {
            $html .= '<li>Property Type: '.ucwords(str_replace('_', ' ', $filters['property_type'])).'</li>';
        }
        if (! empty($filters['status'])) {
            $html .= '<li>Status: '.ucfirst($filters['status']).'</li>';
        }
        if (! empty($filters['date_from'])) {
            $html .= '<li>From Date: '.$filters['date_from'].'</li>';
        }
        if (! empty($filters['date_to'])) {
            $html .= '<li>To Date: '.$filters['date_to'].'</li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Property Type</th>
                <th>Property Count</th>
                <th>Location</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($registrations as $reg) {
        $html .= '<tr>
            <td>'.$reg['id'].'</td>
            <td>'.htmlspecialchars($reg['first_name'].' '.$reg['last_name']).'</td>
            <td>'.htmlspecialchars($reg['email']).'</td>
            <td>'.ucwords(str_replace('_', ' ', $reg['property_type'])).'</td>
            <td>'.$reg['property_count'].'</td>
            <td>'.htmlspecialchars($reg['country'] ?? 'N/A').'</td>
            <td>'.htmlspecialchars((($reg['country_code'] ?? '').' '.($reg['phone_number'] ?? '')) ?: 'N/A').'</td>
            <td>'.ucfirst($reg['status']).'</td>
            <td>'.date('Y-m-d H:i', strtotime($reg['created_at'])).'</td>
        </tr>';
    }

    $html .= '</tbody></table>
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #FFD300; color: #000; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                Print / Save as PDF
            </button>
        </div>
    </body>
    </html>';

    // Output HTML optimized for PDF printing
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Add JavaScript to automatically trigger print dialog
    $html = str_replace('</body>', '
        <script>
            // Auto-trigger print dialog when page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>', $html);

    echo $html;
    exit();
}

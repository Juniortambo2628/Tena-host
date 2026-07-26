<?php

/**
 * Worker for generating PDF exports asynchronously
 */
function pdf_export_worker($outputPath, $paramsJson)
{
    $params = json_decode(stripslashes($paramsJson), true) ?: [];
    require_once __DIR__.'/../config/database.php';
    require_once __DIR__.'/../vendor/autoload.php';

    $db = (new Database)->getConnection();
    $where = [];
    $bind = [];
    if (! empty($params['search'])) {
        $where[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
        $bind[':search'] = '%'.$params['search'].'%';
    }
    if (! empty($params['property_type'])) {
        $where[] = 'property_type = :ptype';
        $bind[':ptype'] = $params['property_type'];
    }
    if (! empty($params['status'])) {
        $where[] = 'status = :status';
        $bind[':status'] = $params['status'];
    }
    if (! empty($params['date_from'])) {
        $where[] = 'DATE(created_at) >= :date_from';
        $bind[':date_from'] = $params['date_from'];
    }
    if (! empty($params['date_to'])) {
        $where[] = 'DATE(created_at) <= :date_to';
        $bind[':date_to'] = $params['date_to'];
    }
    $whereClause = ! empty($where) ? 'WHERE '.implode(' AND ', $where) : '';

    $query = "SELECT id, first_name, last_name, email, property_type, country, country_code, phone_number, status, created_at FROM registrations $whereClause ORDER BY {$params['sort_by']} {$params['sort_order']}";
    $stmt = $db->prepare($query);
    foreach ($bind as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:6px;text-align:left;} th{background:#f4f4f4;}</style></head><body>';
    $html .= '<h2>Registrations Export</h2>';
    $html .= '<table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Country</th><th>Phone</th><th>Status</th><th>Registered</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr>';
        $html .= '<td>'.htmlspecialchars($r['id']).'</td>';
        $html .= '<td>'.htmlspecialchars($r['first_name'].' '.$r['last_name']).'</td>';
        $html .= '<td>'.htmlspecialchars($r['email']).'</td>';
        $html .= '<td>'.htmlspecialchars(ucwords(str_replace('_', ' ', $r['property_type']))).'</td>';
        $html .= '<td>'.htmlspecialchars($r['country'] ?? '').'</td>';
        $html .= '<td>'.htmlspecialchars((($r['country_code'] ?? '').' '.($r['phone_number'] ?? '')) ?: '').'</td>';
        $html .= '<td>'.htmlspecialchars(ucfirst($r['status'])).'</td>';
        $html .= '<td>'.htmlspecialchars($r['created_at']).'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></body></html>';

    $dompdf = new \Dompdf\Dompdf;
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    file_put_contents($outputPath, $dompdf->output());

    // Write status file
    $exportId = basename($outputPath, '.pdf');
    $statusFile = __DIR__."/exports/{$exportId}.json";
    file_put_contents($statusFile, json_encode(['status' => 'ready', 'download' => '/admin/exports/'.basename($outputPath)]));
}

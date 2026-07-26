<?php

require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;

// Require admin
requireAdmin();

$database = new Database;
$db = $database->getConnection();

// Get filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'property_type' => $_GET['property_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'created_at',
    'sort_order' => $_GET['sort_order'] ?? 'DESC',
];

$where = [];
$params = [];
if ($filters['search'] !== '') {
    $where[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%'.$filters['search'].'%';
}
if ($filters['property_type'] !== '') {
    $where[] = 'property_type = :ptype';
    $params[':ptype'] = $filters['property_type'];
}
if ($filters['status'] !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $filters['status'];
}
if ($filters['date_from'] !== '') {
    $where[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $where[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $filters['date_to'];
}

$whereClause = ! empty($where) ? 'WHERE '.implode(' AND ', $where) : '';

$query = "SELECT * FROM registrations $whereClause ORDER BY {$filters['sort_by']} {$filters['sort_order']}";
$stmt = $db->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$rows = $stmt->fetchAll();

$html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, Arial, sans-serif; font-size:10px;} table{width:100%;border-collapse:collapse;font-size:9px;} th,td{border:1px solid #ddd;padding:6px;text-align:left;vertical-align:top;} th{background:#f4f4f4;} thead {display: table-header-group;} tfoot {display: table-row-group;} @media print { table { page-break-after: auto } tr { page-break-inside: avoid; page-break-after: auto } td { word-wrap: break-word } }</style></head><body>';
$html .= '<h2>Registrations Export</h2>';
$html .= '<table><thead><tr>';
$cols = [
    'ID', 'First Name', 'Last Name', 'Email', 'Property Type', 'Property Count', 'Business Name', 'Business Website', 'Business Phone', 'Business Address', 'Years In Business', 'Monthly Guests', 'Average Nightly Rate', 'Marketing Budget', 'Current Challenges', 'Expected Launch Date', 'Referral Source', 'Referral Source Details', 'Additional Notes', 'Preferred Contact Method', 'Timezone', 'Language Preference', 'Newsletter Subscription', 'Marketing Consent', 'GDPR Consent', 'Current Booking Platforms', 'Marketing Goals', 'Country', 'Phone', 'Status', 'Registered',
];
foreach ($cols as $c) {
    $html .= '<th>'.$c.'</th>';
}
$html .= '</tr></thead><tbody>';
foreach ($rows as $r) {
    $html .= '<tr>';
    $html .= '<td>'.htmlspecialchars($r['id']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['first_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['last_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($r['email']).'</td>';
    $html .= '<td>'.htmlspecialchars(ucwords(str_replace('_', ' ', $r['property_type']))).'</td>';
    $html .= '<td>'.htmlspecialchars($r['property_count'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['business_name'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['business_website'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['business_phone'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['business_address'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['years_in_business'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['monthly_guests'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['average_nightly_rate'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['marketing_budget'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['current_challenges'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['expected_launch_date'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['referral_source'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['referral_source_details'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['additional_notes'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['preferred_contact_method'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['timezone'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['language_preference'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['newsletter_subscription'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['marketing_consent'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['gdpr_consent'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['current_booking_platforms'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['marketing_goals'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars($r['country'] ?? '').'</td>';
    $html .= '<td>'.htmlspecialchars((($r['country_code'] ?? '').' '.($r['phone_number'] ?? '')) ?: '').'</td>';
    $html .= '<td>'.htmlspecialchars(ucfirst($r['status'])).'</td>';
    $html .= '<td>'.htmlspecialchars($r['created_at']).'</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table></body></html>';

$dompdf = new Dompdf;
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'tena_registrations_'.date('Y-m-d_H-i-s').'.pdf';
$dompdf->stream($filename, ['Attachment' => 1]);
exit();

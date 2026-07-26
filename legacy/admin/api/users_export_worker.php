<?php

/**
 * Worker script for generating CSV exports in background (best-effort)
 * This file is intentionally simple and not secure for untrusted input.
 */
function users_export_worker($outputPath, $paramsJson)
{
    $raw = json_decode($paramsJson, true) ?: [];
    // params may be [filters, columns]
    $params = $raw[0] ?? [];
    $columns = $raw[1] ?? null;
    require_once __DIR__.'/../../config/database.php';

    $db = (new Database)->getConnection();
    $where = [];
    $paramsBind = [];
    if (! empty($params['search'])) {
        $where[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
        $paramsBind[':search'] = '%'.$params['search'].'%';
    }
    if (! empty($params['property_type'])) {
        $where[] = 'property_type = :ptype';
        $paramsBind[':ptype'] = $params['property_type'];
    }
    if (! empty($params['status'])) {
        $where[] = 'status = :status';
        $paramsBind[':status'] = $params['status'];
    }
    if (! empty($params['date_from'])) {
        $where[] = 'DATE(created_at) >= :date_from';
        $paramsBind[':date_from'] = $params['date_from'];
    }
    if (! empty($params['date_to'])) {
        $where[] = 'DATE(created_at) <= :date_to';
        $paramsBind[':date_to'] = $params['date_to'];
    }
    $whereClause = ! empty($where) ? 'WHERE '.implode(' AND ', $where) : '';

    // determine select columns
    $allowed = include __DIR__.'/../users_columns.php';
    if ($columns) {
        $cols = json_decode($columns, true) ?: [];
        $selected = array_values(array_intersect($cols, array_keys($allowed)));
        if (empty($selected)) {
            $selectExpr = '*';
        } else {
            $selectExpr = implode(', ', $selected);
        }
    } else {
        $selectExpr = 'id, first_name, last_name, email, property_type, property_count, business_name, business_website, business_phone, business_address, years_in_business, monthly_guests, average_nightly_rate, marketing_budget, current_challenges, expected_launch_date, referral_source, referral_source_details, additional_notes, preferred_contact_method, timezone, language_preference, newsletter_subscription, marketing_consent, gdpr_consent, current_booking_platforms, marketing_goals, country, country_code, phone_number, status, created_at';
    }

    $query = "SELECT {$selectExpr} FROM registrations $whereClause ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    foreach ($paramsBind as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    $out = fopen($outputPath, 'w');
    fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Property Type', 'Country', 'Phone', 'Status', 'Created At']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['property_type'], $row['country'] ?? '', ($row['country_code'] ?? '').' '.($row['phone_number'] ?? ''), $row['status'], $row['created_at']]);
    }
    fclose($out);

    // write status file next to the export
    $exportId = basename($outputPath, '.csv');
    $statusFile = __DIR__."/../exports/{$exportId}.json";
    file_put_contents($statusFile, json_encode(['status' => 'ready', 'download' => '/admin/exports/'.basename($outputPath)]));
}

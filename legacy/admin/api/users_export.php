<?php
/**
 * Export users CSV for given filters
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid method', HTTP_METHOD_NOT_ALLOWED);
}

$search = Common::sanitize($_GET['search'] ?? '');
$propertyType = Common::sanitize($_GET['property_type'] ?? '');
$status = Common::sanitize($_GET['status'] ?? '');
$dateFrom = Common::sanitize($_GET['date_from'] ?? '');
$dateTo = Common::sanitize($_GET['date_to'] ?? '');

try {
    $db = (new Database)->getConnection();

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
        $params[':search'] = '%'.$search.'%';
    }
    if ($propertyType !== '') {
        $where[] = 'property_type = :ptype';
        $params[':ptype'] = $propertyType;
    }
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if ($dateFrom !== '') {
        $where[] = 'DATE(created_at) >= :date_from';
        $params[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $where[] = 'DATE(created_at) <= :date_to';
        $params[':date_to'] = $dateTo;
    }

    $whereClause = ! empty($where) ? 'WHERE '.implode(' AND ', $where) : '';

    // Respect columns parameter if provided
    $columnsParam = $_GET['columns'] ?? null;
    if ($columnsParam) {
        $cols = json_decode($columnsParam, true);
        // sanitize column names against allowed list
        $allowed = include __DIR__.'/../users_columns.php';
        $selectedCols = array_values(array_intersect($cols, array_keys($allowed)));
        if (empty($selectedCols)) {
            $selectExpr = '*';
        } else {
            $selectExpr = implode(', ', array_map(function ($c) {
                return $c;
            }, $selectedCols));
        }
    } else {
        $selectExpr = 'id, first_name, last_name, email, property_type, location, status, created_at';
    }

    $query = "SELECT {$selectExpr} FROM registrations $whereClause ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export_'.($dateFrom ?: 'all').'_to_'.($dateTo ?: 'all').'.csv"');

    // If client requested background export, queue it
    if (isset($_GET['async']) && $_GET['async'] === '1') {
        $exportId = 'users_'.time().'_'.bin2hex(random_bytes(4));
        $exportFile = __DIR__."/../exports/{$exportId}.csv";
        $statusFile = __DIR__."/../exports/{$exportId}.json";

        // write a placeholder status
        file_put_contents($statusFile, json_encode(['status' => 'queued']));

        // simple background generator using exec -> php CLI (best-effort)
        $cmd = PHP_BINARY." -r \"require '".__DIR__."/users_export_worker.php' ; users_export_worker('{$exportFile}', '".addslashes(json_encode([$params, $_GET['columns'] ?? null]))."');\" > /dev/null 2>&1 &";
        // On Windows, exec background tasks are different; attempt non-blocking
        @exec($cmd);

        Common::successResponse(['export_id' => $exportId, 'status' => 'queued']);
    }

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Property Type', 'Country', 'Phone', 'Status', 'Created At']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [$row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['property_type'], $row['country'] ?? '', ($row['country_code'] ?? '').' '.($row['phone_number'] ?? ''), $row['status'], $row['created_at']]);
    }
    fclose($out);
    exit;

} catch (Exception $e) {
    error_log('users_export error: '.$e->getMessage());
    Common::errorResponse('Server error', HTTP_INTERNAL_SERVER_ERROR);
}

?>



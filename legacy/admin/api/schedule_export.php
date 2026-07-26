<?php
/**
 * Schedule exports (save a schedule entry to data/ for demo purposes)
 * POST params: type (csv|pdf), cron (simple cron string), columns (json), filters (json)
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/constants.php';
require_once __DIR__.'/../../config/database.php';

requireAdmin();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Common::errorResponse('Method not allowed', HTTP_METHOD_NOT_ALLOWED);
}

$type = $_POST['type'] ?? 'csv';
$cron = trim($_POST['cron'] ?? '');
$columns = $_POST['columns'] ?? '[]';
$filters = $_POST['filters'] ?? '{}';

if ($cron === '') {
    Common::errorResponse('cron required');
}

$jobsFile = __DIR__.'/../../data/export_schedules.json';
$jobs = file_exists($jobsFile) ? (json_decode(file_get_contents($jobsFile), true) ?: []) : [];
$id = 'job_'.time().'_'.bin2hex(random_bytes(4));
$jobs[$id] = ['id' => $id, 'type' => $type, 'cron' => $cron, 'columns' => json_decode($columns, true), 'filters' => json_decode($filters, true), 'owner' => $user['id'], 'created' => date('c')];
file_put_contents($jobsFile, json_encode($jobs));
Common::successResponse(['job_id' => $id]);

?>



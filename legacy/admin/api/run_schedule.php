<?php
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/constants.php';
require_once __DIR__.'/../../config/database.php';
requireAdmin();

$id = $_GET['id'] ?? '';
if (! $id) {
    Common::errorResponse('id required');
}

$jobsFile = __DIR__.'/../../data/export_schedules.json';
if (! file_exists($jobsFile)) {
    Common::errorResponse('job not found');
}
$jobs = json_decode(file_get_contents($jobsFile), true) ?: [];
if (! isset($jobs[$id])) {
    Common::errorResponse('job not found');
}
$job = $jobs[$id];

// Trigger worker directly
require_once __DIR__.'/users_export_worker.php';
$exportId = 'manual_'.$id.'_'.time();
$exportFile = __DIR__.'/../exports/'.$exportId.'.csv';
users_export_worker($exportFile, json_encode([$job['filters'], json_encode($job['columns'])]));
Common::successResponse(['message' => 'Job executed', 'export_id' => $exportId]);

?>



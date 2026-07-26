<?php
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/constants.php';
requireAdmin();

$id = $_POST['id'] ?? $_GET['id'] ?? '';
if (! $id) {
    Common::errorResponse('id required');
}

$jobsFile = __DIR__.'/../../data/export_schedules.json';
if (! file_exists($jobsFile)) {
    Common::errorResponse('job not found');
}
$jobs = json_decode(file_get_contents($jobsFile), true) ?: [];
if (isset($jobs[$id])) {
    unset($jobs[$id]);
    file_put_contents($jobsFile, json_encode($jobs));
    Common::successResponse(['message' => 'Job cancelled']);
}
Common::errorResponse('job not found');

?>



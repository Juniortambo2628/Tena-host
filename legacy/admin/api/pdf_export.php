<?php
/**
 * API to queue PDF export (async) or stream immediately
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid method', HTTP_METHOD_NOT_ALLOWED);
}

$async = isset($_GET['async']) && $_GET['async'] === '1';

// Collect filter params
$filters = [
    'search' => $_GET['search'] ?? '',
    'property_type' => $_GET['property_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'created_at',
    'sort_order' => $_GET['sort_order'] ?? 'DESC',
];

try {
    if ($async) {
        $exportId = 'pdf_'.time().'_'.bin2hex(random_bytes(4));
        $statusFile = __DIR__."/../exports/{$exportId}.json";
        // write queued status
        file_put_contents($statusFile, json_encode(['status' => 'queued']));

        // Spawn background worker (best-effort)
        $paramsJson = addslashes(json_encode($filters));
        $outputPath = __DIR__."/../exports/{$exportId}.pdf";
        $php = PHP_BINARY;
        $worker = escapeshellarg(__DIR__.'/pdf_export_worker.php');
        $cmd = "{$php} -r \"require '".__DIR__."/pdf_export_worker.php'; pdf_export_worker('{$outputPath}', '{$paramsJson}');\" > /dev/null 2>&1 &";
        @exec($cmd);

        Common::successResponse(['export_id' => $exportId, 'status' => 'queued']);
    } else {
        // Immediate: redirect to admin/pdf_export.php which streams the PDF
        $qs = http_build_query(array_merge($_GET, ['stream' => '1']));
        header('Location: ../pdf_export.php?'.$qs);
        exit;
    }

} catch (Exception $e) {
    error_log('pdf_export api error: '.$e->getMessage());
    Common::errorResponse('Server error', HTTP_INTERNAL_SERVER_ERROR);
}

?>



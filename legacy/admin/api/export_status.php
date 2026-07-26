<?php
/**
 * Simple export status endpoint
 * Accepts export_id and returns status and optional download URL when ready
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/constants.php';

// This implementation uses a file-based simple queue for demo purposes.
$exportId = $_GET['export_id'] ?? '';
if (empty($exportId)) {
    Common::errorResponse('export_id required');
}

$statusFile = __DIR__."/../exports/{$exportId}.json";
if (! file_exists($statusFile)) {
    Common::successResponse(['status' => 'pending']);
}

$payload = json_decode(file_get_contents($statusFile), true);
if (! $payload) {
    Common::successResponse(['status' => 'pending']);
}

Common::successResponse($payload);

?>



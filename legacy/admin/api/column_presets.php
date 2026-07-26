<?php
/**
 * Column presets API
 * GET -> list presets for current user
 * POST action=save name=... columns=json
 * POST action=delete name=...
 */
require_once __DIR__.'/../../auth/check_auth.php';
require_once __DIR__.'/../../config/constants.php';

// Require admin
requireAdmin();

$user = getCurrentUser();
$userId = $user['id'];
$file = __DIR__."/../../data/column_presets_user_{$userId}.json";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (! file_exists($file)) {
        Common::successResponse([]);
    }
    $data = json_decode(file_get_contents($file), true) ?: [];
    // return as array of {name, columns}
    $out = [];
    foreach ($data as $name => $cols) {
        $out[] = ['name' => $name, 'columns' => $cols];
    }
    Common::successResponse($out);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $cols = json_decode($_POST['columns'] ?? '[]', true);
        if ($name === '' || ! is_array($cols)) {
            Common::errorResponse('Invalid payload');
        }
        $existing = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $existing[$name] = $cols;
        file_put_contents($file, json_encode($existing));
        // also allow saving global default if user is super-admin (example: id=1)
        if (isset($_POST['global']) && $_POST['global'] === '1' && $userId == 1) {
            file_put_contents(__DIR__.'/../../data/column_presets_global.json', json_encode($existing));
        }
        Common::successResponse(['name' => $name, 'columns' => $cols]);
    }
    if ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Common::errorResponse('Name required');
        }
        $existing = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        if (isset($existing[$name])) {
            unset($existing[$name]);
            file_put_contents($file, json_encode($existing));
        }
        Common::successResponse(['deleted' => $name]);
    }

    Common::errorResponse('Invalid action');
}

Common::errorResponse('Method not allowed', HTTP_METHOD_NOT_ALLOWED);

?>



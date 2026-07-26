<?php

/**
 * Get Registration Details API
 * Fetches detailed information for a specific registration
 */

require_once '../config/database.php';
require_once '../config/constants.php';

// Set JSON header
header('Content-Type: '.RESPONSE_JSON);

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Common::errorResponse('Invalid request method', HTTP_METHOD_NOT_ALLOWED);
}

// Get registration ID
$id = (int) ($_GET['id'] ?? 0);

if (! $id) {
    Common::errorResponse('Registration ID required', HTTP_BAD_REQUEST);
}

try {
    $database = new Database;
    $db = $database->getConnection();

    if (! $db) {
        throw new Exception('Database connection failed');
    }

    // Fetch registration details
    $query = 'SELECT * FROM registrations WHERE id = :id';
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $registration = $stmt->fetch();

    if (! $registration) {
        Common::errorResponse('Registration not found', HTTP_NOT_FOUND);
    }

    // Return registration details
    Common::successResponse($registration);

} catch (Exception $e) {
    error_log('Get registration details error: '.$e->getMessage());
    Common::errorResponse('Server error occurred', HTTP_INTERNAL_SERVER_ERROR);
}

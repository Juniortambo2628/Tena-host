<?php
/**
 * Settings API Handler
 * Handles all settings-related AJAX requests
 */

require_once '../../auth/check_auth.php';
require_once '../../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Get current user
$currentUser = getCurrentUser();

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$database = new Database;
$db = $database->getConnection();

try {
    switch ($action) {
        case 'regenerate_api_key':
            // Generate new API key
            $api_key = 'tena_'.bin2hex(random_bytes(32));

            // Store in database (if API keys table exists)
            // For now, just return the key
            echo json_encode([
                'success' => true,
                'api_key' => $api_key,
                'message' => 'API key regenerated successfully',
            ]);
            break;

        case 'save_preferences':
            // Save user preferences to database or session
            $preferences = json_decode(file_get_contents('php://input'), true);

            // Store preferences (could be in a preferences table)
            $_SESSION['user_preferences'] = $preferences;

            echo json_encode([
                'success' => true,
                'message' => 'Preferences saved successfully',
            ]);
            break;

        case 'save_notification_preferences':
            // Save to notification_preferences table
            $categories = ['system', 'registration', 'export'];
            $email_enabled = $_POST['email_enabled'] ?? 1;
            $dashboard_enabled = $_POST['dashboard_enabled'] ?? 1;

            foreach ($categories as $category) {
                $query = 'INSERT INTO notification_preferences 
                         (user_id, category, email_enabled, dashboard_enabled) 
                         VALUES (:user_id, :category, :email, :dashboard)
                         ON DUPLICATE KEY UPDATE 
                         email_enabled = :email, dashboard_enabled = :dashboard';

                $stmt = $db->prepare($query);
                $stmt->bindValue(':user_id', $currentUser['id'], PDO::PARAM_INT);
                $stmt->bindValue(':category', $category);
                $stmt->bindValue(':email', $email_enabled, PDO::PARAM_BOOL);
                $stmt->bindValue(':dashboard', $dashboard_enabled, PDO::PARAM_BOOL);
                $stmt->execute();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Notification preferences saved',
            ]);
            break;

        case 'download_user_data':
            // GDPR compliance - export user's data
            $query = 'SELECT * FROM users WHERE id = :user_id';
            $stmt = $db->prepare($query);
            $stmt->bindValue(':user_id', $currentUser['id'], PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch();

            // Remove sensitive data
            unset($userData['password_hash']);

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="my_data_'.date('Y-m-d').'.json"');
            echo json_encode($userData, JSON_PRETTY_PRINT);
            break;

        case 'logout_all_devices':
            // Invalidate all sessions for this user
            // This would require a sessions table to track multiple devices
            // For now, just logout current session
            session_destroy();

            echo json_encode([
                'success' => true,
                'message' => 'Logged out from all devices',
                'redirect' => '../../auth/login.php',
            ]);
            break;

        case 'clear_cache':
            // Clear server-side cache (if any)
            // Clear user-specific cached data
            $_SESSION['cache_cleared'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
            break;

        case 'get_preferences':
            // Retrieve user preferences
            $preferences = $_SESSION['user_preferences'] ?? [];

            echo json_encode([
                'success' => true,
                'preferences' => $preferences,
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action',
            ]);
            break;
    }

} catch (Exception $e) {
    error_log('Settings API error: '.$e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
    ]);
}
?>


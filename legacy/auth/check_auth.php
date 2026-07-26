<?php

/**
 * Authentication Check
 * Handles user authentication and authorization
 */

require_once __DIR__.'/../config/constants.php';

// Start session with proper configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (! isset($_SESSION['user_id'])) {
    if (Common::isAjax()) {
        Common::errorResponse(MSG_ACCESS_DENIED, HTTP_UNAUTHORIZED);
    } else {
        header('Location: '.AUTH_URL.'/login.php');
        exit();
    }
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_destroy();
    if (Common::isAjax()) {
        Common::errorResponse('Session expired', HTTP_UNAUTHORIZED);
    } else {
        header('Location: '.AUTH_URL.'/login.php?expired=1');
        exit();
    }
}

// Update last activity
$_SESSION['last_activity'] = time();

// Optional: Check user role for admin-only pages
function requireAdmin()
{
    if (! isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_ADMIN) {
        if (Common::isAjax()) {
            Common::errorResponse(MSG_ACCESS_DENIED, HTTP_FORBIDDEN);
        } else {
            Common::redirect(BASE_URL.'/dashboard.php', MSG_ACCESS_DENIED, NOTIFICATION_ERROR);
        }
    }
}

// Get current user info
function getCurrentUser()
{
    // Get fresh data from database to include last_login
    try {
        $database = new Database;
        $db = $database->getConnection();

        $query = 'SELECT id, username, email, role, last_login FROM users WHERE id = :user_id';
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }
    } catch (Exception $e) {
        // Fallback to session data
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'last_login' => $_SESSION['last_login'] ?? null,
    ];
}

// Check if user has specific role
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Check if user is admin
function isAdmin()
{
    return hasRole(ROLE_ADMIN);
}

// Log user activity
function logUserActivity($action, $details = '')
{
    Common::logActivity($action, $details, $_SESSION['user_id']);
}

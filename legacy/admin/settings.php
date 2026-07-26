<?php
/**
 * Tena Dashboard Settings
 * User preferences, password management, and system settings
 */

require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

// Get current user
$currentUser = getCurrentUser();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database;
    $db = $database->getConnection();

    // Handle password update
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        } else {
            // Verify current password
            $query = 'SELECT password_hash FROM users WHERE id = :user_id';
            $stmt = $db->prepare($query);
            $stmt->bindValue(':user_id', $currentUser['id'], PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = 'UPDATE users SET password_hash = :password_hash WHERE id = :user_id';
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindValue(':password_hash', $new_hash);
                $update_stmt->bindValue(':user_id', $currentUser['id'], PDO::PARAM_INT);

                if ($update_stmt->execute()) {
                    $success_message = 'Password updated successfully!';
                } else {
                    $error_message = 'Failed to update password. Please try again.';
                }
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    }

    // Handle profile update
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $username = trim($_POST['username'] ?? '');

        if (empty($email) || empty($username)) {
            $error_message = 'Username and email are required.';
        } elseif (! $email) {
            $error_message = 'Please enter a valid email address.';
        } else {
            $query = 'UPDATE users SET email = :email, username = :username WHERE id = :user_id';
            $stmt = $db->prepare($query);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':user_id', $currentUser['id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                $currentUser['email'] = $email;
                $currentUser['username'] = $username;
                $_SESSION['user'] = $currentUser;
            } else {
                $error_message = 'Failed to update profile. Please try again.';
            }
        }
    }

    // Handle notification preferences
    if (isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
        // This would integrate with the notification_preferences table
        $success_message = 'Notification preferences updated successfully!';
    }
}

// Include header with sidebar
include '../includes/header.php';
?>

        <!-- Main Content -->
        <div class="container-fluid px-4">
            <!-- Settings Section -->
            <div id="settings-section" class="dashboard-section">
                <div class="row">
                    <div class="col-12 mb-4">
                        <h2 class="mb-0">Settings</h2>
                        <p class="text-muted">Manage your account preferences and system settings</p>
                    </div>
                </div>

                <?php if ($success_message) { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <?php if ($error_message) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <div class="row g-4">
                    <!-- Account Settings -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Account Settings
                                </h5>
                            </div>
                            <div class="p-4">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-1"></i>Username
                                        </label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-shield-alt me-1"></i>Role
                                        </label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?>" disabled>
                                        <small class="text-muted">Contact an administrator to change your role</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Password Settings -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </h5>
                            </div>
                            <div class="p-4">
                                <form method="POST" id="passwordForm">
                                    <input type="hidden" name="action" value="update_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">
                                            <i class="fas fa-key me-1"></i>Current Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-primary" type="button" onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">
                                            <i class="fas fa-key me-1"></i>New Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                            <button class="btn btn-outline-primary" type="button" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2" id="passwordStrength"></div>
                                        <small class="text-muted">Must be at least 8 characters long</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-key me-1"></i>Confirm New Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-primary" type="button" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-lock me-2"></i>Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Preferences -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-sliders-h me-2"></i>Dashboard Preferences
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-list me-1"></i>Default Items Per Page
                                    </label>
                                    <select class="form-select" id="defaultPerPage">
                                        <option value="10">10 items</option>
                                        <option value="25" selected>25 items</option>
                                        <option value="50">50 items</option>
                                        <option value="100">100 items</option>
                                    </select>
                                    <small class="text-muted">Number of records to show in tables by default</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-sort me-1"></i>Default Sort Order
                                    </label>
                                    <select class="form-select" id="defaultSortOrder">
                                        <option value="created_at_desc" selected>Newest First</option>
                                        <option value="created_at_asc">Oldest First</option>
                                        <option value="name_asc">Name (A-Z)</option>
                                        <option value="name_desc">Name (Z-A)</option>
                                        <option value="email_asc">Email (A-Z)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-clock me-1"></i>Timezone
                                    </label>
                                    <select class="form-select" id="timezone">
                                        <option value="UTC">UTC (Coordinated Universal Time)</option>
                                        <option value="America/New_York">Eastern Time (ET)</option>
                                        <option value="America/Chicago">Central Time (CT)</option>
                                        <option value="America/Denver">Mountain Time (MT)</option>
                                        <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                        <option value="Europe/London">London (GMT)</option>
                                        <option value="Europe/Paris">Paris (CET)</option>
                                        <option value="Asia/Tokyo">Tokyo (JST)</option>
                                        <option value="Australia/Sydney">Sydney (AEST)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date Format
                                    </label>
                                    <select class="form-select" id="dateFormat">
                                        <option value="M j, Y" selected>Oct 1, 2025</option>
                                        <option value="Y-m-d">2025-10-01</option>
                                        <option value="d/m/Y">01/10/2025</option>
                                        <option value="m/d/Y">10/01/2025</option>
                                        <option value="F j, Y">October 1, 2025</option>
                                    </select>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="saveDashboardPreferences()">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-bell me-2"></i>Notification Preferences
                                </h5>
                            </div>
                            <div class="p-4">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_notifications">
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                            <label class="form-check-label" for="emailNotifications">
                                                <i class="fas fa-envelope me-2"></i>Email Notifications
                                            </label>
                                        </div>
                                        <small class="text-muted d-block ms-4">Receive email notifications for new registrations</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="dashboardNotifications" checked>
                                            <label class="form-check-label" for="dashboardNotifications">
                                                <i class="fas fa-desktop me-2"></i>Dashboard Notifications
                                            </label>
                                        </div>
                                        <small class="text-muted d-block ms-4">Show notifications in the dashboard</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="exportNotifications" checked>
                                            <label class="form-check-label" for="exportNotifications">
                                                <i class="fas fa-file-export me-2"></i>Export Notifications
                                            </label>
                                        </div>
                                        <small class="text-muted d-block ms-4">Notify when scheduled exports complete</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="weeklyReport">
                                            <label class="form-check-label" for="weeklyReport">
                                                <i class="fas fa-chart-bar me-2"></i>Weekly Summary Reports
                                            </label>
                                        </div>
                                        <small class="text-muted d-block ms-4">Receive weekly analytics summary via email</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Notification Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Export Settings -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-export me-2"></i>Export Settings
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-file-csv me-1"></i>Default Export Format
                                    </label>
                                    <select class="form-select" id="defaultExportFormat">
                                        <option value="csv" selected>CSV (Spreadsheet)</option>
                                        <option value="pdf">PDF (Document)</option>
                                        <option value="xlsx">Excel (XLSX)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="includeHeaders" checked>
                                        <label class="form-check-label" for="includeHeaders">
                                            Include column headers in exports
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="timestampExports" checked>
                                        <label class="form-check-label" for="timestampExports">
                                            Add timestamp to export filenames
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-filter me-1"></i>Auto-apply Filters to Exports
                                    </label>
                                    <select class="form-select" id="exportFilters">
                                        <option value="current" selected>Use current filter settings</option>
                                        <option value="all">Always export all records</option>
                                        <option value="ask">Ask each time</option>
                                    </select>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="saveExportSettings()">
                                    <i class="fas fa-save me-2"></i>Save Export Settings
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Display Preferences -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-palette me-2"></i>Display Preferences
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-moon me-1"></i>Theme
                                    </label>
                                    <select class="form-select" id="themeSelect">
                                        <option value="light" selected>Light Mode</option>
                                        <option value="dark">Dark Mode</option>
                                        <option value="auto">Auto (System)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-font me-1"></i>Font Size
                                    </label>
                                    <select class="form-select" id="fontSize">
                                        <option value="small">Small</option>
                                        <option value="normal" selected>Normal</option>
                                        <option value="large">Large</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="compactView">
                                        <label class="form-check-label" for="compactView">
                                            <i class="fas fa-compress me-2"></i>Compact View
                                        </label>
                                    </div>
                                    <small class="text-muted d-block ms-4">Reduce spacing in tables and cards</small>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="showAnimations" checked>
                                        <label class="form-check-label" for="showAnimations">
                                            <i class="fas fa-magic me-2"></i>Enable Animations
                                        </label>
                                    </div>
                                    <small class="text-muted d-block ms-4">Show smooth transitions and animations</small>
                                </div>

                                <button type="button" class="btn btn-primary" onclick="saveDisplayPreferences()">
                                    <i class="fas fa-save me-2"></i>Save Display Settings
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Session & Security -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Security & Session
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <h6 class="mb-2">Session Information</h6>
                                    <div class="info-item mb-2">
                                        <span class="info-label">Last Login:</span>
                                        <span class="info-value"><?php echo isset($currentUser['last_login']) && $currentUser['last_login'] ? date('M j, Y g:i A', strtotime($currentUser['last_login'])) : 'First login'; ?></span>
                                    </div>
                                    <div class="info-item mb-2">
                                        <span class="info-label">Session Expires:</span>
                                        <span class="info-value" id="sessionExpiry">In 60 minutes</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="mb-2">Security Options</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="rememberMe">
                                        <label class="form-check-label" for="rememberMe">
                                            Remember me on this device
                                        </label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                        <label class="form-check-label" for="twoFactorAuth">
                                            Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                    <small class="text-muted d-block">Coming soon</small>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary text-danger" onclick="confirmLogoutAll()">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout from All Devices
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-cogs me-2"></i>Advanced Settings
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <h6 class="mb-3">Data Management</h6>
                                    
                                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="clearCache()">
                                        <i class="fas fa-broom me-2"></i>Clear Cache
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="downloadMyData()">
                                        <i class="fas fa-download me-2"></i>Download My Data (GDPR)
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <h6 class="mb-3">API Settings</h6>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" value="••••••••••••••••••••" id="apiKeyDisplay" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="regenerateAPIKey()">
                                            <i class="fas fa-sync me-1"></i>Regenerate
                                        </button>
                                    </div>
                                    <small class="text-muted">API Key for third-party integrations</small>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> Some advanced features are in development.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary w-100" onclick="window.location.href='users.php'">
                                            <i class="fas fa-users fa-2x d-block mb-2"></i>
                                            <span>Manage Users</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary w-100" onclick="window.location.href='analytics.php'">
                                            <i class="fas fa-chart-line fa-2x d-block mb-2"></i>
                                            <span>View Analytics</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary w-100" onclick="window.location.href='../dashboard.php'">
                                            <i class="fas fa-tachometer-alt fa-2x d-block mb-2"></i>
                                            <span>Main Dashboard</span>
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary w-100" onclick="window.location.href='../index.html'">
                                            <i class="fas fa-home fa-2x d-block mb-2"></i>
                                            <span>Back to Website</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information (Admin Only) -->
                    <?php if ($currentUser['role'] === 'admin') { ?>
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-server me-2"></i>System Information
                                </h5>
                            </div>
                            <div class="p-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-card mb-3">
                                            <h6 class="info-label">PHP Version</h6>
                                            <div class="info-value"><?php echo phpversion(); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-card mb-3">
                                            <h6 class="info-label">Database</h6>
                                            <div class="info-value">
                                                <?php
                                                $db = (new Database)->getConnection();
                        $version = $db->query('SELECT VERSION() as v')->fetch()['v'];
                        echo 'MySQL '.explode('-', $version)[0];
                        ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-card mb-3">
                                            <h6 class="info-label">Environment</h6>
                                            <div class="info-value">
                                                <span class="badge bg-<?php echo ENVIRONMENT === 'development' ? 'warning' : (ENVIRONMENT === 'staging' ? 'info' : 'success'); ?>">
                                                    <?php echo strtoupper(ENVIRONMENT); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-item">
                                            <span class="info-label">Total Users:</span>
                                            <span class="info-value">
                                                <?php
                        $stmt = $db->query('SELECT COUNT(*) as c FROM users');
                        echo $stmt->fetch()['c'];
                        ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-item">
                                            <span class="info-label">Total Registrations:</span>
                                            <span class="info-value">
                                                <?php
                        $stmt = $db->query('SELECT COUNT(*) as c FROM registrations');
                        echo $stmt->fetch()['c'];
                        ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-item">
                                            <span class="info-label">Disk Usage:</span>
                                            <span class="info-value">
                                                <?php
                        $bytes = disk_total_space('.') - disk_free_space('.');
                        echo Common::formatFileSize($bytes);
                        ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
document.getElementById('new_password')?.addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('passwordStrength');
    
    let strength = 0;
    let feedback = '';
    let color = '';
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    switch(strength) {
        case 0:
        case 1:
            feedback = 'Weak password';
            color = 'danger';
            break;
        case 2:
            feedback = 'Fair password';
            color = 'warning';
            break;
        case 3:
            feedback = 'Good password';
            color = 'info';
            break;
        case 4:
            feedback = 'Strong password';
            color = 'success';
            break;
    }
    
    if (password.length > 0) {
        strengthDiv.innerHTML = `<div class="progress" style="height: 5px;">
            <div class="progress-bar bg-${color}" role="progressbar" style="width: ${strength * 25}%"></div>
        </div>
        <small class="text-${color}">${feedback}</small>`;
    } else {
        strengthDiv.innerHTML = '';
    }
});

// Confirm password match validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    } else {
        this.classList.remove('is-valid', 'is-invalid');
    }
});

// Save dashboard preferences
function saveDashboardPreferences() {
    const preferences = {
        perPage: document.getElementById('defaultPerPage').value,
        sortOrder: document.getElementById('defaultSortOrder').value,
        timezone: document.getElementById('timezone').value,
        dateFormat: document.getElementById('dateFormat').value
    };
    
    localStorage.setItem('dashboard_preferences', JSON.stringify(preferences));
    
    showNotification('success', 'Dashboard preferences saved successfully!');
}

// Save export settings
function saveExportSettings() {
    const settings = {
        format: document.getElementById('defaultExportFormat').value,
        includeHeaders: document.getElementById('includeHeaders').checked,
        timestamp: document.getElementById('timestampExports').checked,
        filters: document.getElementById('exportFilters').value
    };
    
    localStorage.setItem('export_settings', JSON.stringify(settings));
    
    showNotification('success', 'Export settings saved successfully!');
}

// Save display preferences
function saveDisplayPreferences() {
    const preferences = {
        theme: document.getElementById('themeSelect').value,
        fontSize: document.getElementById('fontSize').value,
        compactView: document.getElementById('compactView').checked,
        animations: document.getElementById('showAnimations').checked
    };
    
    localStorage.setItem('display_preferences', JSON.stringify(preferences));
    
    // Apply theme immediately
    applyDisplayPreferences(preferences);
    
    showNotification('success', 'Display preferences saved successfully!');
}

// Apply display preferences
function applyDisplayPreferences(prefs) {
    if (!prefs) {
        const saved = localStorage.getItem('display_preferences');
        prefs = saved ? JSON.parse(saved) : {};
    }
    
    // Apply theme
    if (prefs.theme === 'dark') {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // Apply font size
    if (prefs.fontSize) {
        document.body.classList.remove('font-small', 'font-large');
        if (prefs.fontSize !== 'normal') {
            document.body.classList.add('font-' + prefs.fontSize);
        }
    }
    
    // Apply compact view
    if (prefs.compactView) {
        document.body.classList.add('compact-view');
    } else {
        document.body.classList.remove('compact-view');
    }
}

// Show notification
function showNotification(type, message) {
    if (window.tenaAjax && window.tenaAjax.showNotification) {
        window.tenaAjax.showNotification({
            type: type,
            title: type === 'success' ? 'Success' : 'Info',
            message: message
        });
    } else {
        alert(message);
    }
}

// Clear cache
function clearCache() {
    if (confirm('Are you sure you want to clear the cache? This will reset your preferences.')) {
        localStorage.clear();
        sessionStorage.clear();
        showNotification('success', 'Cache cleared successfully! Page will reload.');
        setTimeout(() => location.reload(), 1500);
    }
}

// Download user data (GDPR compliance)
function downloadMyData() {
    window.location.href = 'api/settings_handler.php?action=download_user_data';
}

// Regenerate API key
function regenerateAPIKey() {
    if (confirm('Are you sure you want to regenerate your API key? Your old key will stop working.')) {
        fetch('api/settings_handler.php?action=regenerate_api_key', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('apiKeyDisplay').value = data.api_key;
                showNotification('success', 'API key regenerated successfully!');
            } else {
                showNotification('error', data.message || 'Failed to regenerate API key');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Failed to regenerate API key');
        });
    }
}

// Confirm logout from all devices
function confirmLogoutAll() {
    if (confirm('Are you sure you want to logout from all devices? You will need to login again on each device.')) {
        fetch('api/settings_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=logout_all_devices'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                setTimeout(() => {
                    window.location.href = data.redirect || '../auth/login.php';
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = '../auth/logout.php';
        });
    }
}

// Load preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard preferences
    const dashPrefs = localStorage.getItem('dashboard_preferences');
    if (dashPrefs) {
        const prefs = JSON.parse(dashPrefs);
        if (prefs.perPage) document.getElementById('defaultPerPage').value = prefs.perPage;
        if (prefs.sortOrder) document.getElementById('defaultSortOrder').value = prefs.sortOrder;
        if (prefs.timezone) document.getElementById('timezone').value = prefs.timezone;
        if (prefs.dateFormat) document.getElementById('dateFormat').value = prefs.dateFormat;
    }
    
    // Load export settings
    const exportPrefs = localStorage.getItem('export_settings');
    if (exportPrefs) {
        const prefs = JSON.parse(exportPrefs);
        if (prefs.format) document.getElementById('defaultExportFormat').value = prefs.format;
        if (prefs.includeHeaders !== undefined) document.getElementById('includeHeaders').checked = prefs.includeHeaders;
        if (prefs.timestamp !== undefined) document.getElementById('timestampExports').checked = prefs.timestamp;
        if (prefs.filters) document.getElementById('exportFilters').value = prefs.filters;
    }
    
    // Load display preferences
    const displayPrefs = localStorage.getItem('display_preferences');
    if (displayPrefs) {
        const prefs = JSON.parse(displayPrefs);
        if (prefs.theme) document.getElementById('themeSelect').value = prefs.theme;
        if (prefs.fontSize) document.getElementById('fontSize').value = prefs.fontSize;
        if (prefs.compactView !== undefined) document.getElementById('compactView').checked = prefs.compactView;
        if (prefs.animations !== undefined) document.getElementById('showAnimations').checked = prefs.animations;
        
        applyDisplayPreferences(prefs);
    }
});

// Update session expiry countdown
function updateSessionExpiry() {
    // This would be calculated based on actual session timeout
    // For now, just a placeholder
    const expiryElement = document.getElementById('sessionExpiry');
    if (expiryElement) {
        // Could fetch actual expiry from server
        expiryElement.textContent = 'In 60 minutes';
    }
}

// Initialize
updateSessionExpiry();
setInterval(updateSessionExpiry, 60000); // Update every minute
</script>

<style>
/* Settings-specific styles */
.info-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

.info-label {
    font-weight: 600;
    color: #666;
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.info-value {
    font-size: 1.1rem;
    color: #000;
    font-weight: 500;
}

.info-item {
    padding: 0.5rem 0;
}

.info-item .info-label {
    display: inline-block;
    min-width: 150px;
    font-weight: 600;
    color: #666;
}

.info-item .info-value {
    color: #000;
}

/* Dark mode styles */
body.dark-mode {
    background-color: #1a1a1a !important;
    color: #fff !important;
}

body.dark-mode .data-table {
    background-color: #2a2a2a;
    border-color: #444;
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    background-color: #333;
    color: #fff;
    border-color: #555;
}

/* Font size adjustments */
body.font-small {
    font-size: 0.875rem;
}

body.font-large {
    font-size: 1.125rem;
}

/* Compact view */
body.compact-view .data-table {
    margin-bottom: 1rem;
}

body.compact-view .p-4 {
    padding: 1rem !important;
}

body.compact-view .mb-4 {
    margin-bottom: 1rem !important;
}
</style>


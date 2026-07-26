<?php
// Enhanced Dashboard with authentication and database integration
require_once 'auth/check_auth.php';
require_once 'config/database.php';

$database = new Database;
$db = $database->getConnection();

// Get current user
$currentUser = getCurrentUser();

// Get registration count and data
$registrationCount = 0;
$registrations = [];

try {
    $query = 'SELECT COUNT(*) as count FROM registrations';
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $registrationCount = $result['count'];

    // Get recent registrations
    $query = 'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10';
    $stmt = $db->prepare($query);
    $stmt->execute();
    $registrations = $stmt->fetchAll();

    // Get analytics data
    $query = "SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_registrations,
                COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals,
                COUNT(CASE WHEN property_type = 'hotel' THEN 1 END) as hotels,
                COUNT(CASE WHEN property_type = 'b&b' THEN 1 END) as bnb
              FROM registrations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $analytics = $stmt->fetch();

} catch (Exception $e) {
    $error = 'Database connection error: '.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tena Dashboard - Waitlist Management</title>
    
    <!-- Favicon -->
    <link href="assets/Tena-logo-square.jpg" rel="icon">
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Space+Grotesk&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Dashboard Custom Styles -->
    <style>
        .dashboard-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-content {
            margin-left: 280px;
            min-height: 100vh;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid #333;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-link {
            color: #ccc;
            padding: 0.75rem 1.5rem;
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .sidebar-nav .nav-link:hover {
            color: var(--bs-primary);
            background-color: rgba(255, 211, 0, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar-nav .nav-link.active {
            color: var(--bs-primary);
            background-color: rgba(255, 211, 0, 0.2);
            border-right: 3px solid var(--bs-primary);
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .dashboard-header {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--bs-primary);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--bs-primary);
        }
        
        .data-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .data-table .table {
            margin: 0;
        }
        
        .data-table .table thead th {
            background: var(--bs-primary);
            color: #000;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .data-table .table tbody td {
            padding: 1rem;
            border-color: #f0f0f0;
            vertical-align: middle;
        }
        
        .data-table .table tbody tr:hover {
            background-color: rgba(255, 211, 0, 0.05);
        }
        
        .btn-dashboard {
            background: var(--bs-primary);
            color: #000;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-dashboard:hover {
            background: #e6c200;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 211, 0, 0.3);
        }
        
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--bs-primary);
            color: #000;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .dashboard-sidebar {
                transform: translateX(-100%);
            }
            
            .dashboard-sidebar.show {
                transform: translateX(0);
            }
            
            .dashboard-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Dashboard Sidebar -->
    <div class="dashboard-sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="assets/Tena-logo-square.jpg" alt="Tena" style="max-width: 50px; margin-right: 1rem;">
            <h4 class="text-white d-inline">Tena Dashboard</h4>
        </div>
        
        <nav class="sidebar-nav">
            <a href="#overview" class="nav-link active" onclick="showSection('overview')">
                <i class="fas fa-chart-pie"></i>
                Overview
            </a>
            <a href="#registrations" class="nav-link" onclick="showSection('registrations')">
                <i class="fas fa-users"></i>
                Waitlist Registrations
            </a>
            <a href="admin/users.php" class="nav-link">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="admin/analytics.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                Analytics
            </a>
            <a href="#settings" class="nav-link" onclick="showSection('settings')">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <a href="index.html" class="nav-link">
                <i class="fas fa-home"></i>
                Back to Website
            </a>
        </nav>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">Dashboard Overview</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-dashboard" onclick="exportData('csv')">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </button>
                        <button class="btn btn-dashboard" onclick="exportData('pdf')">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-primary position-relative" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell me-2"></i>Notifications
                                <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px;">
                                <li class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <button class="btn btn-sm btn-outline-secondary mark-all-read">Mark all read</button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="notification-list" style="max-height: 300px; overflow-y: auto;">
                                    <div class="text-center p-3">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($currentUser['username']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Sections -->
        <div class="container-fluid">
            <!-- Overview Section -->
            <div id="overview-section" class="dashboard-section">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Registrations</h6>
                                    <div class="stats-number"><?php echo $analytics['total_registrations'] ?? 0; ?></div>
                                </div>
                                <i class="fas fa-users fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Today's Registrations</h6>
                                    <div class="stats-number"><?php echo $analytics['today_registrations'] ?? 0; ?></div>
                                </div>
                                <i class="fas fa-calendar-day fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">This Week</h6>
                                    <div class="stats-number"><?php echo $analytics['week_registrations'] ?? 0; ?></div>
                                </div>
                                <i class="fas fa-calendar-week fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Vacation Rentals</h6>
                                    <div class="stats-number"><?php echo $analytics['vacation_rentals'] ?? 0; ?></div>
                                </div>
                                <i class="fas fa-home fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="p-4">
                                <p class="text-muted">Recent waitlist registrations will appear here...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registrations Section -->
            <div id="registrations-section" class="dashboard-section" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Waitlist Registrations</h5>
                            </div>
                            <?php if (empty($registrations)) { ?>
                                <div class="p-4 text-center">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No registrations yet</h5>
                                    <p class="text-muted">Registrations will appear here once users start joining your waitlist.</p>
                                </div>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <?php
                                                if (($handle = fopen($file, 'r')) !== false) {
                                                    $header = fgetcsv($handle);
                                                    foreach ($header as $h) {
                                                        echo '<th>'.htmlspecialchars($h).'</th>';
                                                    }
                                                    echo "</tr></thead><tbody>\n";
                                                    while (($row = fgetcsv($handle)) !== false) {
                                                        echo '<tr>';
                                                        foreach ($row as $cell) {
                                                            echo '<td>'.htmlspecialchars($cell).'</td>';
                                                        }
                                                        echo "</tr>\n";
                                                    }
                                                    fclose($handle);
                                                }
                                ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Section -->
            <div id="analytics-section" class="dashboard-section" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Analytics Dashboard</h5>
                            </div>
                            <div class="p-4">
                                <p class="text-muted">Analytics charts and insights will be displayed here...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings-section" class="dashboard-section" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Settings</h5>
                            </div>
                            <div class="p-4">
                                <p class="text-muted">Dashboard settings and preferences will be available here...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.dashboard-section');
            sections.forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';

            // Update active nav link
            const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');

            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // Load notifications when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (window.tenaAjax) {
                // Load initial notifications
                window.tenaAjax.getNotifications(10, true).then(response => {
                    if (response.success) {
                        updateNotificationDropdown(response.data.notifications);
                        updateNotificationCount(response.data.unread_count);
                    }
                });
                
                // Update stats every 30 seconds
                setInterval(() => {
                    window.tenaAjax.updateStatsCards();
                }, 30000);
            }
        });
        
        function updateNotificationDropdown(notifications) {
            const container = document.querySelector('.notification-list');
            if (!container) return;
            
            if (notifications.length === 0) {
                container.innerHTML = '<div class="text-center p-3 text-muted">No notifications</div>';
                return;
            }
            
            container.innerHTML = notifications.map(notif => `
                <li class="notification-item px-3 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-bold">${notif.title}</div>
                            <div class="small text-muted">${notif.message}</div>
                            <div class="small text-muted">${new Date(notif.created_at).toLocaleString()}</div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mark-read" data-notification-id="${notif.id}">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </li>
            `).join('');
        }
        
        function updateNotificationCount(count) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline' : 'none';
            }
        }
        
        // Export functions
        function exportData(format) {
            if (window.tenaAjax) {
                window.tenaAjax.exportData(format).then(response => {
                    if (response.success) {
                        // Show success notification
                        if (window.tenaAjax) {
                            window.tenaAjax.showNotification({
                                type: 'success',
                                title: 'Export Started',
                                message: `Your ${format.toUpperCase()} export has been initiated.`
                            });
                        }
                    }
                });
            }
        }
    </script>
    
    <!-- Include AJAX functionality -->
    <script src="js/ajax.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true,
            mirror: false,
            offset: 100
        });
    </script>
</body>
</html>


<?php
/**
 * Reusable Header Component
 * Can be included in any dashboard page
 */

// Get current user if not already available
if (! isset($currentUser)) {
    $currentUser = getCurrentUser();
}

// Get current page for title
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = ucfirst(str_replace('_', ' ', $currentPage)).' - Tena Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>assets/Tena-logo-square.jpg" rel="icon">
    
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
    <link href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Template Stylesheet -->
    <link href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>css/style.css" rel="stylesheet">
    
    <style>
        /* Dashboard specific styles */
        .dashboard-header {
            background: white;
            padding: 2rem;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--bs-primary);
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--bs-primary);
        }
        
        .data-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .data-table .table thead th {
            background: var(--bs-primary);
            color: #000;
            border: none;
            font-weight: 600;
            padding: 1rem;
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
        
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            border-bottom: 1px solid #eee;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $currentPage)); ?></h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="header-actions d-flex align-items-center">
                        <button class="btn btn-dashboard" onclick="exportData('csv')" id="exportCsvBtn">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </button>
                        <button class="btn btn-dashboard" onclick="exportData('pdf')" id="exportPdfBtn">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-dashboard-outline position-relative" type="button" data-bs-toggle="dropdown">
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
                            <button class="btn btn-dashboard-outline dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($currentUser['username']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="showProfile()"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#" onclick="showSettings()"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

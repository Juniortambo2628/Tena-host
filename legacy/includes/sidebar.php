<?php
/**
 * Reusable Sidebar Component
 * Can be included in any dashboard page
 */

// Get current user if not already available
if (! isset($currentUser)) {
    $currentUser = getCurrentUser();
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar -->
<div id="sidebar" class="dashboard-sidebar overflow-hidden">
    <!-- Sidebar Brand -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center">
            <img src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>assets/Tena-logo-square.jpg" alt="Tena" class="me-2" style="width: 40px; height: 40px;">
            <div>
                <h5 class="mb-0 text-white">Tena Dashboard</h5>
                <small class="text-muted">Waitlist Management</small>
            </div>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : 'admin/'; ?>users.php" class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            User Management
        </a>
        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : 'admin/'; ?>analytics.php" class="nav-link <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            Analytics
        </a>
        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '' : 'admin/'; ?>settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            Settings
        </a>
        <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>index.html" class="nav-link">
            <i class="fas fa-home"></i>
            Back to Website
        </a>
    </nav>

    <!-- User Info -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-2">
                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                </div>
                <div>
                    <div class="text-white fw-bold"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($currentUser['role']); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle d-md-none" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<style>
/* Sidebar Styles */
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
    overflow-y: auto;
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
    text-decoration: none;
    border-radius: 0;
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
    width: 20px;
    margin-right: 0.75rem;
    text-align: center;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem 1.5rem;
    border-top: 1px solid #333;
    background: rgba(0, 0, 0, 0.3);
}

.user-info {
    color: #fff;
}

.sidebar-toggle {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: var(--bs-primary);
    color: #000;
    border: none;
    padding: 0.5rem;
    border-radius: 5px;
    display: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-sidebar {
        transform: translateX(-100%);
    }
    
    .dashboard-sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: block;
    }
}

/* Dashboard content adjustment */
.dashboard-content {
    margin-left: 280px;
    min-height: 100vh;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .dashboard-content {
        margin-left: 0;
    }
}
</style>

<script>
// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
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

// Show section function (for settings)
function showSection(sectionName) {
    // Hide all sections
    const sections = document.querySelectorAll('.dashboard-section');
    sections.forEach(section => {
        section.style.display = 'none';
    });

    // Show selected section
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }

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
</script>

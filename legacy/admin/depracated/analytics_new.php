<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$database = new Database;
$db = $database->getConnection();

// Get analytics data
$analytics = [];
$chartData = [];

try {
    // Overall statistics
    $stats_query = "SELECT 
        COUNT(*) as total_registrations,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days,
        COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals,
        COUNT(CASE WHEN property_type = 'hotel' THEN 1 END) as hotels,
        COUNT(CASE WHEN property_type = 'b&b' THEN 1 END) as bnb,
        COUNT(CASE WHEN property_type = 'other' THEN 1 END) as other
        FROM registrations";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $analytics = $stats_stmt->fetch();

    // Daily registrations for the last 30 days
    $daily_query = 'SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
        FROM registrations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC';
    $daily_stmt = $db->prepare($daily_query);
    $daily_stmt->execute();
    $dailyData = $daily_stmt->fetchAll();

    // Property type distribution
    $property_query = 'SELECT 
        property_type,
        COUNT(*) as count
        FROM registrations 
        GROUP BY property_type
        ORDER BY count DESC';
    $property_stmt = $db->prepare($property_query);
    $property_stmt->execute();
    $propertyData = $property_stmt->fetchAll();

    // Monthly growth
    $monthly_query = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
        FROM registrations 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";
    $monthly_stmt = $db->prepare($monthly_query);
    $monthly_stmt->execute();
    $monthlyData = $monthly_stmt->fetchAll();

} catch (Exception $e) {
    $error = 'Database error: '.$e->getMessage();
}

// Get current user
$currentUser = getCurrentUser();

// Include header with sidebar
include '../includes/header.php';
?>

        <!-- Main Content -->
        <div class="container-fluid px-4">
            <!-- Analytics Section -->
            <div id="analytics-section" class="dashboard-section">
                <!-- Key Metrics -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['total_registrations'] ?? 0); ?></div>
                                    <div class="text-muted">Total Registrations</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-week fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['last_7_days'] ?? 0); ?></div>
                                    <div class="text-muted">Last 7 Days</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-alt fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['last_30_days'] ?? 0); ?></div>
                                    <div class="text-muted">Last 30 Days</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-line fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number">
                                        <?php
                                        $growth = 0;
if (isset($analytics['last_30_days']) && $analytics['last_30_days'] > 0) {
    $previous_30_days = $analytics['total_registrations'] - $analytics['last_30_days'];
    if ($previous_30_days > 0) {
        $growth = round((($analytics['last_30_days'] - $previous_30_days) / $previous_30_days) * 100, 1);
    }
}
echo $growth > 0 ? '+'.$growth.'%' : $growth.'%';
?>
                                    </div>
                                    <div class="text-muted">Growth Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Daily Registrations Chart -->
                    <div class="col-lg-8" data-aos="fade-right" data-aos-delay="100">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Daily Registrations (Last 30 Days)</h5>
                            </div>
                            <div class="p-3">
                                <canvas id="dailyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Property Type Distribution -->
                    <div class="col-lg-4" data-aos="fade-left" data-aos-delay="200">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Property Type Distribution</h5>
                            </div>
                            <div class="p-3">
                                <canvas id="propertyChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Growth Chart -->
                <div class="row g-4 mb-4">
                    <div class="col-12" data-aos="fade-up" data-aos-delay="300">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Monthly Growth (Last 12 Months)</h5>
                            </div>
                            <div class="p-3">
                                <canvas id="monthlyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics -->
                <div class="row g-4">
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Property Type Breakdown</h5>
                            </div>
                            <div class="p-3">
                                <?php foreach ($propertyData as $property) { ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-capitalize"><?php echo str_replace('_', ' ', $property['property_type']); ?></span>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2" style="width: 100px; height: 8px;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo ($property['count'] / $analytics['total_registrations']) * 100; ?>%"></div>
                                        </div>
                                        <span class="fw-bold"><?php echo $property['count']; ?></span>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6" data-aos="fade-up" data-aos-delay="500">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="p-3">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">System Updated</h6>
                                            <p class="text-muted mb-0">Dashboard analytics enhanced with new features</p>
                                            <small class="text-muted">2 hours ago</small>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">New Registration</h6>
                                            <p class="text-muted mb-0">John Doe joined the waitlist</p>
                                            <small class="text-muted">1 day ago</small>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Export Completed</h6>
                                            <p class="text-muted mb-0">CSV export of all registrations completed</p>
                                            <small class="text-muted">3 days ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart data
const dailyData = <?php echo json_encode($dailyData); ?>;
const propertyData = <?php echo json_encode($propertyData); ?>;
const monthlyData = <?php echo json_encode($monthlyData); ?>;

// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(item => new Date(item.date).toLocaleDateString()),
        datasets: [{
            label: 'Registrations',
            data: dailyData.map(item => item.count),
            borderColor: 'rgb(255, 211, 0)',
            backgroundColor: 'rgba(255, 211, 0, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Property Chart
const propertyCtx = document.getElementById('propertyChart').getContext('2d');
new Chart(propertyCtx, {
    type: 'doughnut',
    data: {
        labels: propertyData.map(item => item.property_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
        datasets: [{
            data: propertyData.map(item => item.count),
            backgroundColor: [
                'rgb(255, 211, 0)',
                'rgb(54, 162, 235)',
                'rgb(255, 99, 132)',
                'rgb(75, 192, 192)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: monthlyData.map(item => new Date(item.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' })),
        datasets: [{
            label: 'Registrations',
            data: monthlyData.map(item => item.count),
            backgroundColor: 'rgba(255, 211, 0, 0.8)',
            borderColor: 'rgb(255, 211, 0)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid var(--bs-primary);
}
</style>

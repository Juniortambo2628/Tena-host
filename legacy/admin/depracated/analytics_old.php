<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$database = new Database;
$db = $database->getConnection();

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get analytics data
try {
    // Registration trends (last 30 days)
    $trends_query = 'SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as registrations
                      FROM registrations 
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                      GROUP BY DATE(created_at)
                      ORDER BY date ASC';
    $trends_stmt = $db->prepare($trends_query);
    $trends_stmt->bindParam(':date_from', $date_from);
    $trends_stmt->bindParam(':date_to', $date_to);
    $trends_stmt->execute();
    $trends_data = $trends_stmt->fetchAll();

    // Property type distribution
    $property_query = 'SELECT 
                         property_type,
                         COUNT(*) as count,
                         ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM registrations), 2) as percentage
                       FROM registrations 
                       WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                       GROUP BY property_type
                       ORDER BY count DESC';
    $property_stmt = $db->prepare($property_query);
    $property_stmt->bindParam(':date_from', $date_from);
    $property_stmt->bindParam(':date_to', $date_to);
    $property_stmt->execute();
    $property_data = $property_stmt->fetchAll();

    // Referral source analysis
    $referral_query = "SELECT 
                         COALESCE(referral_source, 'Unknown') as source,
                         COUNT(*) as count
                       FROM registrations 
                       WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                       GROUP BY referral_source
                       ORDER BY count DESC";
    $referral_stmt = $db->prepare($referral_query);
    $referral_stmt->bindParam(':date_from', $date_from);
    $referral_stmt->bindParam(':date_to', $date_to);
    $referral_stmt->execute();
    $referral_data = $referral_stmt->fetchAll();

    // Status distribution
    $status_query = 'SELECT 
                       status,
                       COUNT(*) as count
                     FROM registrations 
                     WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                     GROUP BY status';
    $status_stmt = $db->prepare($status_query);
    $status_stmt->bindParam(':date_from', $date_from);
    $status_stmt->bindParam(':date_to', $date_to);
    $status_stmt->execute();
    $status_data = $status_stmt->fetchAll();

    // Monthly growth
    $growth_query = 'SELECT 
                       YEAR(created_at) as year,
                       MONTH(created_at) as month,
                       COUNT(*) as registrations
                     FROM registrations 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                     GROUP BY YEAR(created_at), MONTH(created_at)
                     ORDER BY year, month';
    $growth_stmt = $db->prepare($growth_query);
    $growth_stmt->execute();
    $growth_data = $growth_stmt->fetchAll();

    // Top locations
    $location_query = "SELECT 
                         location,
                         COUNT(*) as count
                       FROM registrations 
                       WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                         AND location IS NOT NULL 
                         AND location != ''
                       GROUP BY location
                       ORDER BY count DESC
                       LIMIT 10";
    $location_stmt = $db->prepare($location_query);
    $location_stmt->bindParam(':date_from', $date_from);
    $location_stmt->bindParam(':date_to', $date_to);
    $location_stmt->execute();
    $location_data = $location_stmt->fetchAll();

} catch (Exception $e) {
    $error = 'Database error: '.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Tena Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 1rem 0;
        }
        .metric-card {
            background: linear-gradient(135deg, var(--bs-primary) 0%, #e6c200 100%);
            color: #000;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .metric-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .progress-custom {
            height: 1rem;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        .progress-custom .progress-bar {
            background: linear-gradient(90deg, var(--bs-primary) 0%, #e6c200 100%);
            border-radius: 10px;
        }
    </style>
</head>
<body style="background: #f8f9fa; padding: 2rem;">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Analytics Dashboard</h2>
                        <p class="text-muted">Comprehensive insights into your waitlist performance</p>
                    </div>
                    <div>
                        <a href="../dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button class="btn btn-primary" onclick="exportAnalytics()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="analytics-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Date Range Filter</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number"><?php echo array_sum(array_column($trends_data, 'registrations')); ?></div>
                    <div class="metric-label">Total Registrations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number"><?php echo count($trends_data); ?></div>
                    <div class="metric-label">Active Days</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number"><?php echo count($property_data); ?></div>
                    <div class="metric-label">Property Types</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number"><?php echo count($referral_data); ?></div>
                    <div class="metric-label">Referral Sources</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Registration Trends -->
            <div class="col-md-8">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Registration Trends</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Property Type Distribution -->
            <div class="col-md-4">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Property Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="propertyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Row -->
        <div class="row mb-4">
            <!-- Referral Sources -->
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i>Referral Sources</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($referral_data as $ref) { ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?php echo htmlspecialchars($ref['source']); ?></span>
                                    <span class="fw-bold"><?php echo $ref['count']; ?></span>
                                </div>
                                <div class="progress progress-custom">
                                    <div class="progress-bar" style="width: <?php echo ($ref['count'] / max(array_column($referral_data, 'count'))) * 100; ?>%"></div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Status Distribution -->
            <div class="col-md-6">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Locations -->
        <div class="row">
            <div class="col-12">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Top Locations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($location_data as $loc) { ?>
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                        <div>
                                            <strong><?php echo htmlspecialchars($loc['location']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $loc['count']; ?> registrations</small>
                                        </div>
                                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Registration Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'".implode("','", array_column($trends_data, 'date'))."'"; ?>],
                datasets: [{
                    label: 'Registrations',
                    data: [<?php echo implode(',', array_column($trends_data, 'registrations')); ?>],
                    borderColor: '#FFD300',
                    backgroundColor: 'rgba(255, 211, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Property Type Chart
        const propertyCtx = document.getElementById('propertyChart').getContext('2d');
        new Chart(propertyCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'".implode("','", array_map(function ($p) {
                    return ucwords(str_replace('_', ' ', $p['property_type']));
                }, $property_data))."'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($property_data, 'count')); ?>],
                    backgroundColor: [
                        '#FFD300',
                        '#e6c200',
                        '#ccb300',
                        '#b39900',
                        '#998000'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo "'".implode("','", array_map(function ($s) {
                    return ucfirst($s['status']);
                }, $status_data))."'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($status_data, 'count')); ?>],
                    backgroundColor: '#FFD300',
                    borderColor: '#e6c200',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        function exportAnalytics() {
            // Implement analytics export
            alert('Analytics export functionality would be implemented here');
        }
    </script>
</body>
</html>

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
                <!-- Controls: date range + property filter -->
                <div id="analytics-controls">
                    <input id="analyticsDateRange" class="form-control" placeholder="Select date range" />
                    <select id="propertyFilter" class="form-select">
                        <option value="">All Properties</option>
                        <option value="vacation_rental">Vacation Rental</option>
                        <option value="hotel">Hotel</option>
                        <option value="b&b">Bed & Breakfast</option>
                        <option value="other">Other</option>
                    </select>
                    <button id="refreshAnalytics" class="btn btn-dashboard">Refresh</button>
                </div>

                <!-- Key Metrics -->
                <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="stats-number"><?php echo number_format($analytics['total_registrations'] ?? 0); ?></div>
                                            <div class="text-muted">Total Registrations</div>
                                        </div>
                                        <canvas id="spark_total" width="80" height="30" class="ms-3"></canvas>
                                    </div>
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
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="stats-number"><?php echo number_format($analytics['last_7_days'] ?? 0); ?></div>
                                            <div class="text-muted">Last 7 Days</div>
                                        </div>
                                        <canvas id="spark_7d" width="80" height="30" class="ms-3"></canvas>
                                    </div>
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
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="stats-number"><?php echo number_format($analytics['last_30_days'] ?? 0); ?></div>
                                            <div class="text-muted">Last 30 Days</div>
                                        </div>
                                        <canvas id="spark_30d" width="80" height="30" class="ms-3"></canvas>
                                    </div>
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
                                    <div class="d-flex align-items-center">
                                        <div>
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
                                        <canvas id="spark_growth" width="80" height="30" class="ms-3"></canvas>
                                    </div>
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

<!-- Chart.js + zoom plugin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.1/dist/chartjs-plugin-zoom.min.js"></script>

<!-- Date range picker (flatpickr) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Path prefix: when under /admin/ we need to go up one level to reach root `/api/`
const APP_PREFIX = '<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>';
// Helper to format dates (YYYY-MM-DD)
function formatDateISO(d) {
    const dt = new Date(d);
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const day = String(dt.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

let charts = {};

async function fetchAnalytics(start, end, propertyType = '') {
    const qs = new URLSearchParams({ start, end });
    if (propertyType) qs.set('property_type', propertyType);
    const res = await fetch(`${APP_PREFIX}api/analytics_data.php?${qs.toString()}`, { credentials: 'same-origin' });
    if (!res.ok) throw new Error('Failed to load analytics');
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'API error');
    return payload.data;
}

function createOrUpdateLineChart(ctx, id, labels, data, color) {
    if (charts[id]) {
        charts[id].data.labels = labels;
        charts[id].data.datasets[0].data = data;
        charts[id].update();
        return charts[id];
    }

    const cfg = {
        type: 'line',
        data: { labels, datasets: [{ label: 'Registrations', data, borderColor: color, backgroundColor: color, tension: 0.3, fill: true }] },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} registrations`
                    }
                },
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' }
                }
            },
            interaction: { mode: 'index', intersect: false },
            onClick: (evt, elements) => {
                if (!elements.length) return;
                const idx = elements[0].index;
                const date = charts[id].data.labels[idx];
                // open drilldown
                openDrilldown(date);
            },
            scales: { y: { beginAtZero: true } }
        }
    };

    charts[id] = new Chart(ctx, cfg);
    return charts[id];
}

function createOrUpdateDoughnut(ctx, id, labels, data) {
    if (charts[id]) {
        charts[id].data.labels = labels;
        charts[id].data.datasets[0].data = data;
        charts[id].update();
        return charts[id];
    }

    charts[id] = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: ['rgb(255,211,0)','rgb(54,162,235)','rgb(255,99,132)','rgb(75,192,192)'] }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed}` } } } }
    });
    return charts[id];
}

function openDrilldown(date) {
    // Show modal and fetch details for date
    const modal = document.getElementById('drilldownModal');
    const title = modal.querySelector('.modal-title');
    const body = modal.querySelector('.modal-body');
    title.textContent = `Registrations for ${date}`;
    body.innerHTML = '<div class="text-center p-4">Loading...</div>';
    fetch(`${APP_PREFIX}admin/api/registrations_by_date.php?date=${encodeURIComponent(date)}`, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(payload => {
            if (!payload.success) throw new Error(payload.message || 'Failed');
            const rows = payload.data;
            if (!rows.length) return body.innerHTML = '<div class="p-3">No registrations for this date</div>';
            const table = document.createElement('table');
            table.className = 'table table-sm';
            table.innerHTML = '<thead><tr><th>Name</th><th>Email</th><th>Property Type</th><th>Created</th></tr></thead>';
            const tb = document.createElement('tbody');
            rows.forEach(r => { tb.innerHTML += `<tr><td>${r.first_name} ${r.last_name}</td><td>${r.email}</td><td>${r.property_type}</td><td>${r.created_at}</td></tr>`; });
            table.appendChild(tb);
            body.innerHTML = ''; body.appendChild(table);
        }).catch(err => { body.innerHTML = `<div class="p-3 text-danger">${err.message}</div>`; });
    new bootstrap.Modal(modal).show();
}

async function init() {
    // setup date picker
    const drInput = document.getElementById('analyticsDateRange');
    const today = new Date();
    const thirty = new Date(); thirty.setDate(thirty.getDate() - 29);
    if (drInput && typeof flatpickr === 'function') {
        flatpickr(drInput, { mode: 'range', dateFormat: 'Y-m-d', defaultDate: [thirty, today], onClose: onDateChange });
    }

    // load initial data
    let startDate = formatDateISO(thirty);
    let endDate = formatDateISO(today);
    if (drInput && drInput.value) {
        const parts = drInput.value.split(' to ');
        if (parts[0]) startDate = parts[0];
        if (parts[1]) endDate = parts[1];
    }
    await reloadCharts(startDate, endDate);

    // filters
    const propFilter = document.getElementById('propertyFilter');
    if (propFilter) {
        propFilter.addEventListener('change', async () => {
            const dr = document.getElementById('analyticsDateRange');
            const [s, e] = dr && dr.value ? dr.value.split(' to ') : [startDate, endDate];
            await reloadCharts(s || startDate, e || endDate, propFilter.value);
        });
    }

    const refreshBtn = document.getElementById('refreshAnalytics');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            const dr = document.getElementById('analyticsDateRange');
            const [s, e] = dr && dr.value ? dr.value.split(' to ') : [startDate, endDate];
            const prop = document.getElementById('propertyFilter') ? document.getElementById('propertyFilter').value : '';
            await reloadCharts(s || startDate, e || endDate, prop);
        });
    }
}

async function onDateChange(selectedDates) {
    if (!selectedDates || selectedDates.length < 2) return;
    const start = formatDateISO(selectedDates[0]);
    const end = formatDateISO(selectedDates[1]);
    await reloadCharts(start, end, document.getElementById('propertyFilter').value);
}

async function reloadCharts(start, end, propertyType = '') {
    try {
        const data = await fetchAnalytics(start, end, propertyType);
        // daily
        const dailyLabels = data.daily.map(d => d.date);
        const dailyCounts = data.daily.map(d => parseInt(d.count, 10));
        createOrUpdateLineChart(document.getElementById('dailyChart').getContext('2d'), 'daily', dailyLabels, dailyCounts, 'rgb(255,211,0)');

        // property
        const propLabels = data.property.map(p => p.property_type.replace('_',' ').replace(/\b\w/g, l => l.toUpperCase()));
        const propCounts = data.property.map(p => parseInt(p.count,10));
        createOrUpdateDoughnut(document.getElementById('propertyChart').getContext('2d'), 'property', propLabels, propCounts);

        // monthly
        const monthLabels = data.monthly.map(m => new Date(m.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
        const monthCounts = data.monthly.map(m => parseInt(m.count,10));
        createOrUpdateLineChart(document.getElementById('monthlyChart').getContext('2d'), 'monthly', monthLabels, monthCounts, 'rgba(255,211,0)');

    } catch (err) {
        console.error('Reload charts error', err);
        alert('Unable to load analytics: ' + err.message);
    }
}

document.addEventListener('DOMContentLoaded', init);
</script>

<!-- Drilldown modal -->
<div class="modal fade" id="drilldownModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Loading...</div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<style>
.timeline { position: relative; padding-left: 30px; }
.timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
.timeline-item { position: relative; margin-bottom: 20px; }
.timeline-marker { position: absolute; left: -22px; top: 5px; width: 12px; height: 12px; border-radius: 50%; }
.timeline-content { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 3px solid var(--bs-primary); }

/* Analytics controls */
#analytics-controls { display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem; }
#analytics-controls .form-control, #analytics-controls .btn { height:40px; }
#analytics-section .data-table { min-height: 120px; }
</style>

<!-- KPI sparklines and export button handler -->
<script>
async function fetchSparkline(start, end) {
    const res = await fetch(`${APP_PREFIX}api/analytics_data.php?start=${start}&end=${end}`, { credentials: 'same-origin' });
    if (!res.ok) return null;
    const payload = await res.json();
    return payload.data;
}

function drawSparkline(canvas, values, color='rgb(0,0,0)') {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.width; // clear
    const w = canvas.width; const h = canvas.height;
    const max = Math.max(...values, 1); const min = Math.min(...values, 0);
    ctx.strokeStyle = color; ctx.lineWidth = 1.5; ctx.beginPath();
    values.forEach((v,i)=>{
        const x = (i/(values.length-1 || 1)) * w;
        const y = h - ((v - min)/(max - min || 1)) * h;
        if (i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
    });
    ctx.stroke();
}

async function initSparklines() {
    const dr = document.getElementById('analyticsDateRange');
    let start = null, end = null;
    if (dr && dr.value) {
        const parts = dr.value.split(' to ');
        start = parts[0]; end = parts[1];
    }
    // fallback to 30-day window
    if (!start || !end) {
        const today = new Date(); const thirty = new Date(); thirty.setDate(thirty.getDate() - 29);
        start = formatDateISO(thirty); end = formatDateISO(today);
    }
    const data = await fetchSparkline(start, end);
    if (!data) return;

    // Use last 14 days for sparklines
    const daily = data.daily.slice(-14).map(d => parseInt(d.count,10));
    drawSparkline(document.getElementById('spark_total'), daily, 'rgb(0,123,255)');
    drawSparkline(document.getElementById('spark_7d'), daily.slice(-7), 'rgb(40,167,69)');
    drawSparkline(document.getElementById('spark_30d'), data.daily.map(d=>parseInt(d.count,10)), 'rgb(23,162,184)');
    drawSparkline(document.getElementById('spark_growth'), data.monthly.map(m=>parseInt(m.count,10)), 'rgb(255,193,7)');
}

// Export filtered CSV
const exportBtn = document.getElementById('exportCsvBtn');
if (exportBtn) {
    exportBtn.addEventListener('click', () => {
        const dr = document.getElementById('analyticsDateRange');
        const drVals = dr && dr.value ? dr.value.split(' to ') : [];
        const propEl = document.getElementById('propertyFilter');
        const prop = propEl ? propEl.value : '';
        const params = new URLSearchParams({ start: drVals[0] || '', end: drVals[1] || '' });
        if (prop) params.set('property_type', prop);
        window.location = `${APP_PREFIX}admin/api/analytics_export.php?${params.toString()}`;
    });
}

// Re-init sparklines when charts reload
const originalReload = reloadCharts;
reloadCharts = async (start, end, propertyType='') => {
    await originalReload(start, end, propertyType);
    await initSparklines();
};

document.addEventListener('DOMContentLoaded', () => { if (typeof initSparklines === 'function') initSparklines(); });
</script>

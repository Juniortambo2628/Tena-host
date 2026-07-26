<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

$database = new Database;
$db = $database->getConnection();

// Handle filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'property_type' => $_GET['property_type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'created_at',
    'sort_order' => $_GET['sort_order'] ?? 'DESC',
    'page' => (int) ($_GET['page'] ?? 1),
    'per_page' => (int) ($_GET['per_page'] ?? 25),
];

// Build query with filters
$where_conditions = [];
$params = [];

if (! empty($filters['search'])) {
    $where_conditions[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%'.$filters['search'].'%';
}

if (! empty($filters['property_type'])) {
    $where_conditions[] = 'property_type = :property_type';
    $params[':property_type'] = $filters['property_type'];
}

if (! empty($filters['status'])) {
    $where_conditions[] = 'status = :status';
    $params[':status'] = $filters['status'];
}

if (! empty($filters['date_from'])) {
    $where_conditions[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $filters['date_from'];
}

if (! empty($filters['date_to'])) {
    $where_conditions[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $filters['date_to'];
}

$where_clause = ! empty($where_conditions) ? 'WHERE '.implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM registrations $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $filters['per_page']);

// Get registrations with pagination
$offset = ($filters['page'] - 1) * $filters['per_page'];
$query = "SELECT * FROM registrations $where_clause ORDER BY {$filters['sort_by']} {$filters['sort_order']} LIMIT :offset, :per_page";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $filters['per_page'], PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll();

// Get property type counts for analytics
$analytics_query = 'SELECT property_type, COUNT(*) as count FROM registrations GROUP BY property_type';
$analytics_stmt = $db->prepare($analytics_query);
$analytics_stmt->execute();
$property_analytics = $analytics_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Tena Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .table thead th {
            background: var(--bs-primary);
            color: #000;
            border: none;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 211, 0, 0.05);
        }
        .badge-custom {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
        }
        .pagination-custom .page-link {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        .pagination-custom .page-item.active .page-link {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #000;
        }
        .export-buttons {
            position: sticky;
            top: 0;
            z-index: 20;
            background: white;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
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
                        <h2>User Management</h2>
                        <p class="text-muted">Manage waitlist registrations and user data</p>
                    </div>
                    <div>
                        <a href="../dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button class="btn btn-primary" onclick="exportFilteredData('csv')">
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </button>
                        <button class="btn btn-primary" onclick="exportFilteredData('pdf')">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters & Search</h5>
            </div>
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                   placeholder="Name or email...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Property Type</label>
                            <select class="form-select" name="property_type">
                                <option value="">All Types</option>
                                <option value="vacation_rental" <?php echo $filters['property_type'] === 'vacation_rental' ? 'selected' : ''; ?>>Vacation Rental</option>
                                <option value="hotel" <?php echo $filters['property_type'] === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                                <option value="b&b" <?php echo $filters['property_type'] === 'b&b' ? 'selected' : ''; ?>>B&B</option>
                                <option value="other" <?php echo $filters['property_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="converted" <?php echo $filters['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort_by">
                                <option value="created_at" <?php echo $filters['sort_by'] === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                                <option value="first_name" <?php echo $filters['sort_by'] === 'first_name' ? 'selected' : ''; ?>>First Name</option>
                                <option value="last_name" <?php echo $filters['sort_by'] === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                                <option value="email" <?php echo $filters['sort_by'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="property_type" <?php echo $filters['sort_by'] === 'property_type' ? 'selected' : ''; ?>>Property Type</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <select class="form-select" name="sort_order">
                                <option value="DESC" <?php echo $filters['sort_order'] === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $filters['sort_order'] === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Per Page</label>
                            <select class="form-select" name="per_page">
                                <option value="25" <?php echo $filters['per_page'] === 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $filters['per_page'] === 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $filters['per_page'] === 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="exportCurrentView()">
                                    <i class="fas fa-download me-2"></i>Export Current View
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Showing <?php echo count($registrations); ?> of <?php echo $total_records; ?> registrations
                    (Page <?php echo $filters['page']; ?> of <?php echo $total_pages; ?>)
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Property Type</th>
                            <th>Location</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registrations)) { ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No registrations found</h5>
                                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($registrations as $reg) { ?>
                                <tr>
                                    <td><?php echo $reg['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reg['first_name'].' '.$reg['last_name']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($reg['email']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($reg['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucwords(str_replace('_', ' ', $reg['property_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg['country'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars((($reg['country_code'] ?? '').' '.($reg['phone_number'] ?? '')) ?: 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $status_class = match ($reg['status']) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-warning',
                                            'converted' => 'bg-primary',
                                            default => 'bg-secondary'
                                        };
                                ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($reg['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($reg['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewUser(<?php echo $reg['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="editUser(<?php echo $reg['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1) { ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-custom justify-content-center">
                            <?php if ($filters['page'] > 1) { ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php } ?>
                            
                            <?php for ($i = max(1, $filters['page'] - 2); $i <= min($total_pages, $filters['page'] + 2); $i++) { ?>
                                <li class="page-item <?php echo $i === $filters['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php } ?>
                            
                            <?php if ($filters['page'] < $total_pages) { ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = 'users.php';
        }

        function exportFilteredData(format) {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            formData.append('export', format);
            
            const params = new URLSearchParams(formData);
            window.open('export.php?' + params.toString(), '_blank');
        }

        function exportCurrentView() {
            exportFilteredData('csv');
        }

        function viewUser(id) {
            // Implement user view modal
            alert('View user ' + id);
        }

        function editUser(id) {
            // Implement user edit modal
            alert('Edit user ' + id);
        }
    </script>
</body>
</html>

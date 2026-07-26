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
    $search_value = '%'.$filters['search'].'%';
    $where_conditions[] = '(first_name LIKE :search1 OR last_name LIKE :search2 OR email LIKE :search3)';
    $params[':search1'] = $search_value;
    $params[':search2'] = $search_value;
    $params[':search3'] = $search_value;
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
$query = "SELECT * FROM registrations $where_clause ORDER BY {$filters['sort_by']} {$filters['sort_order']} LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registrations = $stmt->fetchAll();

// Check if this is an AJAX request
$isAjax = ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');

    // Render table rows HTML
    ob_start();
    foreach ($registrations as $registration) { ?>
        <tr data-id="<?php echo $registration['id']; ?>">
            <td class="col-name">
                <div class="d-flex align-items-center">
                    <div class="avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                        <?php echo strtoupper(substr($registration['first_name'], 0, 1).substr($registration['last_name'], 0, 1)); ?>
                    </div>
                    <div class="name-cell">
                        <div class="fw-bold name-text"><?php echo htmlspecialchars($registration['first_name'].' '.$registration['last_name']); ?></div>
                        <small class="text-muted">ID: <?php echo $registration['id']; ?></small>
                    </div>
                </div>
            </td>
            <td class="col-email">
                <div class="email-text">
                    <a href="mailto:<?php echo htmlspecialchars($registration['email']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($registration['email']); ?>
                    </a>
                </div>
            </td>
            <td class="col-type">
                <span class="badge bg-secondary badge-sm">
                    <?php echo ucfirst(str_replace('_', ' ', $registration['property_type'])); ?>
                </span>
            </td>
            <td class="col-location">
                <div class="location-text"><?php echo htmlspecialchars($registration['country'] ?: 'Not specified'); ?></div>
            </td>
            <td class="col-date">
                <div class="date-text"><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></div>
            </td>
            <td class="col-status">
                <span class="badge bg-<?php echo $registration['status'] === 'active' ? 'success' : ($registration['status'] === 'inactive' ? 'warning' : 'primary'); ?> badge-sm">
                    <?php echo ucfirst($registration['status']); ?>
                </span>
            </td>
            <td class="col-actions">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="viewUser(<?php echo $registration['id']; ?>)">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="editUser(<?php echo $registration['id']; ?>)">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $registration['id']; ?>)">
                            <i class="fas fa-trash me-2"></i>Delete
                        </a></li>
                    </ul>
                </div>
            </td>
        </tr>
    <?php }
    $tableHtml = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $tableHtml,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $filters['page'],
    ]);
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Include header with sidebar
include '../includes/header.php';
?>

        <!-- Main Content -->
        <div class="container-fluid px-4">
            <!-- User Management Section -->
            <div id="users-section" class="dashboard-section">
                <!-- Filters -->
                <div class="data-table mb-4">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">User Management</h5>
                    </div>
                    <div class="p-3">
                        <form method="GET" class="row g-3" id="searchForm">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" id="searchInput" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Name or email..." autocomplete="on">
                                <div class="search-loading d-none">
                                    <small class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Searching...</small>
                                </div>
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
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Per Page</label>
                                <select class="form-select" name="per_page" id="perPageSelect">
                                    <option value="10" <?php echo $filters['per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $filters['per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $filters['per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $filters['per_page'] == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results -->
                <div class="data-table">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Users (<?php echo number_format($total_records); ?> total)</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="fas fa-download me-2"></i>Export & Configure
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($registrations)) { ?>
                        <div class="p-4 text-center">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No users found</h5>
                            <p class="text-muted">Try adjusting your filters or check back later.</p>
                        </div>
                    <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-compact" id="usersTable">
                                <thead>
                                    <tr>
                                        <th class="col-name">
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'first_name', 'sort_order' => $filters['sort_by'] === 'first_name' && $filters['sort_order'] === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none">
                                                Name
                                                <?php if ($filters['sort_by'] === 'first_name') { ?>
                                                    <i class="fas fa-sort-<?php echo $filters['sort_order'] === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php } ?>
                                            </a>
                                        </th>
                                        <th class="col-email">
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'email', 'sort_order' => $filters['sort_by'] === 'email' && $filters['sort_order'] === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none">
                                                Email
                                                <?php if ($filters['sort_by'] === 'email') { ?>
                                                    <i class="fas fa-sort-<?php echo $filters['sort_order'] === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php } ?>
                                            </a>
                                        </th>
                                        <th class="col-type">Type</th>
                                        <th class="col-location">Location</th>
                                        <th class="col-date">
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'created_at', 'sort_order' => $filters['sort_by'] === 'created_at' && $filters['sort_order'] === 'ASC' ? 'DESC' : 'ASC'])); ?>" class="text-decoration-none">
                                                Registered
                                                <?php if ($filters['sort_by'] === 'created_at') { ?>
                                                    <i class="fas fa-sort-<?php echo $filters['sort_order'] === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php } ?>
                                            </a>
                                        </th>
                                        <th class="col-status">Status</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <?php foreach ($registrations as $registration) { ?>
                                    <tr data-id="<?php echo $registration['id']; ?>">
                                        <td class="col-name">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <?php echo strtoupper(substr($registration['first_name'], 0, 1).substr($registration['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="name-cell">
                                                    <div class="fw-bold name-text"><?php echo htmlspecialchars($registration['first_name'].' '.$registration['last_name']); ?></div>
                                                    <small class="text-muted">ID: <?php echo $registration['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-email">
                                            <div class="email-text">
                                                <a href="mailto:<?php echo htmlspecialchars($registration['email']); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($registration['email']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="col-type">
                                            <span class="badge bg-secondary badge-sm">
                                                <?php echo ucfirst(str_replace('_', ' ', $registration['property_type'])); ?>
                                            </span>
                                        </td>
                                        <td class="col-location">
                                            <div class="location-text"><?php echo htmlspecialchars($registration['country'] ?: 'Not specified'); ?></div>
                                        </td>
                                        <td class="col-date">
                                            <div class="date-text"><?php echo date('M j, Y', strtotime($registration['created_at'])); ?></div>
                                        </td>
                                        <td class="col-status">
                                            <span class="badge bg-<?php echo $registration['status'] === 'active' ? 'success' : 'secondary'; ?> badge-sm">
                                                <?php echo ucfirst($registration['status']); ?>
                                            </span>
                                        </td>
                                        <td class="col-actions">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="#" onclick="viewUser(<?php echo $registration['id']; ?>)">
                                                        <i class="fas fa-eye me-2"></i>View Details
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="contactUser(<?php echo $registration['id']; ?>)">
                                                        <i class="fas fa-envelope me-2"></i>Contact User
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="editUser(<?php echo $registration['id']; ?>)">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $registration['id']; ?>)">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1) { ?>
                        <div class="p-3 border-top">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        Showing <?php echo (($filters['page'] - 1) * $filters['per_page']) + 1; ?> to 
                                        <?php echo min($filters['page'] * $filters['per_page'], $total_records); ?> of 
                                        <?php echo number_format($total_records); ?> entries
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="User pagination">
                                        <ul class="pagination justify-content-end mb-0 pagination-sm">
                                            <?php if ($filters['page'] > 1) { ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                                                        <i class="fas fa-angle-double-left"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $filters['page'] - 1])); ?>">
                                                        <i class="fas fa-angle-left"></i>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                            
                                            <?php for ($i = max(1, $filters['page'] - 2); $i <= min($total_pages, $filters['page'] + 2); $i++) { ?>
                                                <li class="page-item <?php echo $i === $filters['page'] ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php } ?>
                                            
                                            <?php if ($filters['page'] < $total_pages) { ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $filters['page'] + 1])); ?>">
                                                        <i class="fas fa-angle-right"></i>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                                        <i class="fas fa-angle-double-right"></i>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- View User Modal -->
        <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="viewUserContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h6 class="text-muted">Loading user details...</h6>
                            <p class="small text-muted">Please wait while we fetch the information</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="contactUserFromModal()">
                            <i class="fas fa-envelope me-2"></i>Contact User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact User Modal -->
        <div class="modal fade" id="contactUserModal" tabindex="-1" aria-labelledby="contactUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactUserModalLabel">Contact User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="contactUserForm">
                            <input type="hidden" id="contactUserId" name="user_id">
                            <div class="mb-3">
                                <label for="contactSubject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="contactSubject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label">Message</label>
                                <textarea class="form-control" id="contactMessage" name="message" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="contactType" class="form-label">Contact Type</label>
                                <select class="form-select" id="contactType" name="type" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="phone">Phone Call</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendContactMessage()">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm">
                            <input type="hidden" id="editUserId" name="user_id">
                            <div class="mb-3">
                                <label for="editFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="editPropertyType" class="form-label">Property Type</label>
                                <select class="form-select" id="editPropertyType" name="property_type" required>
                                    <option value="vacation_rental">Vacation Rental</option>
                                    <option value="hotel">Hotel</option>
                                    <option value="b&b">B&B</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="editLocation" name="location">
                            </div>
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveUserChanges()">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>
<script src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? './users.js' : 'admin/users.js'; ?>"></script>

<script>
// User management functions
let currentUserId = null;

function viewUser(id) {
    currentUserId = id;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    modal.show();
    
    // Load user details
    loadUserDetails(id);
}

function loadUserDetails(id) {
    const content = document.getElementById('viewUserContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="text-muted">Loading user details...</h6>
            <p class="small text-muted">Please wait while we fetch the information</p>
        </div>
    `;
    
    // Simulate API call - in real implementation, this would call the backend
    setTimeout(() => {
        // For now, we'll use the data from the table
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            const cells = row.querySelectorAll('td');
            const name = cells[0].textContent.trim();
            const email = cells[1].textContent.trim();
            const propertyType = cells[2].textContent.trim();
            const location = cells[3].textContent.trim();
            const date = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Personal Information</h6>
                        <p><strong>Name:</strong> ${name}</p>
                        <p><strong>Email:</strong> <a href="mailto:${email}">${email}</a></p>
                        <p><strong>Location:</strong> ${location}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Property Information</h6>
                        <p><strong>Property Type:</strong> ${propertyType}</p>
                        <p><strong>Registration Date:</strong> ${date}</p>
                        <p><strong>Status:</strong> ${status}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-muted">Additional Information</h6>
                        <p class="text-muted">No additional information available at this time.</p>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    User details not found.
                </div>
            `;
        }
    }, 1000);
}

function contactUser(id) {
    currentUserId = id;
    
    // Pre-fill form with user data
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        const cells = row.querySelectorAll('td');
        const name = cells[0].textContent.trim();
        const email = cells[1].textContent.trim();
        
        document.getElementById('contactUserId').value = id;
        document.getElementById('contactSubject').value = `Re: Your Tena Waitlist Registration`;
        document.getElementById('contactMessage').value = `Hello ${name},\n\nThank you for joining the Tena waitlist! We're excited to have you on board.\n\nBest regards,\nThe Tena Team`;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('contactUserModal'));
    modal.show();
}

function contactUserFromModal() {
    if (currentUserId) {
        contactUser(currentUserId);
    }
}

function sendContactMessage() {
    const form = document.getElementById('contactUserForm');
    const formData = new FormData(form);
    
    // Show loading state
    const sendBtn = document.querySelector('[onclick="sendContactMessage()"]');
    const originalText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    sendBtn.disabled = true;
    
    // Simulate sending message
    setTimeout(() => {
        // Show success notification
        if (window.tenaAjax) {
            window.tenaAjax.showNotification({
                type: 'success',
                title: 'Message Sent',
                message: 'Your message has been sent successfully!'
            });
        }
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('contactUserModal'));
        modal.hide();
        
        // Reset form
        form.reset();
        
        // Reset button
        sendBtn.innerHTML = originalText;
        sendBtn.disabled = false;
    }, 2000);
}

function editUser(id) {
    currentUserId = id;
    
    // Pre-fill form with user data
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        const cells = row.querySelectorAll('td');
        const name = cells[0].textContent.trim();
        const email = cells[1].textContent.trim();
        const propertyType = cells[2].textContent.trim();
        const location = cells[3].textContent.trim();
        const status = cells[5].textContent.trim();
        
        // Extract first and last name
        const nameParts = name.split(' ');
        const firstName = nameParts[0] || '';
        const lastName = nameParts.slice(1).join(' ') || '';
        
        document.getElementById('editUserId').value = id;
        document.getElementById('editFirstName').value = firstName;
        document.getElementById('editLastName').value = lastName;
        document.getElementById('editEmail').value = email;
        document.getElementById('editPropertyType').value = propertyType.toLowerCase().replace(' ', '_');
        document.getElementById('editLocation').value = location;
        document.getElementById('editStatus').value = status.toLowerCase();
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function saveUserChanges() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    // Show loading state
    const saveBtn = document.querySelector('[onclick="saveUserChanges()"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    saveBtn.disabled = true;
    
    // Simulate saving changes
    setTimeout(() => {
        // Show success notification
        if (window.tenaAjax) {
            window.tenaAjax.showNotification({
                type: 'success',
                title: 'User Updated',
                message: 'User information has been updated successfully!'
            });
        }
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
        modal.hide();
        
        // Reset button
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        
        // Reload page to show changes
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }, 2000);
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        // Show loading state
        const deleteBtn = document.querySelector(`[onclick="deleteUser(${id})"]`);
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
        deleteBtn.disabled = true;
        
        // Simulate deletion
        setTimeout(() => {
            // Show success notification
            if (window.tenaAjax) {
                window.tenaAjax.showNotification({
                    type: 'success',
                    title: 'User Deleted',
                    message: 'User has been deleted successfully!'
                });
            }
            
            // Remove row from table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.remove();
            }
            
            // Reset button
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }, 2000);
    }
}

// Live search functionality
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const searchLoading = document.querySelector('.search-loading');
const usersTableBody = document.getElementById('usersTableBody');

// Live search implementation
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    // Show loading indicator immediately for responsive feel
    searchLoading.classList.remove('d-none');
    
    if (query.length === 0) {
        // If query is empty, show all results quickly
        searchTimeout = setTimeout(() => {
            performSearch('');
        }, 200);
        return;
    }
    
    if (query.length < 2) {
        // If query is too short, wait a bit longer
        searchLoading.classList.add('d-none');
        return;
    }
    
    // Debounce search to avoid too many requests (reduced to 300ms for faster feel)
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
});

// Per-page change handler
document.getElementById('perPageSelect').addEventListener('change', function() {
    const form = document.getElementById('searchForm');
    form.submit();
});

// Perform search function with AJAX (no page reload)
function performSearch(query) {
    const formData = new FormData(document.getElementById('searchForm'));
    formData.set('search', query);
    formData.set('page', '1'); // Reset to first page on search
    
    // Build query string
    const params = new URLSearchParams(formData);
    
    // Fetch results via AJAX
    fetch('users.php?' + params.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Mark as AJAX request
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update table body with smooth fade
            const tableBody = document.getElementById('usersTableBody');
            
            // Add fade-out effect
            tableBody.style.opacity = '0.5';
            tableBody.style.transition = 'opacity 0.2s ease';
            
            setTimeout(() => {
                // Update content
                tableBody.innerHTML = data.html;
                
                // Update pagination info
                updatePaginationInfo(data);
                
                // Fade back in
                tableBody.style.opacity = '1';
            }, 200);
        }
        
        // Hide loading indicator
        searchLoading.classList.add('d-none');
    })
    .catch(error => {
        console.error('Search error:', error);
        searchLoading.classList.add('d-none');
        
        // Show error message
        if (window.tenaAjax && window.tenaAjax.showNotification) {
            window.tenaAjax.showNotification({
                type: 'error',
                title: 'Search Error',
                message: 'Failed to load search results. Please try again.'
            });
        }
    });
}

// Update pagination info after AJAX search
function updatePaginationInfo(data) {
    // Update "Showing X to Y of Z entries" text
    const paginationInfo = document.querySelector('.text-muted');
    if (paginationInfo && paginationInfo.textContent.includes('Showing')) {
        const start = (data.current_page - 1) * <?php echo $filters['per_page']; ?> + 1;
        const end = Math.min(start + <?php echo $filters['per_page']; ?> - 1, data.total_records);
        paginationInfo.textContent = `Showing ${start} to ${end} of ${data.total_records} entries`;
    }
    
    // Update "Users (X total)" header
    const totalHeader = document.querySelector('.p-3.border-bottom h5');
    if (totalHeader) {
        totalHeader.textContent = `Users (${data.total_records.toLocaleString()} total)`;
    }
}

// Add data-id attributes to table rows for easier selection
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach((row, index) => {
        row.setAttribute('data-id', index + 1);
    });
});
</script>

<!-- Export & Column Configuration Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glassmorphism-modal">
            <div class="modal-header glassmorphism-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export & Column Configuration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body glassmorphism-body">
                <div class="row">
                    <!-- Column Configuration Section -->
                    <div class="col-12 mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-columns me-2"></i>Column Configuration
                        </h6>
                        <?php $columns = include __DIR__.'/users_columns.php'; ?>
                        <ul id="columnSelector" class="list-group" style="min-height:140px; max-height:300px; overflow:auto;">
                            <?php foreach ($columns as $key => $label) { ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center" data-value="<?php echo $key; ?>" draggable="true">
                                    <span class="col-label"><i class="fas fa-grip-vertical me-2 text-muted"></i><?php echo $label; ?></span>
                                    <input type="checkbox" class="form-check-input ms-2" checked />
                                </li>
                            <?php } ?>
                        </ul>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle me-1"></i>Drag items to reorder; toggle checkbox to include/exclude columns.
                        </small>
                        
                        <!-- Preset Management -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <label class="form-label fw-bold mb-2">Column Presets</label>
                            <div class="d-flex gap-2 mb-2">
                                <select id="presetSelector" class="form-select form-select-sm flex-grow-1">
                                    <option value="">Select a preset...</option>
                                </select>
                                <button id="loadPresetBtn" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-upload me-1"></i>Load
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button id="savePresetBtn" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-save me-1"></i>Save Preset
                                </button>
                                <button id="deletePresetBtn" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                                <button id="saveDefaultBtn" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-star me-1"></i>Save as Default
                                </button>
                                <button id="previewOrderBtn" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>Preview
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <!-- Export Options Section -->
                    <div class="col-12">
                        <h6 class="mb-3">
                            <i class="fas fa-file-export me-2"></i>Export Options
                        </h6>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary w-100" onclick="exportData('csv')" id="exportCsvBtnModal">
                                    <i class="fas fa-file-csv fa-2x d-block mb-2"></i>
                                    <span class="d-block">Export to CSV</span>
                                    <small class="text-muted">Download as spreadsheet</small>
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary w-100" onclick="exportData('pdf')" id="exportPdfBtnModal">
                                    <i class="fas fa-file-pdf fa-2x d-block mb-2"></i>
                                    <span class="d-block">Export to PDF</span>
                                    <small class="text-muted">Download as document</small>
                                </button>
                            </div>
                            <div class="col-md-12">
                                <button id="scheduleExportBtnModal" class="btn btn-outline-success w-100">
                                    <i class="fas fa-clock fa-2x d-block mb-2"></i>
                                    <span class="d-block">Schedule Recurring Export</span>
                                    <small class="text-muted">Set up automated exports</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer glassmorphism-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
                <button type="button" class="btn btn-primary" onclick="applyColumnChanges()">
                    <i class="fas fa-check me-2"></i>Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Apply column changes and close modal
function applyColumnChanges() {
    // This function will apply column visibility/order changes
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
    if (modal) {
        modal.hide();
    }
    
    // Show success notification
    if (window.tenaAjax && window.tenaAjax.showNotification) {
        window.tenaAjax.showNotification({
            type: 'success',
            title: 'Settings Applied',
            message: 'Column configuration has been updated.'
        });
    }
}
</script>

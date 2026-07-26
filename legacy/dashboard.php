<?php
/**
 * Tena Waitlist Dashboard
 * Main dashboard for managing waitlist registrations
 */

require_once 'auth/check_auth.php';
require_once 'config/database.php';

// Get current user
$currentUser = getCurrentUser();

// Get registration count and data
$registrationCount = 0;
$registrations = [];
$analytics = [];

try {
    $database = new Database;
    $db = $database->getConnection();

    // Get registration count
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

// Include header with sidebar
include 'includes/header.php';
?>

        <!-- Main Content -->
        <div class="container-fluid px-4">
            <!-- Overview Section -->
            <div id="overview-section" class="dashboard-section">
                <div class="row g-4 mb-4">
                    <!-- Statistics Cards -->
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($registrationCount); ?></div>
                                    <div class="text-muted">Total Registrations</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-day fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['today_registrations'] ?? 0); ?></div>
                                    <div class="text-muted">Today</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-week fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['week_registrations'] ?? 0); ?></div>
                                    <div class="text-muted">This Week</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-home fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="stats-number"><?php echo number_format($analytics['vacation_rentals'] ?? 0); ?></div>
                                    <div class="text-muted">Vacation Rentals</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Registrations</h5>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center">
                                        <label class="form-label me-2 mb-0">Show:</label>
                                        <select class="form-select form-select-sm" id="dashboardPerPage" style="width: auto;">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php if (empty($registrations)) { ?>
                                <div class="p-4 text-center">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No registrations yet</h5>
                                    <p class="text-muted">Registrations will appear here once users start joining your waitlist.</p>
                                </div>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-compact">
    <thead>
      <tr>
                                                <th class="col-name">Name</th>
                                                <th class="col-email">Email</th>
                                                <th class="col-type">Type</th>
                                                <th class="col-location">Location</th>
                                                <th class="col-date">Date</th>
                                                <th class="col-actions">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($registrations as $registration) { ?>
                                            <tr data-id="<?php echo $registration['id']; ?>">
                                                <td class="col-name">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                            <?php echo strtoupper(substr($registration['first_name'], 0, 1).substr($registration['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="name-cell">
                                                            <div class="fw-bold name-text"><?php echo htmlspecialchars($registration['first_name'].' '.$registration['last_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="col-email">
                                                    <div class="email-text"><?php echo htmlspecialchars($registration['email']); ?></div>
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
                                                <td class="col-actions">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="#" onclick="viewRegistration(<?php echo $registration['id']; ?>)">
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
                                
                                <!-- Dashboard Pagination -->
                                <div class="p-3 border-top" id="dashboardPagination">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <small class="text-muted" id="dashboardPaginationInfo">
                                                Showing 1 to <?php echo min(10, count($registrations)); ?> of <?php echo count($registrations); ?> entries
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <nav aria-label="Dashboard pagination">
                                                <ul class="pagination justify-content-end mb-0 pagination-sm" id="dashboardPaginationNav">
                                                    <!-- Pagination will be generated by JavaScript -->
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Section (Hidden by default) -->
            <div id="settings-section" class="dashboard-section" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <div class="data-table">
                            <div class="p-3 border-bottom">
                                <h5 class="mb-0">Settings</h5>
                            </div>
                            <div class="p-4">
                                <p class="text-muted">Settings panel coming soon...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Registration Modal with Glassmorphism -->
        <div class="modal fade" id="viewRegistrationModal" tabindex="-1" aria-labelledby="viewRegistrationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content glassmorphism-modal">
                    <div class="modal-header glassmorphism-header">
                        <h5 class="modal-title" id="viewRegistrationModalLabel">
                            <i class="fas fa-user-circle me-2"></i>Registration Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body glassmorphism-body" id="viewRegistrationContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h6 class="text-muted">Loading registration details...</h6>
                            <p class="small text-muted">Please wait while we fetch the information</p>
                        </div>
                    </div>
                    <div class="modal-footer glassmorphism-footer">
                        <button type="button" class="btn btn-secondary pill-btn" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary pill-btn" onclick="contactUserFromModal()">
                            <i class="fas fa-envelope me-2"></i>Contact User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact User Modal with Glassmorphism -->
        <div class="modal fade" id="contactUserModal" tabindex="-1" aria-labelledby="contactUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content glassmorphism-modal">
                    <div class="modal-header glassmorphism-header">
                        <h5 class="modal-title" id="contactUserModalLabel">
                            <i class="fas fa-envelope me-2"></i>Contact User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body glassmorphism-body">
                        <form id="contactUserForm">
                            <input type="hidden" id="contactUserId" name="user_id">
                            <div class="mb-3">
                                <label for="contactSubject" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Subject
                                </label>
                                <input type="text" class="form-control pill-input" id="contactSubject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactMessage" class="form-label">
                                    <i class="fas fa-comment me-1"></i>Message
                                </label>
                                <textarea class="form-control pill-input" id="contactMessage" name="message" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="contactType" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Contact Type
                                </label>
                                <select class="form-select pill-input" id="contactType" name="type" required>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="phone">Phone Call</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer glassmorphism-footer">
                        <button type="button" class="btn btn-secondary pill-btn" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary pill-btn" onclick="sendContactMessage()">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </div>
            </div>
        </div>

<?php include 'includes/footer.php'; ?>

<script>
// Dashboard specific functions
let currentRegistrationId = null;

function viewRegistration(id) {
    currentRegistrationId = id;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('viewRegistrationModal'));
    modal.show();
    
    // Load registration details
    loadRegistrationDetails(id);
}

function loadRegistrationDetails(id) {
    const content = document.getElementById('viewRegistrationContent');
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="text-muted">Loading registration details...</h6>
            <p class="small text-muted">Please wait while we fetch the information</p>
        </div>
    `;
    
    // Fetch registration details from the backend
    fetch('api/get_registration_details.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reg = data.data;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h6>
                                <div class="info-item">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value">${reg.first_name} ${reg.last_name}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><a href="mailto:${reg.email}" class="text-primary">${reg.email}</a></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value">${reg.country_code ? reg.country_code + ' ' : ''}${reg.phone_number || 'Not provided'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Country:</span>
                                    <span class="info-value">${reg.country || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Registration Date:</span>
                                    <span class="info-value">${new Date(reg.created_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-building me-2"></i>Business Information
                                </h6>
                                <div class="info-item">
                                    <span class="info-label">Business Name:</span>
                                    <span class="info-value">${reg.business_name || 'Not provided'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Website:</span>
                                    <span class="info-value">${reg.business_website ? `<a href="${reg.business_website}" target="_blank" class="text-primary">${reg.business_website}</a>` : 'Not provided'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Business Phone:</span>
                                    <span class="info-value">${reg.business_phone || 'Not provided'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Years in Business:</span>
                                    <span class="info-value">${reg.years_in_business || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Property Type:</span>
                                    <span class="info-value"><span class="badge bg-primary pill-badge">${reg.property_type ? reg.property_type.replace('_', ' ').toUpperCase() : 'Not specified'}</span></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Number of Properties:</span>
                                    <span class="info-value">${reg.property_count || 'Not specified'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-chart-line me-2"></i>Business Metrics
                                </h6>
                                <div class="info-item">
                                    <span class="info-label">Monthly Guests:</span>
                                    <span class="info-value">${reg.monthly_guests || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Average Nightly Rate:</span>
                                    <span class="info-value">${reg.average_nightly_rate ? '$' + reg.average_nightly_rate : 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Marketing Budget:</span>
                                    <span class="info-value">${reg.marketing_budget ? '$' + reg.marketing_budget + '/month' : 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Expected Launch:</span>
                                    <span class="info-value">${reg.expected_launch_date ? new Date(reg.expected_launch_date).toLocaleDateString() : 'Not specified'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-cog me-2"></i>Preferences
                                </h6>
                                <div class="info-item">
                                    <span class="info-label">Contact Method:</span>
                                    <span class="info-value">${reg.preferred_contact_method || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Timezone:</span>
                                    <span class="info-value">${reg.timezone || 'Not specified'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Language:</span>
                                    <span class="info-value">${reg.language_preference || 'English'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Referral Source:</span>
                                    <span class="info-value">${reg.referral_source || 'Not specified'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${reg.current_booking_platforms ? `
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-tasks me-2"></i>Current Booking Platforms
                                </h6>
                                <div class="d-flex flex-wrap gap-2">
                                    ${JSON.parse(reg.current_booking_platforms).map(platform => 
                                        `<span class="badge bg-info pill-badge">${platform.charAt(0).toUpperCase() + platform.slice(1)}</span>`
                                    ).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    ${reg.marketing_goals ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-bullseye me-2"></i>Marketing Goals
                                </h6>
                                <div class="d-flex flex-wrap gap-2">
                                    ${JSON.parse(reg.marketing_goals).map(goal => 
                                        `<span class="badge bg-success pill-badge">${goal.replace('_', ' ').toUpperCase()}</span>`
                                    ).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    ${reg.current_challenges ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Current Challenges
                                </h6>
                                <p class="text-muted">${reg.current_challenges}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    ${reg.additional_notes ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-comment me-2"></i>Additional Notes
                                </h6>
                                <p class="text-muted">${reg.additional_notes}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="info-card mb-4">
                                <h6 class="info-card-title mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Consent & Privacy
                                </h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge ${reg.newsletter_subscription ? 'bg-success' : 'bg-secondary'} pill-badge">
                                        <i class="fas fa-envelope me-1"></i>Newsletter
                                    </span>
                                    <span class="badge ${reg.marketing_consent ? 'bg-success' : 'bg-secondary'} pill-badge">
                                        <i class="fas fa-bullhorn me-1"></i>Marketing
                                    </span>
                                    <span class="badge ${reg.gdpr_consent ? 'bg-success' : 'bg-danger'} pill-badge">
                                        <i class="fas fa-lock me-1"></i>GDPR
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Registration details not found.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading registration details:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading registration details. Please try again.
                </div>
            `;
        });
}

function contactUser(id) {
    currentRegistrationId = id;
    
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
    if (currentRegistrationId) {
        contactUser(currentRegistrationId);
    }
}

function editUser(id) {
    // Placeholder for edit functionality
    alert('Edit functionality coming soon for user ID: ' + id);
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this registration? This action cannot be undone.')) {
        // Placeholder for delete functionality
        alert('Delete functionality coming soon for user ID: ' + id);
    }
}

// Dashboard pagination functionality
let currentDashboardPage = 1;
let dashboardPerPage = 10;
let allRegistrations = [];

// Initialize dashboard pagination
document.addEventListener('DOMContentLoaded', function() {
    // Get all registration data
    const tableRows = document.querySelectorAll('.table tbody tr');
    allRegistrations = Array.from(tableRows).map(row => ({
        element: row,
        data: {
            id: row.getAttribute('data-id'),
            name: row.querySelector('.name-text')?.textContent || '',
            email: row.querySelector('.email-text')?.textContent || '',
            type: row.querySelector('.badge')?.textContent || '',
            country: row.querySelector('.location-text')?.textContent || '',
            date: row.querySelector('.date-text')?.textContent || ''
        }
    }));
    
    // Initialize pagination
    updateDashboardPagination();
    
    // Per-page change handler
    document.getElementById('dashboardPerPage').addEventListener('change', function() {
        dashboardPerPage = parseInt(this.value);
        currentDashboardPage = 1;
        updateDashboardPagination();
    });
});

function updateDashboardPagination() {
    const totalItems = allRegistrations.length;
    const totalPages = Math.ceil(totalItems / dashboardPerPage);
    const startIndex = (currentDashboardPage - 1) * dashboardPerPage;
    const endIndex = Math.min(startIndex + dashboardPerPage, totalItems);
    
    // Hide all rows
    allRegistrations.forEach(reg => {
        reg.element.style.display = 'none';
    });
    
    // Show current page rows
    for (let i = startIndex; i < endIndex; i++) {
        if (allRegistrations[i]) {
            allRegistrations[i].element.style.display = '';
        }
    }
    
    // Update pagination info
    const infoElement = document.getElementById('dashboardPaginationInfo');
    if (infoElement) {
        infoElement.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${totalItems} entries`;
    }
    
    // Generate pagination nav
    const navElement = document.getElementById('dashboardPaginationNav');
    if (navElement && totalPages > 1) {
        let paginationHTML = '';
        
        // Previous button
        if (currentDashboardPage > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeDashboardPage(1)">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeDashboardPage(${currentDashboardPage - 1})">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentDashboardPage - 2);
        const endPage = Math.min(totalPages, currentDashboardPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === currentDashboardPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changeDashboardPage(${i})">${i}</a>
                </li>
            `;
        }
        
        // Next button
        if (currentDashboardPage < totalPages) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeDashboardPage(${currentDashboardPage + 1})">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeDashboardPage(${totalPages})">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            `;
        }
        
        navElement.innerHTML = paginationHTML;
    }
}

function changeDashboardPage(page) {
    currentDashboardPage = page;
    updateDashboardPagination();
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

function updateStatsCards() {
    // This function will be called by the AJAX system
    if (window.tenaAjax) {
        window.tenaAjax.getStats().then(response => {
            if (response.success) {
                // Update stats cards with new data
                const stats = response.data;
                const totalEl = document.querySelector('.stats-card .stats-number');
                if (totalEl) totalEl.textContent = stats.total_registrations || 0;
                // Update other stats as needed
            }
        });
    }
}

// Table rows already have data-id attributes set from PHP
</script>

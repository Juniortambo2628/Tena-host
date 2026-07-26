    </div> <!-- End dashboard-content -->
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Include AJAX functionality -->
    <script src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>js/ajax.js"></script>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) { ?>
    <!-- Sortable.js for drag-and-drop column reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <?php } ?>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true,
            mirror: false,
            offset: 100
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
                    if (typeof updateStatsCards === 'function') {
                        updateStatsCards();
                    }
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
            // Show loading state
            const btn = document.getElementById('export' + format.charAt(0).toUpperCase() + format.slice(1) + 'Btn');
            let originalText = '';
            if (btn) {
                originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
                btn.disabled = true;
            }

            try {
                const currentPath = window.location.pathname;
                const isAdminPage = currentPath.includes('/admin/');

                // If on admin pages, perform direct download using server export endpoint (supports filters)
                if (isAdminPage) {
                    // Build base URL (same approach as directExport)
                    let baseUrl = window.location.origin + currentPath.replace(/\/admin\/.*$/, '');
                    const params = new URLSearchParams();
                    const searchEl = document.querySelector('[name="search"]');
                    const propertyEl = document.querySelector('[name="property_type"]');
                    const statusEl = document.querySelector('[name="status"]');
                    const dateFromEl = document.querySelector('[name="date_from"]');
                    const dateToEl = document.querySelector('[name="date_to"]');
                    if (searchEl && searchEl.value) params.set('search', searchEl.value);
                    if (propertyEl && propertyEl.value) params.set('property_type', propertyEl.value);
                    if (statusEl && statusEl.value) params.set('status', statusEl.value);
                    if (dateFromEl && dateFromEl.value) params.set('date_from', dateFromEl.value);
                    if (dateToEl && dateToEl.value) params.set('date_to', dateToEl.value);

                    // If PDF requested, redirect to admin/export.php which handles PDF generation
                    if (format === 'pdf') {
                        const pdfUrl = baseUrl + '/admin/export.php?export=pdf&' + params.toString();
                        const link = document.createElement('a');
                        link.href = pdfUrl;
                        link.target = '_blank';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                        return;
                    }

                    const exportUrl = baseUrl + '/admin/api/users_export.php';
                    // Attach selected columns
                    const colSelect = document.getElementById('columnSelector');
                    if (colSelect) {
                        const selected = Array.from(colSelect.selectedOptions).map(o => o.value);
                        if (selected.length) params.set('columns', JSON.stringify(selected));
                    }
                    // Request async export which returns an export_id
                    const asyncParams = params;
                    asyncParams.set('async', '1');
                    fetch(exportUrl + '?' + asyncParams.toString(), { credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(res => {
                            if (res && res.success && res.data && res.data.export_id) {
                                const exportId = res.data.export_id;
                                showExportProgress(exportId, format, originalText, btn);
                            } else {
                                // fallback to direct download
                                const link = document.createElement('a');
                                link.href = exportUrl + '?' + params.toString();
                                link.target = '_blank';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                            }
                        }).catch(err => {
                            console.error('Async export failed:', err);
                            const link = document.createElement('a');
                            link.href = exportUrl + '?' + params.toString();
                            link.target = '_blank';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                        });
                    return;
                }

                // Non-admin: fall back to AJAX export request
                if (window.tenaAjax) {
                    window.tenaAjax.exportData(format).then(response => {
                        if (response && response.success) {
                            if (window.tenaAjax) window.tenaAjax.showNotification({ type: 'success', title: 'Export Started', message: `Your ${format.toUpperCase()} export has been initiated.` });
                        } else {
                            directExport(format);
                        }
                    }).catch(err => { console.error('Export error:', err); directExport(format); })
                    .finally(() => { if (btn) { btn.innerHTML = originalText; btn.disabled = false; } });
                } else {
                    directExport(format);
                }
            } catch (err) {
                console.error('ExportData error:', err);
                directExport(format);
                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
            }
        }
        
        function directExport(format) {
            // Direct export without AJAX
            const currentPath = window.location.pathname;
            const isAdminPage = currentPath.includes('/admin/');
            
            let baseUrl;
            if (isAdminPage) {
                // If we're in admin directory, go up one level
                baseUrl = window.location.origin + currentPath.replace(/\/admin\/.*$/, '');
            } else {
                // If we're in root directory, use current path
                baseUrl = window.location.origin + currentPath.replace(/\/[^\/]*$/, '');
            }
            
            const exportUrl = baseUrl + '/admin/export.php?format=' + format;
            
            // Create a temporary link and click it
            const link = document.createElement('a');
            link.href = exportUrl;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show notification
            if (window.tenaAjax) {
                window.tenaAjax.showNotification({
                    type: 'info',
                    title: 'Export Started',
                    message: `Your ${format.toUpperCase()} export has been initiated.`
                });
            }
        }

        // Export progress UI
        function showExportProgress(exportId, format, originalText = '', btn = null) {
            // Create modal if not exists
            let modal = document.getElementById('exportProgressModal');
            if (!modal) {
                const html = `
                <div class="modal fade" id="exportProgressModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Export Progress</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div id="exportProgressMsg" class="mb-3">Queued...</div>
                        <div class="progress"><div id="exportProgressBar" class="progress-bar" role="progressbar" style="width:0%"></div></div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a id="exportDownloadLink" class="btn btn-primary d-none" href="#" target="_blank">Download</a>
                      </div>
                    </div>
                  </div>
                </div>`;
                document.body.insertAdjacentHTML('beforeend', html);
                modal = document.getElementById('exportProgressModal');
            }

            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            const msg = document.getElementById('exportProgressMsg');
            const bar = document.getElementById('exportProgressBar');
            const dlLink = document.getElementById('exportDownloadLink');

            let attempts = 0;
            const poll = setInterval(() => {
                attempts++;
                fetch((window.location.pathname.includes('/admin/') ? '../' : '') + 'admin/api/export_status.php?export_id=' + encodeURIComponent(exportId), { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) return;
                        const data = res.data;
                        if (data.status === 'queued') {
                            msg.textContent = 'Queued...';
                            bar.style.width = '10%';
                        } else if (data.status === 'processing') {
                            msg.textContent = data.message || 'Processing...';
                            bar.style.width = '50%';
                        } else if (data.status === 'ready') {
                            msg.textContent = 'Ready. Click to download.';
                            bar.style.width = '100%';
                            if (data.download) {
                                dlLink.href = data.download;
                                dlLink.classList.remove('d-none');
                            }
                            clearInterval(poll);
                        } else if (data.status === 'failed') {
                            msg.textContent = 'Export failed.';
                            bar.style.width = '100%';
                            clearInterval(poll);
                        }
                    }).catch(err => {
                        console.error('Export status poll error', err);
                        if (attempts > 10) { clearInterval(poll); msg.textContent = 'Unable to check export status.'; }
                    });
            }, 2000);
        }
        
        // Mark notification as read
        document.addEventListener('click', function(e) {
            if (e.target.closest('.mark-read')) {
                const notificationId = e.target.closest('.mark-read').dataset.notificationId;
                if (window.tenaAjax) {
                    window.tenaAjax.markNotificationAsRead(notificationId).then(response => {
                        if (response.success) {
                            // Reload notifications
                            window.tenaAjax.getNotifications(10, true).then(response => {
                                if (response.success) {
                                    updateNotificationDropdown(response.data.notifications);
                                    updateNotificationCount(response.data.unread_count);
                                }
                            });
                        }
                    });
                }
            }
            
            if (e.target.closest('.mark-all-read')) {
                if (window.tenaAjax) {
                    window.tenaAjax.markAllNotificationsAsRead().then(response => {
                        if (response.success) {
                            // Reload notifications
                            window.tenaAjax.getNotifications(10, true).then(response => {
                                if (response.success) {
                                    updateNotificationDropdown(response.data.notifications);
                                    updateNotificationCount(response.data.unread_count);
                                }
                            });
                        }
                    });
                }
            }
        });
        
        // Profile and Settings functions
        function showProfile() {
            // Create profile modal with glassmorphism
            const modalHtml = `
                <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content glassmorphism-modal">
                            <div class="modal-header glassmorphism-header">
                                <h5 class="modal-title" id="profileModalLabel">
                                    <i class="fas fa-user-circle me-2"></i>User Profile
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body glassmorphism-body">
                                <div class="text-center mb-4">
                                    <div class="profile-avatar mb-3">
                                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                                    </div>
                                    <h4 class="text-dark"><?php echo htmlspecialchars($currentUser['username']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                </div>
                                <div class="info-card mb-4">
                                    <h6 class="info-card-title mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Account Information
                                    </h6>
                                    <div class="info-item">
                                        <span class="info-label">Username:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Role:</span>
                                        <span class="info-value"><span class="badge bg-primary pill-badge"><?php echo ucfirst($currentUser['role']); ?></span></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">User ID:</span>
                                        <span class="info-value">#<?php echo $currentUser['id']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer glassmorphism-footer">
                                <button type="button" class="btn btn-secondary pill-btn" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>
                                <button type="button" class="btn btn-primary pill-btn" onclick="showSettings()">
                                    <i class="fas fa-cog me-2"></i>Edit Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('profileModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('profileModal'));
            modal.show();
        }
        
        function showSettings() {
            // Create settings modal with glassmorphism
            const modalHtml = `
                <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content glassmorphism-modal">
                            <div class="modal-header glassmorphism-header">
                                <h5 class="modal-title" id="settingsModalLabel">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body glassmorphism-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-card mb-4">
                                            <h6 class="info-card-title mb-3">
                                                <i class="fas fa-user me-2"></i>Account Settings
                                            </h6>
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-user me-1"></i>Username
                                                </label>
                                                <input type="text" class="form-control pill-input" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-envelope me-1"></i>Email
                                                </label>
                                                <input type="email" class="form-control pill-input" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-card mb-4">
                                            <h6 class="info-card-title mb-3">
                                                <i class="fas fa-sliders-h me-2"></i>Preferences
                                            </h6>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                                <label class="form-check-label" for="emailNotifications">
                                                    <i class="fas fa-envelope me-2"></i>Email Notifications
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="dashboardUpdates" checked>
                                                <label class="form-check-label" for="dashboardUpdates">
                                                    <i class="fas fa-chart-line me-2"></i>Dashboard Updates
                                                </label>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="realTimeUpdates" checked>
                                                <label class="form-check-label" for="realTimeUpdates">
                                                    <i class="fas fa-sync me-2"></i>Real-time Updates
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-card">
                                            <h6 class="info-card-title mb-3">
                                                <i class="fas fa-palette me-2"></i>Display Preferences
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">
                                                            <i class="fas fa-moon me-1"></i>Theme
                                                        </label>
                                                        <select class="form-select pill-input" id="themeSelect">
                                                            <option value="light" selected>Light</option>
                                                            <option value="dark">Dark</option>
                                                            <option value="auto">Auto</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">
                                                            <i class="fas fa-language me-1"></i>Language
                                                        </label>
                                                        <select class="form-select pill-input" id="languageSelect">
                                                            <option value="en" selected>English</option>
                                                            <option value="es">Spanish</option>
                                                            <option value="fr">French</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer glassmorphism-footer">
                                <button type="button" class="btn btn-secondary pill-btn" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-primary pill-btn" onclick="saveSettings()">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('settingsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
            modal.show();
        }
        
        function saveSettings() {
            // Get settings values
            const emailNotifications = document.getElementById('emailNotifications').checked;
            const dashboardUpdates = document.getElementById('dashboardUpdates').checked;
            const realTimeUpdates = document.getElementById('realTimeUpdates').checked;
            const theme = document.getElementById('themeSelect').value;
            const language = document.getElementById('languageSelect').value;
            
            // Show loading state
            const saveBtn = document.querySelector('[onclick="saveSettings()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveBtn.disabled = true;
            
            // Simulate saving settings
            setTimeout(() => {
                // Show success message
                if (window.tenaAjax) {
                    window.tenaAjax.showNotification({
                        type: 'success',
                        title: 'Settings Saved',
                        message: 'Your preferences have been updated successfully.'
                    });
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
                modal.hide();
                
                // Reset button
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }, 1500);
        }
    </script>
</body>
</html>

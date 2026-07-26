/**
 * AJAX Handler for Tena Waitlist System
 * Handles real-time updates and AJAX requests
 */

class TenaAjax {
    constructor() {
        // More reliable URL construction
        this.baseUrl = this.getBaseUrl();
        this.apiUrl = this.baseUrl + '/api/ajax_handler.php';
        this.lastCheck = Math.floor(Date.now() / 1000);
        this.updateInterval = null;
        this.notificationContainer = null;
        this.init();
    }
    
    getBaseUrl() {
        // Get the base URL more reliably
        const currentPath = window.location.pathname;
        const isAdminPage = currentPath.includes('/admin/');
        
        if (isAdminPage) {
            // If we're in admin directory, go up one level
            return window.location.origin + currentPath.replace(/\/admin\/.*$/, '');
        } else {
            // If we're in root directory, use current path
            return window.location.origin + currentPath.replace(/\/[^\/]*$/, '');
        }
    }
    
    init() {
        this.setupNotificationContainer();
        this.bindEvents();
        
        // Only start real-time updates if we're on a dashboard page
        if (this.isDashboardPage()) {
            this.startRealtimeUpdates();
        }
    }
    
    isDashboardPage() {
        const currentPath = window.location.pathname;
        return currentPath.includes('dashboard.php') || 
               currentPath.includes('admin/') || 
               currentPath.includes('users.php') || 
               currentPath.includes('analytics.php');
    }
    
    setupNotificationContainer() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        this.notificationContainer = document.getElementById('notification-container');
    }
    
    startRealtimeUpdates() {
        // Check for updates every 30 seconds
        this.updateInterval = setInterval(() => {
            this.checkRealtimeUpdates();
        }, 30000);
        
        // Initial check
        this.checkRealtimeUpdates();
    }
    
    stopRealtimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    // Public method to stop updates
    stopUpdates() {
        this.stopRealtimeUpdates();
    }
    
    async checkRealtimeUpdates() {
        try {
            const response = await this.makeRequest('realtime_updates', {
                last_check: this.lastCheck
            });
            
            if (response && response.success) {
                this.handleRealtimeUpdates(response.data);
                this.lastCheck = response.data.timestamp;
            }
        } catch (error) {
            console.error('Realtime update error:', error);
            
            // If it's an authentication error, stop the updates
            if (error.message.includes('401') || error.message.includes('Authentication')) {
                this.stopRealtimeUpdates();
            }
        }
    }
    
    handleRealtimeUpdates(data) {
        // Update notification count in header
        this.updateNotificationCount(data.unread_count);
        
        // Show new notifications
        if (data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(notification => {
                this.showNotification(notification);
            });
        }
    }
    
    updateNotificationCount(count) {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }
    
    showNotification(notification) {
        const alertClass = this.getNotificationClass(notification.type);
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show`;
        alert.innerHTML = `
            <strong>${notification.title}</strong><br>
            ${notification.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        this.notificationContainer.appendChild(alert);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    getNotificationClass(type) {
        const classes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };
        return classes[type] || 'alert-info';
    }
    
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        
        // Add data to form
        Object.keys(data).forEach(key => {
            if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        });
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                if (response.status === 401) {
                    // Handle authentication error
                    console.warn('Authentication required. Redirecting to login...');
                    window.location.href = this.getBaseUrl() + '/auth/login.php';
                    return { success: false, message: 'Authentication required' };
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('AJAX request failed:', error);
            
            // Show user-friendly error message for network issues
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                this.showNotification({
                    type: 'error',
                    title: 'Connection Error',
                    message: 'Unable to connect to the server. Please check your internet connection and try again.'
                });
            }
            
            throw error;
        }
    }
    
    // Public methods for different actions
    async getNotifications(limit = 20, unreadOnly = false) {
        return await this.makeRequest('get_notifications', {
            limit: limit,
            unread_only: unreadOnly
        });
    }
    
    async markNotificationRead(notificationId) {
        return await this.makeRequest('mark_notification_read', {
            notification_id: notificationId
        });
    }
    
    async markAllRead() {
        return await this.makeRequest('mark_all_read');
    }
    
    async getStats() {
        return await this.makeRequest('get_stats');
    }
    
    async getRegistrations(filters = {}) {
        return await this.makeRequest('get_registrations', filters);
    }
    
    async updateRegistration(registrationId, field, value) {
        return await this.makeRequest('update_registration', {
            registration_id: registrationId,
            field: field,
            value: value
        });
    }
    
    async getAnalytics(dateFrom, dateTo) {
        return await this.makeRequest('get_analytics', {
            date_from: dateFrom,
            date_to: dateTo
        });
    }
    
    async exportData(format, filters = {}) {
        return await this.makeRequest('export_data', {
            format: format,
            filters: filters
        });
    }
    
    bindEvents() {
        // Bind notification events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-read')) {
                const notificationId = e.target.dataset.notificationId;
                this.markNotificationRead(notificationId).then(() => {
                    e.target.closest('.notification-item').remove();
                });
            }
            
            if (e.target.classList.contains('mark-all-read')) {
                this.markAllRead().then(() => {
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.remove();
                    });
                });
            }
        });
        
        // Bind form submissions for AJAX
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleFormSubmission(e.target);
            }
        });
    }
    
    async handleFormSubmission(form) {
        const formData = new FormData(form);
        const action = form.dataset.action;
        
        try {
            const response = await this.makeRequest(action, Object.fromEntries(formData));
            
            if (response.success) {
                this.showNotification({
                    type: 'success',
                    title: 'Success',
                    message: response.message || 'Operation completed successfully'
                });
                
                // Trigger custom event
                form.dispatchEvent(new CustomEvent('ajax-success', { detail: response }));
            } else {
                this.showNotification({
                    type: 'error',
                    title: 'Error',
                    message: response.message || 'Operation failed'
                });
            }
        } catch (error) {
            this.showNotification({
                type: 'error',
                title: 'Error',
                message: 'Network error occurred'
            });
        }
    }
    
    // Utility method to update stats cards
    async updateStatsCards() {
        try {
            const response = await this.getStats();
            if (response.success) {
                this.updateStatsDisplay(response.data);
            }
        } catch (error) {
            console.error('Failed to update stats:', error);
        }
    }
    
    updateStatsDisplay(stats) {
        // Update total registrations
        const totalEl = document.querySelector('[data-stat="total_registrations"]');
        if (totalEl) totalEl.textContent = stats.total_registrations || 0;
        
        // Update today's registrations
        const todayEl = document.querySelector('[data-stat="today_registrations"]');
        if (todayEl) todayEl.textContent = stats.today_registrations || 0;
        
        // Update week registrations
        const weekEl = document.querySelector('[data-stat="week_registrations"]');
        if (weekEl) weekEl.textContent = stats.week_registrations || 0;
        
        // Update vacation rentals
        const vacationEl = document.querySelector('[data-stat="vacation_rentals"]');
        if (vacationEl) vacationEl.textContent = stats.vacation_rentals || 0;
    }
    
    // Method to refresh data tables
    async refreshDataTable(tableId, filters = {}) {
        try {
            const response = await this.getRegistrations(filters);
            if (response.success) {
                this.updateDataTable(tableId, response.data.registrations);
            }
        } catch (error) {
            console.error('Failed to refresh data table:', error);
        }
    }
    
    updateDataTable(tableId, data) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        // Clear existing rows
        tbody.innerHTML = '';
        
        // Add new rows
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = this.buildTableRow(row);
            tbody.appendChild(tr);
        });
    }
    
    buildTableRow(data) {
        return `
            <td>${data.id}</td>
            <td><strong>${data.first_name} ${data.last_name}</strong></td>
            <td><a href="mailto:${data.email}" class="text-decoration-none">${data.email}</a></td>
            <td><span class="badge bg-secondary">${this.formatPropertyType(data.property_type)}</span></td>
            <td>${data.location || 'N/A'}</td>
            <td>${data.phone || 'N/A'}</td>
            <td><span class="badge ${this.getStatusClass(data.status)}">${this.formatStatus(data.status)}</span></td>
            <td>${this.formatDate(data.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewUser(${data.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="editUser(${data.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
    }
    
    formatPropertyType(type) {
        const types = {
            'vacation_rental': 'Vacation Rental',
            'hotel': 'Hotel',
            'b&b': 'B&B',
            'other': 'Other'
        };
        return types[type] || 'Unknown';
    }
    
    formatStatus(status) {
        const statuses = {
            'active': 'Active',
            'inactive': 'Inactive',
            'converted': 'Converted'
        };
        return statuses[status] || 'Unknown';
    }
    
    getStatusClass(status) {
        const classes = {
            'active': 'bg-success',
            'inactive': 'bg-warning',
            'converted': 'bg-primary'
        };
        return classes[status] || 'bg-secondary';
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Get notifications (using the main makeRequest method)
    async getNotifications(limit = 10, unreadOnly = false) {
        return await this.makeRequest('get_notifications', {
            limit: limit,
            unread_only: unreadOnly
        });
    }
    
    // Mark notification as read
    async markNotificationAsRead(notificationId) {
        return await this.makeRequest('mark_notification_read', {
            notification_id: notificationId
        });
    }
    
    // Mark all notifications as read
    async markAllNotificationsAsRead() {
        return await this.makeRequest('mark_all_read');
    }
    
    // Get stats - fixed method
    async getStats() {
        return await this.makeRequest('get_stats');
    }
    
    // Export data - fixed method
    async exportData(format, filters = {}) {
        return await this.makeRequest('export_data', {
            format: format,
            filters: filters
        });
    }
}

// Initialize AJAX handler when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.tenaAjax = new TenaAjax();
});

// Global functions for backward compatibility
function exportData(format) {
    if (window.tenaAjax) {
        window.tenaAjax.exportData(format);
    }
}

function refreshStats() {
    if (window.tenaAjax) {
        window.tenaAjax.updateStatsCards();
    }
}

function refreshTable(tableId, filters = {}) {
    if (window.tenaAjax) {
        window.tenaAjax.refreshDataTable(tableId, filters);
    }
}

# Tena Waitlist System - Refactored Setup Guide

## 🚀 System Overview

This refactored version includes:

- **Environment Detection**: Automatic dev/staging/production configuration
- **Reusable Constants**: Centralized configuration and helper functions
- **Real-time Updates**: AJAX-powered live data without page reloads
- **Notification System**: Comprehensive alert and notification management
- **Enhanced Security**: Improved authentication and session management

## 📁 New File Structure

```
Tena/
├── config/
│   ├── config.php              # Environment detection & configuration
│   ├── constants.php           # Reusable constants & helper functions
│   ├── database.php            # Enhanced database class
│   └── notifications.sql       # Notification system SQL
├── classes/
│   └── NotificationManager.php # Notification management class
├── api/
│   └── ajax_handler.php        # AJAX request handler
├── js/
│   └── ajax.js                 # Frontend AJAX functionality
├── auth/
│   ├── login.php               # Enhanced login with constants
│   ├── logout.php              # Logout handler
│   └── check_auth.php          # Enhanced authentication check
├── admin/
│   ├── users.php               # User management interface
│   ├── analytics.php           # Analytics dashboard
│   └── export.php              # Export functionality
└── composer.json               # Composer dependencies
```

## 🔧 Installation Steps

### 1. Database Setup

```sql
-- Run the enhanced database setup
SOURCE database_setup.sql;
```

### 2. Environment Configuration

The system automatically detects your environment:

- **Development**: localhost, 127.0.0.1, dev._, test._
- **Staging**: staging._, stage._
- **Production**: Everything else

### 3. Configuration Files

- **`config/config.php`**: Environment-specific settings
- **`config/constants.php`**: Reusable functions and constants
- **`config/database.php`**: Enhanced database connection

### 4. Composer Dependencies

```bash
# Install required packages
composer install
```

## 🎯 Key Features

### **Environment Detection**

```php
// Automatic environment detection
if (isDevelopment()) {
    // Development-specific code
}

if (isProduction()) {
    // Production-specific code
}
```

### **Reusable Constants**

```php
// Use predefined constants
Common::jsonResponse($data, HTTP_OK, 'Success');
Common::errorResponse('Error message', HTTP_BAD_REQUEST);
Common::successResponse($data, 'Operation completed');
```

### **Real-time Updates**

```javascript
// Automatic real-time updates every 30 seconds
window.tenaAjax.updateStatsCards();
window.tenaAjax.getNotifications();
```

### **Notification System**

```php
// Create notifications
$notificationManager->createSystemNotification(
    'System Update',
    'New features available',
    NOTIFICATION_INFO
);
```

## 🔐 Enhanced Security

### **Session Management**

- Automatic session timeout (1 hour)
- Secure session configuration
- Activity logging

### **Authentication**

- Role-based access control
- Session validation
- AJAX-aware error handling

### **Input Validation**

- Sanitization helpers
- Email validation
- Property type validation

## 📊 Real-time Features

### **Live Statistics**

- Auto-updating dashboard cards
- Real-time notification count
- Live data refresh

### **AJAX Endpoints**

- `get_notifications` - Fetch user notifications
- `get_stats` - Get dashboard statistics
- `get_registrations` - Fetch filtered registrations
- `update_registration` - Update registration data
- `export_data` - Trigger data export
- `realtime_updates` - Get real-time updates

### **Notification System**

- Real-time notifications
- User-specific alerts
- System-wide announcements
- Notification preferences

## 🎨 Frontend Enhancements

### **AJAX Integration**

```javascript
// Make AJAX requests
const response = await window.tenaAjax.getStats();
const notifications = await window.tenaAjax.getNotifications();
```

### **Real-time Updates**

- Auto-refresh every 30 seconds
- Live notification badges
- Dynamic content updates

### **Notification UI**

- Dropdown notification panel
- Real-time badge updates
- Mark as read functionality

## 📈 Analytics & Reporting

### **Enhanced Analytics**

- Real-time chart updates
- Live data filtering
- Dynamic date ranges

### **Export System**

- AJAX-triggered exports
- Real-time progress updates
- Notification on completion

## 🔧 Configuration Options

### **Environment Settings**

```php
// Development
define('APP_DEBUG', true);
define('SESSION_LIFETIME', 3600);

// Production
define('APP_DEBUG', false);
define('CACHE_ENABLED', true);
```

### **Database Configuration**

```php
// Environment-specific database settings
$dbConfig = [
    'development' => [...],
    'staging' => [...],
    'production' => [...]
];
```

## 🚀 Usage Examples

### **Creating Notifications**

```php
// System notification
$notificationManager->createSystemNotification(
    'New Registration',
    'A new user joined the waitlist',
    NOTIFICATION_SUCCESS
);

// User-specific notification
$notificationManager->createUserNotification(
    $userId,
    'Export Ready',
    'Your data export is complete',
    NOTIFICATION_INFO,
    NOTIF_CAT_EXPORT
);
```

### **AJAX Requests**

```javascript
// Get filtered registrations
const registrations = await window.tenaAjax.getRegistrations({
  search: "john",
  property_type: "vacation_rental",
  status: "active",
});

// Update registration
await window.tenaAjax.updateRegistration(123, "status", "converted");
```

### **Using Helper Functions**

```php
// Format dates
$formattedDate = Common::formatDate($date);
$formattedDateTime = Common::formatDateTime($datetime);

// Validate data
$validEmail = Common::validateEmail($email);
$validPropertyType = Common::validatePropertyType($type);

// Pagination
$pagination = Common::paginate($totalRecords, $page, $perPage);
```

## 🔍 Monitoring & Logging

### **Activity Logging**

```php
// Log user activities
Common::logActivity('user_login', 'User logged in successfully');
Common::logActivity('data_export', 'CSV export completed');
```

### **Error Handling**

- Environment-specific error reporting
- AJAX error responses
- Database error logging

## 📱 Mobile Responsiveness

### **Responsive Design**

- Mobile-optimized notification panel
- Touch-friendly interface
- Responsive data tables

### **Progressive Enhancement**

- Works without JavaScript
- Graceful degradation
- Enhanced with AJAX

## 🛠️ Troubleshooting

### **Common Issues**

1. **AJAX Not Working**

   - Check browser console for errors
   - Verify `js/ajax.js` is loaded
   - Check API endpoint accessibility

2. **Notifications Not Showing**

   - Verify notification tables exist
   - Check user permissions
   - Clear browser cache

3. **Real-time Updates Not Working**
   - Check JavaScript console
   - Verify AJAX handler is accessible
   - Check network connectivity

### **Debug Mode**

```php
// Enable debug mode in development
if (isDevelopment()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```

## 🔄 Migration from Old System

### **Database Migration**

1. Run the new `database_setup.sql`
2. Update existing files to use new constants
3. Test all functionality

### **File Updates**

1. Include `config/constants.php` in all PHP files
2. Update database connections to use new class
3. Replace hardcoded values with constants

## 📚 API Reference

### **AJAX Endpoints**

- `POST /api/ajax_handler.php`
- Actions: `get_notifications`, `get_stats`, `get_registrations`, etc.
- Response: JSON with success/error status

### **Helper Functions**

- `Common::jsonResponse()` - Send JSON response
- `Common::sanitize()` - Sanitize input
- `Common::validateEmail()` - Validate email
- `Common::logActivity()` - Log activities

## 🎉 Benefits of Refactoring

1. **Maintainability**: Centralized configuration and constants
2. **Scalability**: Environment-specific settings
3. **User Experience**: Real-time updates and notifications
4. **Security**: Enhanced authentication and validation
5. **Performance**: Optimized database queries and caching
6. **Flexibility**: Easy to extend and modify

---

**Note**: This refactored system maintains backward compatibility while adding powerful new features. All existing functionality continues to work while benefiting from the new enhancements.

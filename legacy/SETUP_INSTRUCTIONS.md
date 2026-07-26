# Tena Waitlist System Setup Instructions

## 🗄️ Database Setup

### 1. Create MySQL Database

1. Open phpMyAdmin or MySQL command line
2. Run the SQL script: `database_setup.sql`
3. This will create:
   - `tena_waitlist` database
   - `users` table for authentication
   - `registrations` table for waitlist data
   - `analytics` table for metrics
   - Sample data for testing

### 2. Database Configuration

- Update `config/database.php` with your MySQL credentials:
  - Host: `localhost` (or your MySQL host)
  - Database: `tena_waitlist`
  - Username: `root` (or your MySQL username)
  - Password: `''` (or your MySQL password)

## 🔐 Authentication Setup

### Default Admin Credentials

- **Username:** `admin`
- **Email:** `admin@tena.com`
- **Password:** `password`

### Change Default Password

1. Login to the dashboard
2. Go to Settings (or implement password change functionality)
3. Update the password hash in the database

## 📁 File Structure

```
Tena/
├── auth/
│   ├── login.php          # Login page
│   ├── logout.php         # Logout handler
│   └── check_auth.php     # Authentication check
├── admin/
│   ├── users.php          # User management interface
│   ├── analytics.php      # Analytics dashboard
│   └── export.php         # Export functionality
├── config/
│   └── database.php       # Database configuration
├── dashboard.php          # Main dashboard
├── register.php           # Registration handler
└── database_setup.sql     # Database setup script
```

## 🚀 Features Implemented

### ✅ Authentication System

- Secure login/logout
- Session management
- Role-based access (admin/user)
- Password hashing

### ✅ User Management

- Comprehensive user listing
- Advanced filtering (search, property type, status, date range)
- Sorting and pagination
- User actions (view, edit)

### ✅ Analytics Dashboard

- Registration trends chart
- Property type distribution
- Referral source analysis
- Status distribution
- Top locations
- Date range filtering

### ✅ Export Functionality

- CSV export with all data
- PDF export (HTML-based)
- Filtered data export
- Advanced export options

### ✅ Database Integration

- MySQL database with proper schema
- PDO for secure database operations
- Data validation and sanitization
- Error handling

## 🔧 Configuration Steps

### 1. Database Setup

```sql
-- Run this in MySQL
SOURCE database_setup.sql;
```

### 2. File Permissions

```bash
# Ensure proper permissions
chmod 755 auth/
chmod 755 admin/
chmod 755 config/
chmod 644 *.php
```

### 3. Web Server Configuration

- Ensure PHP 7.4+ is installed
- Enable PDO MySQL extension
- Set proper document root to Tena folder

## 🎯 Usage Guide

### Accessing the System

1. **Main Website:** `http://localhost/Tena/index.html`
2. **Dashboard Login:** `http://localhost/Tena/auth/login.php`
3. **User Management:** `http://localhost/Tena/admin/users.php`
4. **Analytics:** `http://localhost/Tena/admin/analytics.php`

### Managing Users

1. Login with admin credentials
2. Navigate to User Management
3. Use filters to find specific users
4. Export data as needed
5. View analytics for insights

### Registration Process

1. Users visit the main website
2. Fill out the waitlist form
3. Data is saved to database
4. Admin can view in dashboard

## 🔒 Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with PDO prepared statements
- Input validation and sanitization
- Session-based authentication
- Role-based access control
- CSRF protection (can be added)

## 📊 Analytics Features

- Real-time registration counts
- Property type distribution
- Referral source tracking
- Geographic analysis
- Growth trends
- Status monitoring

## 📤 Export Features

- CSV export with all fields
- PDF export with formatting
- Filtered data export
- Date range filtering
- Custom field selection

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Error**

   - Check MySQL credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Login Issues**

   - Check if user exists in database
   - Verify password hash
   - Check session configuration

3. **Export Issues**
   - Ensure proper file permissions
   - Check PHP memory limits
   - Verify data exists

### Support

- Check PHP error logs
- Verify database connectivity
- Test with sample data
- Check file permissions

## 🚀 Next Steps

### Potential Enhancements

1. Email notifications for new registrations
2. Advanced user management (edit, delete users)
3. Email marketing integration
4. Advanced analytics with more charts
5. User role management
6. API endpoints for mobile apps
7. Automated backup system
8. Advanced security features

### Maintenance

1. Regular database backups
2. Monitor system performance
3. Update security patches
4. Review user access logs
5. Optimize database queries

---

**Note:** This system is designed for production use with proper security measures. Always test thoroughly before deploying to a live environment.

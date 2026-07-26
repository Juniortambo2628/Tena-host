-- Tena Waitlist Database Setup
-- Run this script in your MySQL database

CREATE DATABASE IF NOT EXISTS tena_waitlist;

USE tena_waitlist;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Registrations table for waitlist data
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    property_type ENUM(
        'vacation_rental',
        'hotel',
        'b&b',
        'other'
    ) NOT NULL,
    property_count INT DEFAULT 1,
    location VARCHAR(100),
    phone VARCHAR(20),
    message TEXT,
    referral_source VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM(
        'active',
        'inactive',
        'converted'
    ) DEFAULT 'active'
);

-- Analytics table for tracking metrics
CREATE TABLE IF NOT EXISTS analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(50) NOT NULL,
    metric_value DECIMAL(10, 2) NOT NULL,
    date_recorded DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: password)
INSERT INTO
    users (
        username,
        email,
        password_hash,
        role
    )
VALUES (
        'admin',
        'admin@tena.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    );

-- Insert sample data for testing
INSERT INTO
    registrations (
        email,
        first_name,
        last_name,
        property_type,
        property_count,
        location,
        phone,
        referral_source
    )
VALUES (
        'john.doe@example.com',
        'John',
        'Doe',
        'vacation_rental',
        2,
        'Miami, FL',
        '+1-555-0123',
        'google'
    ),
    (
        'jane.smith@example.com',
        'Jane',
        'Smith',
        'hotel',
        1,
        'Los Angeles, CA',
        '+1-555-0124',
        'facebook'
    ),
    (
        'mike.johnson@example.com',
        'Mike',
        'Johnson',
        'b&b',
        3,
        'Austin, TX',
        '+1-555-0125',
        'referral'
    ),
    (
        'sarah.wilson@example.com',
        'Sarah',
        'Wilson',
        'vacation_rental',
        1,
        'Denver, CO',
        '+1-555-0126',
        'instagram'
    ),
    (
        'david.brown@example.com',
        'David',
        'Brown',
        'other',
        2,
        'Seattle, WA',
        '+1-555-0127',
        'google'
    );

-- Insert sample analytics data
INSERT INTO
    analytics (
        metric_name,
        metric_value,
        date_recorded
    )
VALUES ('registrations', 5, CURDATE()),
    (
        'registrations',
        3,
        DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ),
    (
        'registrations',
        7,
        DATE_SUB(CURDATE(), INTERVAL 2 DAY)
    ),
    (
        'registrations',
        4,
        DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    ),
    (
        'registrations',
        6,
        DATE_SUB(CURDATE(), INTERVAL 4 DAY)
    ),
    (
        'conversion_rate',
        25.5,
        CURDATE()
    ),
    (
        'conversion_rate',
        30.2,
        DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ),
    (
        'conversion_rate',
        22.8,
        DATE_SUB(CURDATE(), INTERVAL 2 DAY)
    );

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL for system-wide notifications
    type ENUM(
        'success',
        'error',
        'warning',
        'info'
    ) NOT NULL DEFAULT 'info',
    category ENUM(
        'system',
        'user',
        'registration',
        'export'
    ) NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL, -- Additional data for the notification
    is_read BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL, -- Auto-expire notifications
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category ENUM(
        'system',
        'user',
        'registration',
        'export'
    ) NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    dashboard_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- Insert default notification preferences for existing users
INSERT IGNORE INTO
    notification_preferences (
        user_id,
        category,
        email_enabled,
        dashboard_enabled
    )
SELECT id, 'system', TRUE, TRUE
FROM users;

INSERT IGNORE INTO
    notification_preferences (
        user_id,
        category,
        email_enabled,
        dashboard_enabled
    )
SELECT id, 'user', TRUE, TRUE
FROM users;

INSERT IGNORE INTO
    notification_preferences (
        user_id,
        category,
        email_enabled,
        dashboard_enabled
    )
SELECT id, 'registration', TRUE, TRUE
FROM users;

INSERT IGNORE INTO
    notification_preferences (
        user_id,
        category,
        email_enabled,
        dashboard_enabled
    )
SELECT id, 'export', TRUE, TRUE
FROM users;

-- Sample notifications
INSERT INTO
    notifications (
        user_id,
        type,
        category,
        title,
        message,
        data
    )
VALUES (
        NULL,
        'info',
        'system',
        'System Update',
        'The waitlist system has been updated with new features.',
        '{"version": "1.0.0", "features": ["real-time updates", "notifications"]}'
    ),
    (
        NULL,
        'success',
        'registration',
        'New Registration',
        'A new user has joined the waitlist.',
        '{"registration_id": 1, "user_name": "John Doe"}'
    ),
    (
        NULL,
        'warning',
        'export',
        'Export Completed',
        'Your data export has been completed and is ready for download.',
        '{"export_id": 1, "file_name": "registrations_2024-01-15.csv"}'
    );
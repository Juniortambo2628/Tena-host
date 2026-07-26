-- Notifications System SQL
-- Add this to your database_setup.sql or run separately

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
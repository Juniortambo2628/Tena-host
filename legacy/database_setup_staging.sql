-- Staging-ready SQL for Tena Waitlist (for stayawhile-rentals.com)
-- Run on the staging server to create the tena_waitlist schema and tables

CREATE DATABASE IF NOT EXISTS `zhpebukm_tena_waitlist` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `zhpebukm_tena_waitlist`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME NULL
);

-- Registrations table (full fields)
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `location` VARCHAR(255) NULL,
    `business_name` VARCHAR(255) NULL,
    `business_website` VARCHAR(255) NULL,
    `business_phone` VARCHAR(50) NULL,
    `business_address` VARCHAR(255) NULL,
    `years_in_business` VARCHAR(50) NULL,
    `property_type` VARCHAR(50) NULL,
    `property_count` INT DEFAULT 1,
    `monthly_guests` INT NULL,
    `average_nightly_rate` DECIMAL(10, 2) NULL,
    `marketing_budget` DECIMAL(12, 2) NULL,
    `current_challenges` TEXT NULL,
    `expected_launch_date` DATE NULL,
    `referral_source` VARCHAR(255) NULL,
    `referral_source_details` VARCHAR(255) NULL,
    `additional_notes` TEXT NULL,
    `preferred_contact_method` VARCHAR(50) NULL,
    `timezone` VARCHAR(100) NULL,
    `language_preference` VARCHAR(50) NULL,
    `newsletter_subscription` TINYINT(1) DEFAULT 0,
    `marketing_consent` TINYINT(1) DEFAULT 0,
    `gdpr_consent` TINYINT(1) DEFAULT 0,
    `current_booking_platforms` TEXT NULL,
    `marketing_goals` TEXT NULL,
    `status` ENUM(
        'active',
        'inactive',
        'converted'
    ) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notifications and preferences
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `type` ENUM(
        'success',
        'error',
        'warning',
        'info'
    ) NOT NULL DEFAULT 'info',
    `category` ENUM(
        'system',
        'user',
        'registration',
        'export'
    ) NOT NULL DEFAULT 'system',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` JSON NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `is_archived` TINYINT(1) DEFAULT 0,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category` ENUM(
        'system',
        'user',
        'registration',
        'export'
    ) NOT NULL,
    `email_enabled` TINYINT(1) DEFAULT 1,
    `dashboard_enabled` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_category` (`user_id`, `category`)
);

-- Scheduled exports storage (simple jobs list)
CREATE TABLE IF NOT EXISTS `export_schedules` (
    `id` VARCHAR(100) PRIMARY KEY,
    `owner` INT NOT NULL,
    `type` VARCHAR(10) NOT NULL,
    `cron` VARCHAR(200) NOT NULL,
    `columns` JSON NULL,
    `filters` JSON NULL,
    `status` VARCHAR(50) DEFAULT 'active',
    `attempts` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Job history
CREATE TABLE IF NOT EXISTS `export_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_id` VARCHAR(100) NULL,
    `export_id` VARCHAR(100) NULL,
    `owner` INT NULL,
    `type` VARCHAR(10) NULL,
    `file_path` VARCHAR(1024) NULL,
    `status` VARCHAR(50) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (change password after deploy)
INSERT INTO
    `users` (
        `username`,
        `email`,
        `password_hash`,
        `role`
    )
VALUES (
        'admin',
        'admin@stayawhile-rentals.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    )
ON DUPLICATE KEY UPDATE
    email = VALUES(email);
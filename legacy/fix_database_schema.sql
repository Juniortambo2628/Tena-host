-- Fix database schema to match form fields
-- This migration adds missing columns and ensures backward compatibility

USE tena_waitlist;

-- Add country_code column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'tena_waitlist' 
    AND TABLE_NAME = 'registrations' 
    AND COLUMN_NAME = 'country_code');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE registrations ADD COLUMN country_code VARCHAR(10) AFTER email',
    'SELECT "Column country_code already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add phone_number column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'tena_waitlist' 
    AND TABLE_NAME = 'registrations' 
    AND COLUMN_NAME = 'phone_number');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE registrations ADD COLUMN phone_number VARCHAR(20) AFTER country_code',
    'SELECT "Column phone_number already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update old phone data to phone_number if phone column exists and phone_number is empty
UPDATE registrations 
SET phone_number = COALESCE(phone_number, phone)
WHERE (phone_number IS NULL OR phone_number = '') 
  AND phone IS NOT NULL 
  AND phone != ''
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_SCHEMA = 'tena_waitlist' 
              AND TABLE_NAME = 'registrations' 
              AND COLUMN_NAME = 'phone');

-- Ensure property_type enum includes the new values from the form
ALTER TABLE registrations 
MODIFY COLUMN property_type ENUM(
    'short_term_rental',
    'vacation_rental',
    'hotel',
    'b&b',
    'other'
) NOT NULL;

-- Ensure country column exists and has the right type (it should after running update_country_field.sql)
ALTER TABLE registrations 
MODIFY COLUMN country VARCHAR(100) DEFAULT NULL;

SELECT 'Database schema updated successfully!' AS status;

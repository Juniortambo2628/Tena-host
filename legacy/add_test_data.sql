-- Add comprehensive test data for dashboard testing
-- This script updates existing records and adds new test data

USE tena_waitlist;

-- First, update records with empty property_type to have valid values
UPDATE registrations 
SET property_type = 'vacation_rental' 
WHERE property_type IS NULL OR property_type = '' OR property_type NOT IN ('short_term_rental', 'vacation_rental');

-- Add diverse test data with the new schema
INSERT INTO registrations (
    first_name, last_name, email, country_code, phone_number, country,
    business_name, business_website, business_phone, business_address,
    years_in_business, property_type, property_count,
    referral_source, additional_notes, preferred_contact_method,
    timezone, language_preference, newsletter_subscription, marketing_consent,
    gdpr_consent, status, created_at
) VALUES
-- Test User 1: Short-term rental owner
(
    'Emma', 'Rodriguez', 'emma.rodriguez@example.com', '+1', '5551234567', 'United States',
    'Coastal Retreats LLC', 'https://coastalretreats.com', '+15559876543', '123 Beach Drive, Miami, FL 33139',
    '3-5', 'short_term_rental', 3,
    'google', 'Looking to expand direct booking channels', 'email',
    'America/New_York', 'en', 1, 1, 1, 'active', NOW() - INTERVAL 2 DAY
),

-- Test User 2: Vacation rental manager
(
    'Marcus', 'Chen', 'marcus.chen@example.com', '+44', '2071234567', 'United Kingdom',
    'London Luxury Stays', 'https://londonluxurystays.co.uk', '+442079876543', '45 Regent Street, London, W1B 2AD',
    '6-10', 'vacation_rental', 8,
    'linkedin', 'Managing multiple properties in central London', 'phone',
    'Europe/London', 'en', 1, 0, 1, 'active', NOW() - INTERVAL 5 DAY
),

-- Test User 3: New short-term rental host
(
    'Sofia', 'Martinez', 'sofia.martinez@example.com', '+34', '911234567', 'Spain',
    'Barcelona Beach Homes', 'https://bcnbeachhomes.es', '+34919876543', 'Carrer de Mallorca 401, Barcelona, 08013',
    '0-1', 'short_term_rental', 1,
    'facebook', 'First property, need help with marketing', 'sms',
    'Europe/Madrid', 'es', 1, 1, 1, 'active', NOW() - INTERVAL 1 DAY
),

-- Test User 4: Large vacation rental portfolio
(
    'James', 'Thompson', 'james.thompson@example.com', '+61', '291234567', 'Australia',
    'Aussie Coastal Properties', 'https://aussiecoastal.com.au', '+61298765432', '88 George Street, Sydney, NSW 2000',
    '10+', 'vacation_rental', 25,
    'conference', 'Expanding across Sydney and Gold Coast', 'email',
    'Australia/Sydney', 'en', 1, 1, 1, 'active', NOW() - INTERVAL 7 DAY
),

-- Test User 5: Short-term rental startup
(
    'Aisha', 'Patel', 'aisha.patel@example.com', '+91', '2212345678', 'India',
    'Mumbai Modern Stays', NULL, '+912298765432', 'Bandra West, Mumbai, Maharashtra 400050',
    '1-2', 'short_term_rental', 5,
    'instagram', 'Tech-savvy host looking for automation', 'email',
    'Asia/Kolkata', 'en', 1, 1, 1, 'active', NOW() - INTERVAL 3 DAY
),

-- Test User 6: Vacation rental in tourist destination
(
    'Pierre', 'Dubois', 'pierre.dubois@example.com', '+33', '142345678', 'France',
    'Paris Chic Apartments', 'https://parischic.fr', '+33142987654', '12 Rue de Rivoli, Paris, 75001',
    '3-5', 'vacation_rental', 12,
    'referral', 'High-end properties near Louvre and Eiffel Tower', 'phone',
    'Europe/Paris', 'fr', 0, 1, 1, 'active', NOW() - INTERVAL 4 DAY
),

-- Test User 7: Multi-property short-term rental
(
    'Yuki', 'Tanaka', 'yuki.tanaka@example.com', '+81', '312345678', 'Japan',
    'Tokyo Urban Rentals', 'https://tokyourban.jp', '+81398765432', 'Shibuya, Tokyo, 150-0002',
    '3-5', 'short_term_rental', 7,
    'google', 'Focusing on business travelers and tourists', 'email',
    'Asia/Tokyo', 'en', 1, 1, 1, 'active', NOW() - INTERVAL 6 DAY
),

-- Test User 8: Vacation rental with unique properties
(
    'Isabella', 'Romano', 'isabella.romano@example.com', '+39', '0612345678', 'Italy',
    'Tuscan Villa Collection', 'https://tuscanvillas.it', '+390698765432', 'Via Veneto 119, Rome, 00187',
    '6-10', 'vacation_rental', 15,
    'other', 'Historic villas and countryside estates', 'phone',
    'Europe/Rome', 'it', 1, 0, 1, 'active', NOW() - INTERVAL 8 DAY
),

-- Test User 9: Recent signup - short-term rental
(
    'Carlos', 'Silva', 'carlos.silva@example.com', '+351', '211234567', 'Portugal',
    'Lisbon Sunset Rentals', 'https://lisbonsunset.pt', '+351219876543', 'Avenida da Liberdade, Lisbon, 1250-096',
    '1-2', 'short_term_rental', 4,
    'instagram', 'Recently started, eager to learn best practices', 'sms',
    'Europe/Lisbon', 'pt', 1, 1, 1, 'active', NOW() - INTERVAL 12 HOUR
),

-- Test User 10: Large vacation rental company
(
    'Hannah', 'Schmidt', 'hannah.schmidt@example.com', '+49', '3012345678', 'Germany',
    'Berlin Urban Homes', 'https://berlinurbanhomes.de', '+493098765432', 'Friedrichstrasse 95, Berlin, 10117',
    '10+', 'vacation_rental', 40,
    'conference', 'Professional property management company', 'email',
    'Europe/Berlin', 'de', 1, 1, 1, 'active', NOW() - INTERVAL 10 DAY
),

-- Test User 11: Inactive status test
(
    'Mohammed', 'Al-Farsi', 'mohammed.alfarsi@example.com', '+971', '43123456', 'United Arab Emirates',
    'Dubai Luxury Suites', 'https://dubailuxury.ae', '+97143987654', 'Downtown Dubai, Dubai',
    '3-5', 'vacation_rental', 6,
    'linkedin', 'High-end properties in Dubai Marina', 'phone',
    'Asia/Dubai', 'en', 1, 1, 1, 'inactive', NOW() - INTERVAL 15 DAY
),

-- Test User 12: Converted status test
(
    'Maria', 'Santos', 'maria.santos@example.com', '+55', '1131234567', 'Brazil',
    'Rio Beach Houses', 'https://riobeachhouses.com.br', '+551198765432', 'Copacabana, Rio de Janeiro, 22070-002',
    '1-2', 'short_term_rental', 2,
    'facebook', 'Started with Tena 3 months ago', 'email',
    'America/Sao_Paulo', 'pt', 1, 1, 1, 'converted', NOW() - INTERVAL 90 DAY
);

-- Verify the data
SELECT 
    COUNT(*) as total_records,
    COUNT(CASE WHEN property_type = 'short_term_rental' THEN 1 END) as short_term_rentals,
    COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users,
    COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted_users
FROM registrations;

SELECT 'Test data added successfully!' as message;


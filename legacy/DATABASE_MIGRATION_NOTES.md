# Database Schema Migration Notes

## Overview
This document tracks the database schema changes and code updates needed to align the dashboard with the updated form and database structure.

## Database Schema Changes

### Fields Renamed
- `location` → `country` (stores the country name, e.g., "United States")

### Fields Added
- `country_code` (VARCHAR 10) - Phone country code (e.g., "+1")
- `phone_number` (VARCHAR 20) - Phone number without country code

### Fields Deprecated
- `phone` - Replaced by separate `country_code` + `phone_number` fields
- `location` - Replaced by `country` field

### Property Types Updated
The `property_type` enum now includes:
- `short_term_rental`
- `vacation_rental`
- `hotel`
- `b&b`
- `other`

## Migration Steps

### 1. Run SQL Migration Scripts (In Order)
```bash
# Step 1: Rename location to country
mysql -u your_user -p tena_waitlist < update_country_field.sql

# Step 2: Add new fields and update schema
mysql -u your_user -p tena_waitlist < enhance_registrations.sql

# Step 3: Add country_code and phone_number fields
mysql -u your_user -p tena_waitlist < fix_database_schema.sql
```

### 2. Code Files Updated
The following files have been updated to use the new field names:

#### Dashboard Files
- `dashboard.php` - Updated to use `country`, `country_code`, and `phone_number`
- `admin/users.php` - Updated location references
- `admin/users_old.php` - Updated location references

#### Export Files
- `simple_export.php` - CSV headers and data rows
- `admin/export.php` - CSV headers and data rows
- `admin/api/users_export.php` - Export API endpoint
- `admin/api/users_export_worker.php` - Background export worker
- `admin/pdf_export.php` - PDF export headers
- `admin/pdf_export_worker.php` - PDF export worker query

## Form Field Mapping

### Registration Form → Database Table

| Form Field | Database Column | Type | Required |
|------------|----------------|------|----------|
| `first_name` | `first_name` | VARCHAR(50) | Yes |
| `last_name` | `last_name` | VARCHAR(50) | Yes |
| `email` | `email` | VARCHAR(100) | Yes |
| `country` | `country` | VARCHAR(100) | Yes |
| `country_code` | `country_code` | VARCHAR(10) | Yes |
| `phone_number` | `phone_number` | VARCHAR(20) | Yes |
| `business_name` | `business_name` | VARCHAR(100) | Yes |
| `business_website` | `business_website` | VARCHAR(255) | No |
| `business_phone` | `business_phone` | VARCHAR(20) | No |
| `business_address` | `business_address` | TEXT | No |
| `years_in_business` | `years_in_business` | VARCHAR(20) | Yes |
| `property_type` | `property_type` | ENUM | Yes |
| `property_count` | `property_count` | INT | Yes |
| `preferred_contact_method` | `preferred_contact_method` | ENUM | Yes |
| `timezone` | `timezone` | VARCHAR(50) | No |
| `language_preference` | `language_preference` | VARCHAR(10) | No |
| `referral_source` | `referral_source` | VARCHAR(50) | No |
| `additional_notes` | `additional_notes` | TEXT | No |
| `newsletter_subscription` | `newsletter_subscription` | BOOLEAN | No |
| `marketing_consent` | `marketing_consent` | BOOLEAN | No |
| `gdpr_consent` | `gdpr_consent` | BOOLEAN | Yes |

## Display Format Changes

### Old Format
- **Location**: Displayed `location` field directly
- **Phone**: Displayed `phone` field directly

### New Format
- **Country**: Displays `country` field (e.g., "United States")
- **Phone**: Displays `country_code` + `phone_number` (e.g., "+1 5551234567")

## Testing Checklist

- [ ] Run all SQL migration scripts
- [ ] Verify database schema matches expected structure
- [ ] Test registration form submission
- [ ] Verify dashboard displays registrations correctly
- [ ] Test CSV export functionality
- [ ] Test PDF export functionality
- [ ] Test admin user management pages
- [ ] Verify modal registration details display correctly
- [ ] Check that phone numbers display with country code
- [ ] Verify country displays instead of location

## Potential Issues to Watch For

1. **Existing Data Migration**: Old records may have data in the `phone` and `location` columns but not in `phone_number`, `country_code`, and `country`. The migration script handles this by copying data from old columns to new ones.

2. **Property Type Validation**: Ensure form only submits `short_term_rental` or `vacation_rental` (per current form design).

3. **Export Filters**: Export functionality may need to be updated if filters reference old field names.

4. **API Endpoints**: Check if any API endpoints expect old field names in request/response.

## Rollback Plan

If issues occur, you can rollback by:

1. Reverting code changes using git
2. Running these SQL commands:
```sql
-- Restore old column names (only if needed)
ALTER TABLE registrations CHANGE COLUMN country location VARCHAR(100);
-- Copy phone data back if needed
UPDATE registrations SET phone = CONCAT(country_code, ' ', phone_number) 
WHERE phone IS NULL OR phone = '';
```

## Notes

- The `fix_database_schema.sql` script uses `ADD COLUMN IF NOT EXISTS` to be idempotent
- Old columns (`phone`, `location`) are preserved during migration for safety
- Manual cleanup of old columns can be done after verifying everything works
- All export files now include "Country" instead of "Location" in headers

## Date
Migration completed: October 1, 2025

## Version
Schema Version: 2.0


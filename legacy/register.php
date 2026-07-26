<?php

// Enhanced registration endpoint - saves to database with new fields
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'property_type', 'business_name', 'years_in_business', 'property_count', 'preferred_contact_method', 'gdpr_consent', 'country', 'country_code', 'phone_number'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: index.html?error=missing_fields');
        exit;
    }
}

// Sanitize and validate data
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
$country_code = trim($_POST['country_code'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$country = trim($_POST['country'] ?? '');
$business_name = trim($_POST['business_name']);
$business_website = trim($_POST['business_website'] ?? '');
$business_phone = trim($_POST['business_phone'] ?? '');
$business_address = trim($_POST['business_address'] ?? '');
$years_in_business = $_POST['years_in_business'];
$property_type = $_POST['property_type'];
$property_count = (int) ($_POST['property_count']);
// Removed business goals fields as per client requirements
$referral_source = trim($_POST['referral_source'] ?? '');
$referral_source_details = trim($_POST['referral_source_details'] ?? '');
$additional_notes = trim($_POST['additional_notes'] ?? '');
$preferred_contact_method = $_POST['preferred_contact_method'];
$timezone = trim($_POST['timezone'] ?? '');
$language_preference = trim($_POST['language_preference'] ?? 'en');
$newsletter_subscription = isset($_POST['newsletter_subscription']) ? 1 : 0;
$marketing_consent = isset($_POST['marketing_consent']) ? 1 : 0;
$gdpr_consent = isset($_POST['gdpr_consent']) ? 1 : 0;

// Removed JSON fields as per client requirements

if (! $email) {
    header('Location: index.html?error=invalid_email');
    exit;
}

// Validate property type
$valid_property_types = ['short_term_rental', 'vacation_rental'];
if (! in_array($property_type, $valid_property_types)) {
    $property_type = 'vacation_rental';
}

// Validate contact method
$valid_contact_methods = ['email', 'phone', 'sms'];
if (! in_array($preferred_contact_method, $valid_contact_methods)) {
    $preferred_contact_method = 'email';
}

try {
    $database = new Database;
    $db = $database->getConnection();

    // Check if email already exists
    $check_query = 'SELECT id FROM registrations WHERE email = :email';
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        header('Location: index.html?error=email_exists');
        exit;
    }

    // Insert new registration with updated fields
    $insert_query = "INSERT INTO registrations 
                     (first_name, last_name, email, country_code, phone_number, country, business_name, business_website, 
                      business_phone, business_address, years_in_business, property_type, property_count,
                      referral_source, additional_notes, preferred_contact_method, timezone, language_preference, 
                      newsletter_subscription, marketing_consent, gdpr_consent, status) 
                     VALUES 
                     (:first_name, :last_name, :email, :country_code, :phone_number, :country, :business_name, :business_website,
                      :business_phone, :business_address, :years_in_business, :property_type, :property_count,
                      :referral_source, :additional_notes, :preferred_contact_method, :timezone, :language_preference, 
                      :newsletter_subscription, :marketing_consent, :gdpr_consent, 'active')";

    $stmt = $db->prepare($insert_query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':country_code', $country_code);
    $stmt->bindParam(':phone_number', $phone_number);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':business_name', $business_name);
    $stmt->bindParam(':business_website', $business_website);
    $stmt->bindParam(':business_phone', $business_phone);
    $stmt->bindParam(':business_address', $business_address);
    $stmt->bindParam(':years_in_business', $years_in_business);
    $stmt->bindParam(':property_type', $property_type);
    $stmt->bindParam(':property_count', $property_count, PDO::PARAM_INT);
    $stmt->bindParam(':referral_source', $referral_source);
    $stmt->bindParam(':additional_notes', $additional_notes);
    $stmt->bindParam(':preferred_contact_method', $preferred_contact_method);
    $stmt->bindParam(':timezone', $timezone);
    $stmt->bindParam(':language_preference', $language_preference);
    $stmt->bindParam(':newsletter_subscription', $newsletter_subscription, PDO::PARAM_BOOL);
    $stmt->bindParam(':marketing_consent', $marketing_consent, PDO::PARAM_BOOL);
    $stmt->bindParam(':gdpr_consent', $gdpr_consent, PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $registration_id = $db->lastInsertId();

        // Also save to CSV for backup
        $dataDir = __DIR__.'/data';
        if (! is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        $file = $dataDir.'/registrations.csv';
        $fields = [
            'id', 'first_name', 'last_name', 'email', 'country_code', 'phone_number', 'country', 'business_name', 'business_website',
            'business_phone', 'business_address', 'years_in_business', 'property_type', 'property_count',
            'referral_source', 'additional_notes', 'preferred_contact_method', 'timezone', 'language_preference',
            'newsletter_subscription', 'marketing_consent', 'gdpr_consent', 'status', 'created_at',
        ];

        $row = [
            $registration_id,
            $first_name,
            $last_name,
            $email,
            $country_code,
            $phone_number,
            $country,
            $business_name,
            $business_website,
            $business_phone,
            $business_address,
            $years_in_business,
            $property_type,
            $property_count,
            $referral_source,
            $additional_notes,
            $preferred_contact_method,
            $timezone,
            $language_preference,
            $newsletter_subscription,
            $marketing_consent,
            $gdpr_consent,
            'active',
            date('Y-m-d H:i:s'),
        ];

        // If file doesn't exist, write header
        if (! file_exists($file)) {
            $f = fopen($file, 'w');
            fputcsv($f, $fields);
            fclose($f);
        }

        $f = fopen($file, 'a');
        fputcsv($f, $row);
        fclose($f);

        // Log successful registration
        error_log("New registration: ID $registration_id, Email: $email, Business: $business_name");

        header('Location: index.html?success=registered');
    } else {
        header('Location: index.html?error=database_error');
    }

} catch (Exception $e) {
    error_log('Registration error: '.$e->getMessage());
    header('Location: index.html?error=server_error');
}
exit;

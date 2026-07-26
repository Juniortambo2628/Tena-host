<?php

/**
 * System Test Script
 * Tests all components of the Tena Waitlist System
 */
echo "Tena Waitlist System - Component Test\n";
echo "=====================================\n\n";

// Test 1: Configuration Loading
echo "1. Testing Configuration Loading...\n";
try {
    require_once 'config/constants.php';
    echo "   ✅ Configuration loaded successfully\n";
    echo '   ✅ Environment: '.ENVIRONMENT."\n";
    echo '   ✅ Base URL: '.BASE_URL."\n";
} catch (Exception $e) {
    echo '   ❌ Configuration error: '.$e->getMessage()."\n";
}

// Test 2: Database Connection
echo "\n2. Testing Database Connection...\n";
try {
    require_once 'config/database.php';
    $database = new Database;
    $db = $database->getConnection();

    if ($db) {
        echo "   ✅ Database connection successful\n";

        // Test if tables exist
        $tables = ['users', 'registrations', 'notifications'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "   ✅ Table '$table' exists\n";
            } else {
                echo "   ⚠️  Table '$table' missing - run database_setup.sql\n";
            }
        }
    } else {
        echo "   ❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo '   ❌ Database error: '.$e->getMessage()."\n";
}

// Test 3: Composer Autoloader
echo "\n3. Testing Composer Autoloader...\n";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "   ✅ Composer autoloader loaded\n";

        // Test if packages are available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            echo "   ✅ PHPMailer available\n";
        } else {
            echo "   ⚠️  PHPMailer not found\n";
        }

        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            echo "   ✅ PhpSpreadsheet available\n";
        } else {
            echo "   ⚠️  PhpSpreadsheet not found\n";
        }

        if (class_exists('Dompdf\Dompdf')) {
            echo "   ✅ DomPDF available\n";
        } else {
            echo "   ⚠️  DomPDF not found\n";
        }
    } else {
        echo "   ❌ Composer autoloader not found\n";
    }
} catch (Exception $e) {
    echo '   ❌ Autoloader error: '.$e->getMessage()."\n";
}

// Test 4: Notification Manager
echo "\n4. Testing Notification Manager...\n";
try {
    if (class_exists('Tena\NotificationManager')) {
        echo "   ✅ NotificationManager class available\n";

        if (isset($db)) {
            $notificationManager = new \Tena\NotificationManager($db);
            echo "   ✅ NotificationManager instantiated successfully\n";
        } else {
            echo "   ⚠️  Cannot test NotificationManager - no database connection\n";
        }
    } else {
        echo "   ❌ NotificationManager class not found\n";
    }
} catch (Exception $e) {
    echo '   ❌ NotificationManager error: '.$e->getMessage()."\n";
}

// Test 5: Helper Functions
echo "\n5. Testing Helper Functions...\n";
try {
    // Test Common class methods
    $testEmail = 'test@example.com';
    if (Common::validateEmail($testEmail)) {
        echo "   ✅ Email validation working\n";
    } else {
        echo "   ❌ Email validation failed\n";
    }

    $testDate = '2024-01-15 10:30:00';
    $formatted = Common::formatDate($testDate);
    if (! empty($formatted)) {
        echo "   ✅ Date formatting working\n";
    } else {
        echo "   ❌ Date formatting failed\n";
    }

    $randomString = Common::generateRandomString(10);
    if (strlen($randomString) === 10) { // 10 chars requested
        echo "   ✅ Random string generation working\n";
    } else {
        echo '   ❌ Random string generation failed (got '.strlen($randomString)." chars)\n";
    }

} catch (Exception $e) {
    echo '   ❌ Helper functions error: '.$e->getMessage()."\n";
}

// Test 6: File Permissions
echo "\n6. Testing File Permissions...\n";
$writable_dirs = ['data', 'logs'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "   ✅ Directory '$dir' is writable\n";
        } else {
            echo "   ⚠️  Directory '$dir' is not writable\n";
        }
    } else {
        echo "   ⚠️  Directory '$dir' does not exist\n";
    }
}

// Test 7: AJAX Handler
echo "\n7. Testing AJAX Handler...\n";
if (file_exists('api/ajax_handler.php')) {
    echo "   ✅ AJAX handler file exists\n";

    // Test syntax
    $output = shell_exec('php -l api/ajax_handler.php 2>&1');
    if (strpos($output, 'No syntax errors') !== false) {
        echo "   ✅ AJAX handler syntax is valid\n";
    } else {
        echo "   ❌ AJAX handler has syntax errors\n";
    }
} else {
    echo "   ❌ AJAX handler file not found\n";
}

echo "\n".str_repeat('=', 50)."\n";
echo "System Test Complete!\n";
echo "\nNext steps:\n";
echo "1. Run database_setup.sql to create tables\n";
echo "2. Access http://localhost/Tena/auth/login.php\n";
echo "3. Login with: admin / password\n";
echo "4. Test the dashboard functionality\n";

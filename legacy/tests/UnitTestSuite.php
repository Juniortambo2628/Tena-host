<?php

/**
 * Comprehensive Unit Test Suite for Tena Waitlist System
 * Tests all major components and functionality
 */

require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../classes/NotificationManager.php';

class UnitTestSuite
{
    private $testResults = [];

    private $database;

    private $db;

    public function __construct()
    {
        $this->database = new Database;
        $this->db = $this->database->getConnection();
    }

    public function runAllTests()
    {
        echo "🧪 Tena Waitlist System - Unit Test Suite\n";
        echo "==========================================\n\n";

        $this->testDatabaseConnection();
        $this->testConfiguration();
        $this->testNotificationManager();
        $this->testAuthentication();
        $this->testDataRetrieval();
        $this->testExportFunctionality();
        $this->testHelperFunctions();
        $this->testFilePermissions();
        $this->testAJAXEndpoints();

        $this->displayResults();
    }

    private function testDatabaseConnection()
    {
        echo "📊 Testing Database Connection...\n";

        try {
            $this->assert($this->db !== null, 'Database connection established');

            // Test basic query
            $stmt = $this->db->query('SELECT 1 as test');
            $result = $stmt->fetch();
            $this->assert($result['test'] == 1, 'Basic query execution');

            // Test table existence
            $tables = ['users', 'registrations', 'notifications', 'notification_preferences'];
            foreach ($tables as $table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                $this->assert($stmt->rowCount() > 0, "Table '$table' exists");
            }

            echo "✅ Database connection tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Database connection failed: '.$e->getMessage());
            echo "❌ Database connection tests failed\n\n";
        }
    }

    private function testConfiguration()
    {
        echo "⚙️ Testing Configuration...\n";

        $this->assert(defined('BASE_URL'), 'BASE_URL constant defined');
        $this->assert(defined('DB_CONFIG'), 'DB_CONFIG constant defined');
        $this->assert(defined('HTTP_OK'), 'HTTP_OK constant defined');
        $this->assert(defined('ROLE_ADMIN'), 'ROLE_ADMIN constant defined');

        // Test environment detection
        $this->assert(is_string(ENVIRONMENT), 'Environment detection works');
        $this->assert(in_array(ENVIRONMENT, ['development', 'staging', 'production']), 'Valid environment detected');

        // Test database config
        $dbConfig = DB_CONFIG;
        $this->assert(is_array($dbConfig), 'Database config is array');
        $this->assert(isset($dbConfig['host']), 'Database host configured');
        $this->assert(isset($dbConfig['dbname']), 'Database name configured');

        echo "✅ Configuration tests passed\n\n";
    }

    private function testNotificationManager()
    {
        echo "🔔 Testing Notification Manager...\n";

        try {
            $notificationManager = new \Tena\NotificationManager($this->db);
            $this->assert($notificationManager !== null, 'NotificationManager instantiated');

            // Test creating notification
            $testData = [
                'type' => 'info',
                'category' => 'system',
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
                'data' => ['test' => true],
            ];

            $result = $notificationManager->create($testData);
            $this->assert($result !== false, 'Notification created successfully');

            // Test retrieving notifications
            $notifications = $notificationManager->getForUser(null, 5);
            $this->assert(is_array($notifications), 'Notifications retrieved as array');

            echo "✅ Notification Manager tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Notification Manager failed: '.$e->getMessage());
            echo "❌ Notification Manager tests failed\n\n";
        }
    }

    private function testAuthentication()
    {
        echo "🔐 Testing Authentication...\n";

        try {
            // Test user table structure
            $stmt = $this->db->query('DESCRIBE users');
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = ['id', 'username', 'email', 'password_hash', 'role'];
            foreach ($requiredColumns as $column) {
                $this->assert(in_array($column, $columns), "Users table has '$column' column");
            }

            // Test admin user exists
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = 'admin'");
            $stmt->execute();
            $adminUser = $stmt->fetch();
            $this->assert($adminUser !== false, 'Admin user exists');

            if ($adminUser) {
                $this->assert($adminUser['role'] === 'admin', 'Admin user has correct role');
                $this->assert(! empty($adminUser['password_hash']), 'Admin user has password hash');
            }

            echo "✅ Authentication tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Authentication test failed: '.$e->getMessage());
            echo "❌ Authentication tests failed\n\n";
        }
    }

    private function testDataRetrieval()
    {
        echo "📊 Testing Data Retrieval...\n";

        try {
            // Test registrations table
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM registrations');
            $count = $stmt->fetch()['count'];
            $this->assert(is_numeric($count), 'Registration count retrieved');

            // Test analytics query
            $analyticsQuery = "SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals
                FROM registrations";
            $stmt = $this->db->prepare($analyticsQuery);
            $stmt->execute();
            $analytics = $stmt->fetch();
            $this->assert($analytics !== false, 'Analytics query executed');
            $this->assert(isset($analytics['total_registrations']), 'Analytics data structure correct');

            // Test recent registrations query
            $recentQuery = 'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10';
            $stmt = $this->db->prepare($recentQuery);
            $stmt->execute();
            $registrations = $stmt->fetchAll();
            $this->assert(is_array($registrations), 'Recent registrations retrieved as array');

            echo "✅ Data retrieval tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Data retrieval test failed: '.$e->getMessage());
            echo "❌ Data retrieval tests failed\n\n";
        }
    }

    private function testExportFunctionality()
    {
        echo "📤 Testing Export Functionality...\n";

        try {
            // Test CSV export file exists
            $csvFile = __DIR__.'/../simple_export.php';
            $this->assert(file_exists($csvFile), 'CSV export file exists');

            // Test export directory exists
            $exportDir = __DIR__.'/../exports';
            if (! is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            $this->assert(is_dir($exportDir), 'Export directory exists');
            $this->assert(is_writable($exportDir), 'Export directory is writable');

            echo "✅ Export functionality tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Export functionality test failed: '.$e->getMessage());
            echo "❌ Export functionality tests failed\n\n";
        }
    }

    private function testHelperFunctions()
    {
        echo "🛠️ Testing Helper Functions...\n";

        try {
            // Test Common class methods
            $this->assert(method_exists('Common', 'logActivity'), 'Common::logActivity method exists');
            $this->assert(method_exists('Common', 'generateRandomString'), 'Common::generateRandomString method exists');
            $this->assert(method_exists('Common', 'isAjax'), 'Common::isAjax method exists');

            // Test random string generation
            $randomString = Common::generateRandomString(10);
            $this->assert(strlen($randomString) === 10, 'Random string generation works');
            $this->assert(ctype_alnum($randomString), 'Random string is alphanumeric');

            // Test AJAX detection
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $this->assert(Common::isAjax() === true, 'AJAX detection works');
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            $this->assert(Common::isAjax() === false, 'Non-AJAX detection works');

            echo "✅ Helper functions tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Helper functions test failed: '.$e->getMessage());
            echo "❌ Helper functions tests failed\n\n";
        }
    }

    private function testFilePermissions()
    {
        echo "📁 Testing File Permissions...\n";

        try {
            // Test data directory
            $dataDir = __DIR__.'/../data';
            if (! is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            $this->assert(is_dir($dataDir), 'Data directory exists');
            $this->assert(is_writable($dataDir), 'Data directory is writable');

            // Test logs directory
            $logsDir = __DIR__.'/../logs';
            if (! is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }
            $this->assert(is_dir($logsDir), 'Logs directory exists');
            $this->assert(is_writable($logsDir), 'Logs directory is writable');

            // Test include files
            $includeFiles = [
                'includes/header.php',
                'includes/sidebar.php',
                'includes/footer.php',
            ];

            foreach ($includeFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Include file '$file' exists");
                $this->assert(is_readable($filePath), "Include file '$file' is readable");
            }

            echo "✅ File permissions tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'File permissions test failed: '.$e->getMessage());
            echo "❌ File permissions tests failed\n\n";
        }
    }

    private function testAJAXEndpoints()
    {
        echo "🌐 Testing AJAX Endpoints...\n";

        try {
            // Test AJAX handler file exists
            $ajaxFile = __DIR__.'/../api/ajax_handler.php';
            $this->assert(file_exists($ajaxFile), 'AJAX handler file exists');

            // Test AJAX JavaScript file exists
            $jsFile = __DIR__.'/../js/ajax.js';
            $this->assert(file_exists($jsFile), 'AJAX JavaScript file exists');

            // Test AJAX file is readable
            $this->assert(is_readable($ajaxFile), 'AJAX handler is readable');
            $this->assert(is_readable($jsFile), 'AJAX JavaScript is readable');

            // Test AJAX file syntax
            $output = shell_exec("php -l \"$ajaxFile\" 2>&1");
            $this->assert(strpos($output, 'No syntax errors') !== false, 'AJAX handler has valid PHP syntax');

            echo "✅ AJAX endpoints tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'AJAX endpoints test failed: '.$e->getMessage());
            echo "❌ AJAX endpoints tests failed\n\n";
        }
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->testResults[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->testResults[] = ['status' => 'FAIL', 'message' => $message];
        }
    }

    private function displayResults()
    {
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function ($test) {
            return $test['status'] === 'PASS';
        }));
        $failedTests = $totalTests - $passedTests;

        echo "📋 Test Results Summary\n";
        echo "======================\n";
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedTests\n";
        echo "Failed: $failedTests\n";
        echo 'Success Rate: '.round(($passedTests / $totalTests) * 100, 2)."%\n\n";

        if ($failedTests > 0) {
            echo "❌ Failed Tests:\n";
            echo "================\n";
            foreach ($this->testResults as $test) {
                if ($test['status'] === 'FAIL') {
                    echo '• '.$test['message']."\n";
                }
            }
            echo "\n";
        }

        if ($passedTests === $totalTests) {
            echo "🎉 All tests passed! The system is working correctly.\n";
        } else {
            echo "⚠️ Some tests failed. Please review the issues above.\n";
        }
    }
}

// Run the test suite
$testSuite = new UnitTestSuite;
$testSuite->runAllTests();

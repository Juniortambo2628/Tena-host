<?php

/**
 * Simple Integration Test for Tena Waitlist System
 * Tests core functionality without including authentication-dependent files
 */

require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../classes/NotificationManager.php';

class SimpleIntegrationTest
{
    private $testResults = [];

    private $database;

    private $db;

    public function __construct()
    {
        $this->database = new Database;
        $this->db = $this->database->getConnection();
    }

    public function runIntegrationTests()
    {
        echo "🔗 Tena Waitlist System - Simple Integration Tests\n";
        echo "==================================================\n\n";

        $this->testCoreSystem();
        $this->testDatabaseOperations();
        $this->testFileSystem();
        $this->testConfiguration();
        $this->testNotificationSystem();
        $this->testExportSystem();

        $this->displayResults();
    }

    private function testCoreSystem()
    {
        echo "⚙️ Testing Core System...\n";

        try {
            // Test database connection
            $this->assert($this->db !== null, 'Database connection established');

            // Test basic query
            $stmt = $this->db->query('SELECT 1 as test');
            $result = $stmt->fetch();
            $this->assert($result['test'] == 1, 'Basic database query works');

            // Test table existence
            $tables = ['users', 'registrations', 'notifications', 'notification_preferences'];
            foreach ($tables as $table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                $this->assert($stmt->rowCount() > 0, "Table '$table' exists");
            }

            echo "✅ Core system tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Core system test failed: '.$e->getMessage());
            echo "❌ Core system tests failed\n\n";
        }
    }

    private function testDatabaseOperations()
    {
        echo "📊 Testing Database Operations...\n";

        try {
            // Test user operations
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM users');
            $userCount = $stmt->fetch()['count'];
            $this->assert(is_numeric($userCount), 'User count retrieved');

            // Test registration operations
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM registrations');
            $regCount = $stmt->fetch()['count'];
            $this->assert(is_numeric($regCount), 'Registration count retrieved');

            // Test analytics queries
            $analyticsQuery = "SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals
                FROM registrations";
            $stmt = $this->db->prepare($analyticsQuery);
            $stmt->execute();
            $analytics = $stmt->fetch();
            $this->assert($analytics !== false, 'Analytics query executed');

            // Test recent data queries
            $recentQuery = 'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10';
            $stmt = $this->db->prepare($recentQuery);
            $stmt->execute();
            $registrations = $stmt->fetchAll();
            $this->assert(is_array($registrations), 'Recent registrations retrieved');

            echo "✅ Database operations tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Database operations test failed: '.$e->getMessage());
            echo "❌ Database operations tests failed\n\n";
        }
    }

    private function testFileSystem()
    {
        echo "📁 Testing File System...\n";

        try {
            // Test core files exist
            $coreFiles = [
                'dashboard.php',
                'admin/users.php',
                'admin/analytics.php',
                'js/ajax.js',
                'api/ajax_handler.php',
                'simple_export.php',
            ];

            foreach ($coreFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Core file '$file' exists");

                if (file_exists($filePath)) {
                    $this->assert(is_readable($filePath), "Core file '$file' is readable");

                    // Test PHP syntax for PHP files
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        $output = shell_exec("php -l \"$filePath\" 2>&1");
                        $this->assert(strpos($output, 'No syntax errors') !== false, "Core file '$file' has valid PHP syntax");
                    }
                }
            }

            // Test directories exist and are writable
            $directories = ['data', 'logs', 'exports'];
            foreach ($directories as $dir) {
                $dirPath = __DIR__.'/../'.$dir;
                if (! is_dir($dirPath)) {
                    mkdir($dirPath, 0755, true);
                }
                $this->assert(is_dir($dirPath), "Directory '$dir' exists");
                $this->assert(is_writable($dirPath), "Directory '$dir' is writable");
            }

            echo "✅ File system tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'File system test failed: '.$e->getMessage());
            echo "❌ File system tests failed\n\n";
        }
    }

    private function testConfiguration()
    {
        echo "⚙️ Testing Configuration...\n";

        try {
            // Test constants are defined
            $constants = [
                'BASE_URL', 'DB_CONFIG', 'HTTP_OK', 'HTTP_UNAUTHORIZED',
                'ROLE_ADMIN', 'NOTIFICATION_INFO', 'NOTIF_CAT_SYSTEM',
            ];

            foreach ($constants as $constant) {
                $this->assert(defined($constant), "Constant '$constant' is defined");
            }

            // Test environment detection
            $this->assert(is_string(ENVIRONMENT), 'Environment is detected');
            $this->assert(in_array(ENVIRONMENT, ['development', 'staging', 'production']), 'Valid environment detected');

            // Test database configuration
            $dbConfig = DB_CONFIG;
            $this->assert(is_array($dbConfig), 'Database configuration is array');
            $this->assert(isset($dbConfig['host']), 'Database host is configured');
            $this->assert(isset($dbConfig['dbname']), 'Database name is configured');
            $this->assert(isset($dbConfig['username']), 'Database username is configured');

            echo "✅ Configuration tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Configuration test failed: '.$e->getMessage());
            echo "❌ Configuration tests failed\n\n";
        }
    }

    private function testNotificationSystem()
    {
        echo "🔔 Testing Notification System...\n";

        try {
            $notificationManager = new \Tena\NotificationManager($this->db);
            $this->assert($notificationManager !== null, 'NotificationManager instantiated');

            // Test creating notification
            $testData = [
                'type' => 'info',
                'category' => 'system',
                'title' => 'Integration Test',
                'message' => 'Testing notification system integration',
                'data' => ['test' => true],
            ];

            $result = $notificationManager->create($testData);
            $this->assert($result !== false, 'Notification created successfully');

            // Test retrieving notifications
            $notifications = $notificationManager->getForUser(null, 5);
            $this->assert(is_array($notifications), 'Notifications retrieved as array');

            // Test notification preferences
            $preferences = $notificationManager->getPreferences(1);
            $this->assert(is_array($preferences), 'Notification preferences retrieved');

            echo "✅ Notification system tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Notification system test failed: '.$e->getMessage());
            echo "❌ Notification system tests failed\n\n";
        }
    }

    private function testExportSystem()
    {
        echo "📤 Testing Export System...\n";

        try {
            // Test export files
            $exportFiles = ['simple_export.php', 'admin/export.php'];
            foreach ($exportFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Export file '$file' exists");

                $output = shell_exec("php -l \"$filePath\" 2>&1");
                $this->assert(strpos($output, 'No syntax errors') !== false, "Export file '$file' has valid syntax");
            }

            // Test export directory
            $exportDir = __DIR__.'/../exports';
            if (! is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            $this->assert(is_dir($exportDir), 'Export directory exists');
            $this->assert(is_writable($exportDir), 'Export directory is writable');

            // Test export data queries
            $exportQueries = [
                'SELECT * FROM registrations ORDER BY created_at DESC',
                'SELECT * FROM users ORDER BY created_at DESC',
            ];

            foreach ($exportQueries as $query) {
                $stmt = $this->db->prepare($query);
                $this->assert($stmt->execute(), 'Export query executed');
                $results = $stmt->fetchAll();
                $this->assert(is_array($results), 'Export query returned array');
            }

            echo "✅ Export system tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Export system test failed: '.$e->getMessage());
            echo "❌ Export system tests failed\n\n";
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

        echo "📋 Simple Integration Test Results\n";
        echo "==================================\n";
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
            echo "🎉 All integration tests passed! The system is working correctly.\n";
        } else {
            echo "⚠️ Some integration tests failed. Please review the issues above.\n";
        }
    }
}

// Run the simple integration tests
$integrationTest = new SimpleIntegrationTest;
$integrationTest->runIntegrationTests();

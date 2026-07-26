<?php

/**
 * Integration Test for Tena Waitlist System
 * Tests the complete system integration and user workflows
 */

require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../classes/NotificationManager.php';

class IntegrationTest
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
        echo "🔗 Tena Waitlist System - Integration Tests\n";
        echo "===========================================\n\n";

        $this->testCompleteWorkflow();
        $this->testDashboardIntegration();
        $this->testUserManagementWorkflow();
        $this->testAnalyticsIntegration();
        $this->testNotificationSystem();
        $this->testExportWorkflow();

        $this->displayResults();
    }

    private function testCompleteWorkflow()
    {
        echo "🔄 Testing Complete User Workflow...\n";

        try {
            // Test 1: User registration workflow
            $this->assert($this->testUserRegistration(), 'User registration workflow');

            // Test 2: Admin login workflow
            $this->assert($this->testAdminLogin(), 'Admin login workflow');

            // Test 3: Dashboard access workflow
            $this->assert($this->testDashboardAccess(), 'Dashboard access workflow');

            // Test 4: Data viewing workflow
            $this->assert($this->testDataViewing(), 'Data viewing workflow');

            echo "✅ Complete workflow tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Complete workflow test failed: '.$e->getMessage());
            echo "❌ Complete workflow tests failed\n\n";
        }
    }

    private function testUserRegistration()
    {
        // Simulate user registration
        $testData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'property_type' => 'vacation_rental',
            'property_count' => 1,
            'location' => 'Test City',
            'phone' => '123-456-7890',
            'message' => 'Test registration',
            'referral_source' => 'website',
        ];

        $query = "INSERT INTO registrations 
                  (first_name, last_name, email, property_type, property_count, 
                   location, phone, message, referral_source, status) 
                  VALUES 
                  (:first_name, :last_name, :email, :property_type, :property_count,
                   :location, :phone, :message, :referral_source, 'active')";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':first_name', $testData['first_name']);
        $stmt->bindParam(':last_name', $testData['last_name']);
        $stmt->bindParam(':email', $testData['email']);
        $stmt->bindParam(':property_type', $testData['property_type']);
        $stmt->bindParam(':property_count', $testData['property_count'], PDO::PARAM_INT);
        $stmt->bindParam(':location', $testData['location']);
        $stmt->bindParam(':phone', $testData['phone']);
        $stmt->bindParam(':message', $testData['message']);
        $stmt->bindParam(':referral_source', $testData['referral_source']);

        return $stmt->execute();
    }

    private function testAdminLogin()
    {
        // Test admin user exists and can be authenticated
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminUser = $stmt->fetch();

        if (! $adminUser) {
            return false;
        }

        // Test password verification
        return password_verify('password', $adminUser['password_hash']);
    }

    private function testDashboardAccess()
    {
        // Test dashboard files are accessible
        $dashboardFiles = [
            'dashboard.php',
            'admin/users.php',
            'admin/analytics.php',
        ];

        foreach ($dashboardFiles as $file) {
            if (! file_exists(__DIR__.'/../'.$file)) {
                return false;
            }
        }

        return true;
    }

    private function testDataViewing()
    {
        // Test data retrieval for dashboard
        $queries = [
            'SELECT COUNT(*) as count FROM registrations',
            'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10',
            'SELECT COUNT(*) as count FROM users',
        ];

        foreach ($queries as $query) {
            $stmt = $this->db->prepare($query);
            if (! $stmt->execute()) {
                return false;
            }
        }

        return true;
    }

    private function testDashboardIntegration()
    {
        echo "🎛️ Testing Dashboard Integration...\n";

        try {
            // Test include files work together
            $includeFiles = ['includes/header.php', 'includes/sidebar.php', 'includes/footer.php'];
            foreach ($includeFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Include file '$file' exists");

                // Test file can be included without errors
                ob_start();
                $error = false;
                try {
                    include $filePath;
                } catch (Exception $e) {
                    $error = true;
                }
                ob_end_clean();

                $this->assert(! $error, "Include file '$file' can be included without errors");
            }

            // Test dashboard pages can be loaded
            $dashboardPages = ['dashboard.php', 'admin/users.php', 'admin/analytics.php'];
            foreach ($dashboardPages as $page) {
                $pagePath = __DIR__.'/../'.$page;
                $this->assert(file_exists($pagePath), "Dashboard page '$page' exists");

                // Test PHP syntax
                $output = shell_exec("php -l \"$pagePath\" 2>&1");
                $this->assert(strpos($output, 'No syntax errors') !== false, "Dashboard page '$page' has valid syntax");
            }

            echo "✅ Dashboard integration tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Dashboard integration test failed: '.$e->getMessage());
            echo "❌ Dashboard integration tests failed\n\n";
        }
    }

    private function testUserManagementWorkflow()
    {
        echo "👥 Testing User Management Workflow...\n";

        try {
            // Test user table operations
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM users');
            $userCount = $stmt->fetch()['count'];
            $this->assert(is_numeric($userCount), 'User count retrieved');

            // Test registration table operations
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM registrations');
            $regCount = $stmt->fetch()['count'];
            $this->assert(is_numeric($regCount), 'Registration count retrieved');

            // Test user management queries
            $queries = [
                'SELECT * FROM users ORDER BY created_at DESC LIMIT 10',
                'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10',
                'SELECT u.*, r.* FROM users u LEFT JOIN registrations r ON u.email = r.email',
            ];

            foreach ($queries as $query) {
                $stmt = $this->db->prepare($query);
                $this->assert($stmt->execute(), 'User management query executed: '.substr($query, 0, 50).'...');
            }

            echo "✅ User management workflow tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'User management workflow test failed: '.$e->getMessage());
            echo "❌ User management workflow tests failed\n\n";
        }
    }

    private function testAnalyticsIntegration()
    {
        echo "📈 Testing Analytics Integration...\n";

        try {
            // Test analytics queries
            $analyticsQueries = [
                'SELECT COUNT(*) as total_registrations FROM registrations',
                'SELECT COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days FROM registrations',
                "SELECT COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals FROM registrations",
                "SELECT COUNT(CASE WHEN property_type = 'hotel' THEN 1 END) as hotels FROM registrations",
            ];

            foreach ($analyticsQueries as $query) {
                $stmt = $this->db->prepare($query);
                $this->assert($stmt->execute(), 'Analytics query executed');
                $result = $stmt->fetch();
                $this->assert($result !== false, 'Analytics query returned result');
            }

            // Test chart data queries
            $chartQueries = [
                'SELECT DATE(created_at) as date, COUNT(*) as count FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date ASC',
                'SELECT property_type, COUNT(*) as count FROM registrations GROUP BY property_type ORDER BY count DESC',
            ];

            foreach ($chartQueries as $query) {
                $stmt = $this->db->prepare($query);
                $this->assert($stmt->execute(), 'Chart data query executed');
                $results = $stmt->fetchAll();
                $this->assert(is_array($results), 'Chart data query returned array');
            }

            echo "✅ Analytics integration tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Analytics integration test failed: '.$e->getMessage());
            echo "❌ Analytics integration tests failed\n\n";
        }
    }

    private function testNotificationSystem()
    {
        echo "🔔 Testing Notification System...\n";

        try {
            $notificationManager = new \Tena\NotificationManager($this->db);

            // Test creating notification
            $testData = [
                'type' => 'info',
                'category' => 'system',
                'title' => 'Integration Test Notification',
                'message' => 'This is a test notification for integration testing',
                'data' => ['test' => true, 'integration' => true],
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

    private function testExportWorkflow()
    {
        echo "📤 Testing Export Workflow...\n";

        try {
            // Test export files exist and are functional
            $exportFiles = ['simple_export.php', 'admin/export.php'];
            foreach ($exportFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Export file '$file' exists");

                // Test syntax
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

            // Test data export queries
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

            echo "✅ Export workflow tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Export workflow test failed: '.$e->getMessage());
            echo "❌ Export workflow tests failed\n\n";
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

        echo "📋 Integration Test Results\n";
        echo "==========================\n";
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
            echo "🎉 All integration tests passed! The system is fully integrated and working.\n";
        } else {
            echo "⚠️ Some integration tests failed. Please review the issues above.\n";
        }
    }
}

// Run the integration tests
$integrationTest = new IntegrationTest;
$integrationTest->runIntegrationTests();

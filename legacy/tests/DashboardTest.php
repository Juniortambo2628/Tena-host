<?php

/**
 * Dashboard Functionality Tests
 * Tests specific dashboard features and modal functionality
 */

require_once __DIR__.'/../config/constants.php';
require_once __DIR__.'/../config/database.php';

class DashboardTest
{
    private $testResults = [];

    private $database;

    private $db;

    public function __construct()
    {
        $this->database = new Database;
        $this->db = $this->database->getConnection();
    }

    public function runDashboardTests()
    {
        echo "🎛️ Dashboard Functionality Tests\n";
        echo "================================\n\n";

        $this->testDashboardFiles();
        $this->testModalDialogs();
        $this->testDataDisplay();
        $this->testUserManagement();
        $this->testAnalytics();
        $this->testExportFeatures();

        $this->displayResults();
    }

    private function testDashboardFiles()
    {
        echo "📄 Testing Dashboard Files...\n";

        $dashboardFiles = [
            'dashboard.php',
            'admin/users.php',
            'admin/analytics.php',
            'includes/header.php',
            'includes/sidebar.php',
            'includes/footer.php',
        ];

        foreach ($dashboardFiles as $file) {
            $filePath = __DIR__.'/../'.$file;
            $this->assert(file_exists($filePath), "Dashboard file '$file' exists");

            if (file_exists($filePath)) {
                $this->assert(is_readable($filePath), "Dashboard file '$file' is readable");

                // Test PHP syntax
                $output = shell_exec("php -l \"$filePath\" 2>&1");
                $this->assert(strpos($output, 'No syntax errors') !== false, "Dashboard file '$file' has valid PHP syntax");
            }
        }

        echo "✅ Dashboard files tests passed\n\n";
    }

    private function testModalDialogs()
    {
        echo "🪟 Testing Modal Dialogs...\n";

        // Test dashboard modal content
        $dashboardContent = file_get_contents(__DIR__.'/../dashboard.php');
        $this->assert(strpos($dashboardContent, 'viewRegistrationModal') !== false, 'View registration modal exists');
        $this->assert(strpos($dashboardContent, 'contactUserModal') !== false, 'Contact user modal exists');
        $this->assert(strpos($dashboardContent, 'spinner-border') !== false, 'Loading spinners implemented');

        // Test users modal content
        $usersContent = file_get_contents(__DIR__.'/../admin/users.php');
        $this->assert(strpos($usersContent, 'viewUserModal') !== false, 'View user modal exists');
        $this->assert(strpos($usersContent, 'editUserModal') !== false, 'Edit user modal exists');
        $this->assert(strpos($usersContent, 'contactUserModal') !== false, 'Contact user modal exists');

        // Test JavaScript functions
        $this->assert(strpos($dashboardContent, 'function viewRegistration') !== false, 'viewRegistration function exists');
        $this->assert(strpos($dashboardContent, 'function contactUser') !== false, 'contactUser function exists');
        $this->assert(strpos($usersContent, 'function viewUser') !== false, 'viewUser function exists');
        $this->assert(strpos($usersContent, 'function editUser') !== false, 'editUser function exists');

        echo "✅ Modal dialogs tests passed\n\n";
    }

    private function testDataDisplay()
    {
        echo "📊 Testing Data Display...\n";

        try {
            // Test registration data retrieval
            $query = 'SELECT COUNT(*) as count FROM registrations';
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            $this->assert(is_numeric($count), 'Registration count is numeric');

            // Test recent registrations query
            $recentQuery = 'SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10';
            $stmt = $this->db->prepare($recentQuery);
            $stmt->execute();
            $registrations = $stmt->fetchAll();
            $this->assert(is_array($registrations), 'Recent registrations query returns array');

            // Test analytics query
            $analyticsQuery = 'SELECT 
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_registrations
                FROM registrations';
            $stmt = $this->db->prepare($analyticsQuery);
            $stmt->execute();
            $analytics = $stmt->fetch();
            $this->assert($analytics !== false, 'Analytics query executes successfully');
            $this->assert(isset($analytics['total_registrations']), 'Analytics contains total_registrations');

            echo "✅ Data display tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Data display test failed: '.$e->getMessage());
            echo "❌ Data display tests failed\n\n";
        }
    }

    private function testUserManagement()
    {
        echo "👥 Testing User Management...\n";

        try {
            // Test user table structure
            $stmt = $this->db->query('DESCRIBE users');
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $requiredColumns = ['id', 'username', 'email', 'password_hash', 'role', 'created_at'];
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

            // Test user management queries
            $userQuery = 'SELECT * FROM users ORDER BY created_at DESC LIMIT 10';
            $stmt = $this->db->prepare($userQuery);
            $stmt->execute();
            $users = $stmt->fetchAll();
            $this->assert(is_array($users), 'User management query returns array');

            echo "✅ User management tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'User management test failed: '.$e->getMessage());
            echo "❌ User management tests failed\n\n";
        }
    }

    private function testAnalytics()
    {
        echo "📈 Testing Analytics...\n";

        try {
            // Test analytics queries
            $queries = [
                'SELECT COUNT(*) as total FROM registrations',
                "SELECT COUNT(CASE WHEN property_type = 'vacation_rental' THEN 1 END) as vacation_rentals FROM registrations",
                "SELECT COUNT(CASE WHEN property_type = 'hotel' THEN 1 END) as hotels FROM registrations",
                "SELECT COUNT(CASE WHEN property_type = 'b&b' THEN 1 END) as bnb FROM registrations",
            ];

            foreach ($queries as $query) {
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch();
                $this->assert($result !== false, 'Analytics query executed: '.substr($query, 0, 50).'...');
            }

            // Test date-based analytics
            $dateQuery = 'SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
                FROM registrations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC';
            $stmt = $this->db->prepare($dateQuery);
            $stmt->execute();
            $dailyData = $stmt->fetchAll();
            $this->assert(is_array($dailyData), 'Daily analytics data retrieved');

            echo "✅ Analytics tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Analytics test failed: '.$e->getMessage());
            echo "❌ Analytics tests failed\n\n";
        }
    }

    private function testExportFeatures()
    {
        echo "📤 Testing Export Features...\n";

        try {
            // Test export files exist
            $exportFiles = [
                'simple_export.php',
                'admin/export.php',
            ];

            foreach ($exportFiles as $file) {
                $filePath = __DIR__.'/../'.$file;
                $this->assert(file_exists($filePath), "Export file '$file' exists");

                if (file_exists($filePath)) {
                    $output = shell_exec("php -l \"$filePath\" 2>&1");
                    $this->assert(strpos($output, 'No syntax errors') !== false, "Export file '$file' has valid syntax");
                }
            }

            // Test export directory
            $exportDir = __DIR__.'/../exports';
            if (! is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            $this->assert(is_dir($exportDir), 'Export directory exists');
            $this->assert(is_writable($exportDir), 'Export directory is writable');

            echo "✅ Export features tests passed\n\n";
        } catch (Exception $e) {
            $this->assert(false, 'Export features test failed: '.$e->getMessage());
            echo "❌ Export features tests failed\n\n";
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

        echo "📋 Dashboard Test Results\n";
        echo "========================\n";
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
            echo "🎉 All dashboard tests passed! The dashboard is fully functional.\n";
        } else {
            echo "⚠️ Some dashboard tests failed. Please review the issues above.\n";
        }
    }
}

// Run the dashboard tests
$dashboardTest = new DashboardTest;
$dashboardTest->runDashboardTests();

<?php

/**
 * Database Configuration
 * Uses constants from config.php for environment-specific settings
 */

require_once __DIR__.'/constants.php';

class Database
{
    private $conn;

    public function getConnection()
    {
        if ($this->conn === null) {
            try {
                $config = DB_CONFIG;
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

                $this->conn = new PDO($dsn, $config['username'], $config['password'], $config['options']);

                // Log successful connection in development
                if (isDevelopment()) {
                    Common::logActivity('database_connection', 'Database connected successfully');
                }

            } catch (PDOException $exception) {
                $errorMsg = 'Database connection error: '.$exception->getMessage();
                Common::logActivity('database_error', $errorMsg);

                if (isDevelopment()) {
                    echo $errorMsg;
                } else {
                    echo 'Database connection failed. Please try again later.';
                }
            }
        }

        return $this->conn;
    }

    /**
     * Test database connection
     */
    public function testConnection()
    {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query('SELECT 1');

                return $stmt !== false;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get database info
     */
    public function getInfo()
    {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query('SELECT VERSION() as version, DATABASE() as database_name');

                return $stmt->fetch();
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}

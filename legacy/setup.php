<?php

/**
 * Tena Waitlist System Setup
 * Simple setup without Composer dependencies
 */
echo "Tena Waitlist System Setup\n";
echo "==========================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo '❌ PHP 7.4 or higher is required. Current version: '.PHP_VERSION."\n";
    exit(1);
}
echo '✅ PHP version: '.PHP_VERSION."\n";

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (! extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (! empty($missing_extensions)) {
    echo '❌ Missing required extensions: '.implode(', ', $missing_extensions)."\n";
    exit(1);
}
echo "✅ All required extensions are loaded\n";

// Create necessary directories
$directories = [
    'data',
    'logs',
    'vendor',
    'config',
    'classes',
    'api',
    'auth',
    'admin',
];

foreach ($directories as $dir) {
    if (! is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Test database connection
echo "\nTesting database connection...\n";
try {
    require_once 'config/database.php';
    $database = new Database;
    $db = $database->getConnection();

    if ($db) {
        echo "✅ Database connection successful\n";

        // Test if tables exist
        $tables = ['users', 'registrations', 'notifications'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "⚠️  Table '$table' does not exist - run database_setup.sql\n";
            }
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo '❌ Database error: '.$e->getMessage()."\n";
}

// Check file permissions
echo "\nChecking file permissions...\n";
$writable_dirs = ['data', 'logs'];
foreach ($writable_dirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ Directory '$dir' is writable\n";
    } else {
        echo "⚠️  Directory '$dir' is not writable - may cause issues\n";
    }
}

// Create .htaccess for security
$htaccess_content = '# Tena Waitlist System Security
# Deny access to sensitive files
<Files "*.sql">
    Order Allow,Deny
    Deny from all
</Files>

<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>

<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "constants.php">
    Order Allow,Deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
';

if (file_put_contents('.htaccess', $htaccess_content)) {
    echo "✅ Created .htaccess security file\n";
} else {
    echo "⚠️  Could not create .htaccess file\n";
}

// Create sample environment file
$env_content = '# Tena Waitlist System Environment Configuration
# Copy this file to .env and modify as needed

# Database Configuration
DB_HOST=localhost
DB_NAME=tena_waitlist
DB_USER=root
DB_PASS=

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/Tena

# Security
SESSION_LIFETIME=3600
PASSWORD_MIN_LENGTH=8

# Email Settings (optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_FROM_NAME=Tena System
MAIL_FROM_EMAIL=noreply@tena.com
';

if (file_put_contents('.env.example', $env_content)) {
    echo "✅ Created .env.example file\n";
} else {
    echo "⚠️  Could not create .env.example file\n";
}

echo "\n🎉 Setup completed!\n\n";
echo "Next steps:\n";
echo "1. Run the database setup: SOURCE database_setup.sql;\n";
echo "2. Copy .env.example to .env and configure your settings\n";
echo "3. Access the system at: http://localhost/Tena\n";
echo "4. Login with: admin / password\n\n";
echo "For detailed setup instructions, see REFACTORED_SETUP_GUIDE.md\n";

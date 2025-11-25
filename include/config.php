<?php
/**
 * Database Configuration
 * 
 * Environment-based configuration for database connections
 * Supports different environments: development, staging, production
 */

// Define application environment
defined('APP_ENV') || define('APP_ENV', getEnvironment());

// Database configuration based on environment
$dbConfig = getDatabaseConfig(APP_ENV);

// Extract configuration variables
$host = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];
$port = $dbConfig['port'];
$charset = $dbConfig['charset'];

/**
 * Get current environment
 */
function getEnvironment()
{
    // Check from environment variable first
    if ($env = getenv('APP_ENV')) {
        return $env;
    }

    // Check from server hostname
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    if (in_array($serverName, ['localhost', '127.0.0.1', '::1'])) {
        return 'development';
    } elseif (strpos($serverName, 'staging.') !== false || strpos($serverName, 'test.') !== false) {
        return 'staging';
    } else {
        return 'production';
    }
}

/**
 * Get database configuration for environment
 */
function getDatabaseConfig($environment)
{
    $configs = [
        'development' => [
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'database' => 'correspondence-management',
            'port' => 3306,
            'charset' => 'utf8mb4'
        ],
        'staging' => [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USERNAME') ?: 'staging_user',
            'password' => getenv('DB_PASSWORD') ?: 'staging_password',
            'database' => getenv('DB_DATABASE') ?: 'correspondence_management_staging',
            'port' => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8mb4'
        ],
        'production' => [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'username' => getenv('DB_USERNAME') ?: 'prod_user',
            'password' => getenv('DB_PASSWORD') ?: 'secure_password',
            'database' => getenv('DB_DATABASE') ?: 'correspondence_management_prod',
            'port' => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8mb4'
        ]
    ];

    // Validate environment
    if (!isset($configs[$environment])) {
        throw new InvalidArgumentException("Invalid environment: {$environment}");
    }

    return $configs[$environment];
}

/**
 * Create DSN string for PDO connections
 */
function getDsn($host, $database, $port = 3306, $charset = 'utf8mb4')
{
    return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
}

/**
 * Validate database configuration
 */
function validateDatabaseConfig($config)
{
    $required = ['host', 'username', 'password', 'database'];

    foreach ($required as $key) {
        if (!isset($config[$key])) {
            throw new RuntimeException("Missing required database configuration: {$key}");
        }
    }

    return true;
}

/**
 * Get database connection options
 */
function getConnectionOptions()
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT => false
    ];
}

// Validate current configuration
try {
    validateDatabaseConfig($dbConfig);
} catch (Exception $e) {
    error_log("Database configuration error: " . $e->getMessage());

    // In development, show errors; in production, log silently
    if (APP_ENV === 'development') {
        throw $e;
    }
}

/**
 * Security recommendations:
 * 1. Use environment variables for production credentials
 * 2. Regularly rotate database passwords
 * 3. Use different users for different environments
 * 4. Consider using SSL for database connections in production
 * 5. Restrict database user permissions (GRANT only necessary privileges)
 */

// Example environment variable usage:
// Development: Set in .env file or system environment
// Production: Set in server environment or use secrets management

/*
// Example .env file structure:
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
DB_DATABASE=correspondence_management
DB_PORT=3306
APP_ENV=production
*/
?>
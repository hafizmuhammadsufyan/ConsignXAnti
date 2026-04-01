<?php
// FILE: /consignxAnti/includes/config.php

// Error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load .env file
function load_env_file($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        if (strpos($line, '=') === false) {
            continue; // Skip lines without =
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load environment variables from .env file
load_env_file();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password
define('DB_NAME', 'consignx_database');

// Application Settings
define('APP_NAME', 'ConsignX - Courier Management System');
define('APP_URL', 'http://localhost/consignxAnti');

// Email Configuration (from .env file or defaults)
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@consignx.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'ConsignX Team');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_DEBUG', getenv('SMTP_DEBUG') ?: 0);
define('MAIL_FROM', 'noreply@consignx.com');
define('MAIL_FROM_NAME', 'ConsignX Team');

// Start secure sessions
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('UTC');
?>
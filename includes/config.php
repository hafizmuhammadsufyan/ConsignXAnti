<?php
// FILE: /consignxAnti/includes/config.php

// Error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password
define('DB_NAME', 'consignx_database');

// Application Settings
define('APP_NAME', 'ConsignX - Courier Management System');
define('APP_URL', 'http://localhost/consignxAnti');

// Email Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'sufyanfortech810@gmail.com');
define('SMTP_PASS', 'jmhvavxfhfcjkseu'); // Gmail App Password
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('MAIL_FROM', 'sufyanfortech810@gmail.com');
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
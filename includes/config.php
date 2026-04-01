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

// Email Configuration (PHPMailer using .env concept via constants)
define('SMTP_HOST', 'smtp.mailtrap.io'); // Replace with real SMTP server in production
define('SMTP_USER', 'your_smtp_user');
define('SMTP_PASS', 'your_smtp_pass');
define('SMTP_PORT', 2525);
define('MAIL_FROM', 'noreply@consignx.com');
define('MAIL_FROM_NAME', 'ConsignX Admin');

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
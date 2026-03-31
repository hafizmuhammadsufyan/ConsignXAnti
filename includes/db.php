<?php
// FILE: /consignxAnti/includes/db.php

require_once 'config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error securely and display generic message
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}

// Temporary migration
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `company_requests` ( 
        `id` int(11) NOT NULL AUTO_INCREMENT, 
        `name` varchar(100) NOT NULL, 
        `company_name` varchar(150) NOT NULL, 
        `email` varchar(100) NOT NULL, 
        `phone` varchar(20) NOT NULL, 
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending', 
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 
        PRIMARY KEY (`id`) 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `blocked_emails` ( 
        `id` int(11) NOT NULL AUTO_INCREMENT, 
        `email` varchar(100) NOT NULL UNIQUE, 
        `reason` varchar(255), 
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 
        PRIMARY KEY (`id`) 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
} catch (PDOException $e) {}

// Function to safely execute queries
function db_query($sql, $params = [])
{
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage() . ' - SQL: ' . $sql);
        throw $e;
    }
}
?>
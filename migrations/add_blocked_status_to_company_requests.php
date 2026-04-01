<?php
/**
 * Migration: Add 'blocked' status to company_requests enum
 * This allows admins to block specific emails/phones from registration
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    // Modify the status enum to include 'blocked'
    $pdo->exec("ALTER TABLE company_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'blocked') DEFAULT 'pending'");
    
    echo "✓ Migration successful: Added 'blocked' status to company_requests table\n";
} catch (PDOException $e) {
    echo "✗ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

<?php
// FILE: /consignxAnti/migrations/add_company_requests_indexes.php

require_once '../includes/config.php';
require_once '../includes/db.php';

/**
 * Migration: Add performance indexes to company_requests table
 * Purpose: Improve query performance for pagination, filtering, and searching
 * 
 * This migration adds indexes on columns used for filtering and searching:
 * - created_at: For date range filtering
 * - status: For status-based filtering
 * - email: For search functionality
 * - phone: For search functionality
 * - name: For search functionality
 * - company_name: For search functionality
 */

try {
    $pdo->beginTransaction();

    echo "Adding indexes to company_requests table...\n\n";

    // Check if indexes already exist
    $indexes = $pdo->query("SHOW INDEXES FROM company_requests")->fetchAll();
    $index_names = array_column($indexes, 'Key_name');

    // Index 1: created_at (for date filtering and sorting)
    if (!in_array('idx_created_at', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_created_at (created_at DESC)");
        echo "✓ Added index on created_at\n";
    } else {
        echo "✗ Index on created_at already exists\n";
    }

    // Index 2: status (for status filtering)
    if (!in_array('idx_status', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_status (status)");
        echo "✓ Added index on status\n";
    } else {
        echo "✗ Index on status already exists\n";
    }

    // Index 3: email (for search functionality)
    if (!in_array('idx_email', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_email (email)");
        echo "✓ Added index on email\n";
    } else {
        echo "✗ Index on email already exists\n";
    }

    // Index 4: phone (for search functionality)
    if (!in_array('idx_phone', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_phone (phone)");
        echo "✓ Added index on phone\n";
    } else {
        echo "✗ Index on phone already exists\n";
    }

    // Index 5: name (for search functionality)
    if (!in_array('idx_name', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_name (name)");
        echo "✓ Added index on name\n";
    } else {
        echo "✗ Index on name already exists\n";
    }

    // Index 6: company_name (for search functionality)
    if (!in_array('idx_company_name', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_company_name (company_name)");
        echo "✓ Added index on company_name\n";
    } else {
        echo "✗ Index on company_name already exists\n";
    }

    // Composite Index 7: (status, created_at) for optimized filtering
    if (!in_array('idx_status_created_at', $index_names)) {
        $pdo->exec("ALTER TABLE company_requests ADD INDEX idx_status_created_at (status, created_at DESC)");
        echo "✓ Added composite index on (status, created_at)\n";
    } else {
        echo "✗ Composite index on (status, created_at) already exists\n";
    }

    $pdo->commit();
    echo "\n✓ Migration completed successfully!\n";
    echo "\nNote: These indexes improve query performance for:\n";
    echo "- Date range filtering\n";
    echo "- Status filtering\n";
    echo "- Search by name, email, or phone\n";
    echo "- Pagination with ORDER BY created_at\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>

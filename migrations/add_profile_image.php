<?php
// FILE: /consignxAnti/migrations/add_profile_image.php

require_once '../includes/config.php';
require_once '../includes/db.php';

try {
    echo "Starting migration...\n";

    // Add profile_image to admins
    $pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
    echo "Updated admins table.\n";

    // Add profile_image to agents
    $pdo->exec("ALTER TABLE agents ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
    echo "Updated agents table.\n";

    // Add profile_image to customers
    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
    echo "Updated customers table.\n";

    // Create uploads directory
    $upload_dir = '../assets/uploads/profiles';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Created uploads/profiles directory.\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>

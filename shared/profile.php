<?php
// FILE: /consignxAnti/shared/profile.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Secure the route
if (!is_logged_in()) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = current_user_id();
$role = current_user_role();
$msg = '';

$table = '';
if ($role === 'admin') $table = 'admins';
elseif ($role === 'agent') $table = 'agents';
elseif ($role === 'customer') $table = 'customers';

// Fetch current details
try {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error loading profile: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $msg = display_alert("Invalid security token.", "danger");
    } else {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $new_password = $_POST['new_password'] ?? '';
        
        try {
            // Check email uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM $table WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $user_id]);
            if ($checkStmt->fetch()) {
                throw new Exception("Email address is already in use by another account.");
            }

            $update_fields = [];
            $params = [];

            if ($role === 'admin') {
                $update_fields[] = "username = ?";
                $params[] = $name; // Admin uses username field for 'name'
            } else {
                $update_fields[] = "name = ?";
                $params[] = $name;
                $update_fields[] = "phone = ?";
                $params[] = $phone;
            }

            $update_fields[] = "email = ?";
            $params[] = $email;

            if (!empty($new_password)) {
                $update_fields[] = "password_hash = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $params[] = $user_id;
            $fields_sql = implode(", ", $update_fields);
            
            $stmt = $pdo->prepare("UPDATE $table SET $fields_sql WHERE id = ?");
            $stmt->execute($params);

            // Update session if needed
            if ($role === 'admin') $_SESSION['user_name'] = $name;
            else $_SESSION['user_name'] = $name;

            $msg = display_alert("Profile updated successfully.", "success");
            
            // Refresh local user data
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            $msg = display_alert($e->getMessage(), "danger");
        }
    }
}

// Set back link based on role
$back_link = ($role === 'admin') ? '../admin/dashboard.php' : (($role === 'agent') ? '../agent/dashboard.php' : '../customer/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConsignX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>
<body class="neumorphic-bg">
    <div class="admin-wrapper">
        <button class="btn btn-primary sidebar-toggle-btn shadow-sm" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>

        <?php 
        $active_page = 'profile.php';
        require_once '../includes/sidebar.php'; 
        ?>

        <main class="main-content">
            <div class="container-fluid py-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold text-primary mb-0">Manage Profile</h2>
                            <a href="<?= $back_link ?>" class="btn neumorphic-btn text-muted"><i class="bi bi-arrow-left me-1"></i> Back</a>
                        </div>

                        <?= $msg ?>

                        <div class="neumorphic-card p-4 p-md-5">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold"><?= ($role === 'admin') ? 'Username' : 'Full Name' ?></label>
                                        <input type="text" name="name" class="form-control neumorphic-input py-2" 
                                            value="<?= escape($user['username'] ?? $user['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Email Address</label>
                                        <input type="email" name="email" class="form-control neumorphic-input py-2" 
                                            value="<?= escape($user['email']) ?>" required>
                                    </div>
                                    
                                    <?php if ($role !== 'admin'): ?>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">Phone Number</label>
                                        <input type="text" name="phone" class="form-control neumorphic-input py-2" 
                                            value="<?= escape($user['phone']) ?>" required>
                                    </div>
                                    <?php endif; ?>

                                    <div class="col-12">
                                        <hr class="my-3 opacity-10">
                                        <h6 class="fw-bold text-muted mb-3">Security</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold">New Password (Leave blank to keep current)</label>
                                        <input type="password" name="new_password" class="form-control neumorphic-input py-2" 
                                            placeholder="Enter new password">
                                    </div>
                                </div>

                                <div class="mt-5 text-end border-top pt-4">
                                    <button type="submit" class="btn neumorphic-btn btn-primary fw-bold px-5 py-3">Update Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>

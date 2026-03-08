<?php
// FILE: /consignxAnti/auth/login.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Redirect if already logged in
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf)) {
        $error = "Invalid security token. Please try again.";
    } elseif (empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $loginResult = attempt_login($email, $password, $role);

        if ($loginResult['success']) {
            redirect_if_logged_in(); // Will naturally redirect based on role
        } else {
            $error = $loginResult['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ConsignX Web App</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="neumorphic-card p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">ConsignX</h2>
                        <p class="text-muted">Enter your details to login</p>
                    </div>

                    <?= $error ? display_alert($error, 'danger') : '' ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <label for="role" class="form-label">Account Type</label>
                            <select name="role" id="role" class="form-select neumorphic-input" required>
                                <option value="customer">Customer</option>
                                <option value="agent">Agent (Company)</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address (or Username)</label>
                            <input type="text" name="email" id="email" class="form-control neumorphic-input" required
                                autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control neumorphic-input"
                                required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn neumorphic-btn btn-primary fw-bold">Login to
                                Account</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">Need an agent account? <a href="register.php"
                                class="text-decoration-none fw-bold">Register Company</a></p>
                        <p class="mt-2"><a href="../index.php" class="text-decoration-none">Back to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
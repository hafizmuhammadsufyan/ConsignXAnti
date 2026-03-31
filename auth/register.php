<?php
// FILE: /consignxAnti/auth/register.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Skip registration if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    // Validate inputs
    if (!validate_csrf_token($csrf)) {
        $error = "Invalid security token. Please try again.";
    } elseif (empty($name) || empty($company_name) || empty($email) || empty($phone)) {
        $error = "All fields are required.";
    } else {
        // Validate name, email, phone
        $name_validation = validate_name($name);
        $email_validation = validate_email($email);
        $phone_validation = validate_phone($phone);
        
        if (!$name_validation['valid']) {
            $error = $name_validation['message'];
        } elseif (!$email_validation['valid']) {
            $error = $email_validation['message'];
        } elseif (!$phone_validation['valid']) {
            $error = $phone_validation['message'];
        } else {
            // Check email validation order:
            // 1. If email is blocked → reject
            // 2. Else if exists in agents → reject
            // 3. Else → allow
            
            global $pdo;
            
            // Check if email is blocked
            if (is_email_blocked($email)) {
                $error = "This email address is blocked from registration.";
            } else {
                // Check if email exists in agents table
                $stmt = $pdo->prepare("SELECT id FROM agents WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = "An agent account with this email already exists.";
                } else {
                    // Check if there's a previous request with this email
                    $stmt = $pdo->prepare("SELECT id, status FROM company_requests WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$email]);
                    $prev_request = $stmt->fetch();
                    
                    // Allow re-registration if previous request was rejected
                    // Block if previous request was approved (agent exists) or pending
                    if ($prev_request) {
                        if ($prev_request['status'] === 'approved') {
                            $error = "An account with this email already exists.";
                        } elseif ($prev_request['status'] === 'pending') {
                            $error = "A registration request with this email is already pending review.";
                        }
                        // If status is 'rejected', allow re-registration
                    }
                    
                    if (empty($error)) {
                        try {
                            // Insert into company_requests
                            $insertStmt = $pdo->prepare("INSERT INTO company_requests (name, company_name, email, phone) VALUES (?, ?, ?, ?)");
                            $insertStmt->execute([$name, $company_name, $email, $phone]);

                            $success = "Registration request submitted successfully! An Admin will review your request shortly.";

                        } catch (PDOException $e) {
                            error_log("Registration Error: " . $e->getMessage());
                            $error = "A system error occurred during registration. Please try again.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Registration - ConsignX</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg bg-light py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="neumorphic-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Partner with ConsignX</h2>
                        <p class="text-muted">Register your courier company and start managing shipments.</p>
                    </div>

                    <?= $error ? display_alert($error, 'danger') : '' ?>
                    <?= $success ? display_alert($success, 'success') : '' ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Contact Person Name</label>
                                <input type="text" name="name" id="name" class="form-control neumorphic-input" required
                                    value="<?= escape($_POST['name'] ?? '') ?>">
                                <span id="name-error" class="field-error"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="company_name"
                                    class="form-control neumorphic-input" required
                                    value="<?= escape($_POST['company_name'] ?? '') ?>">
                                <span id="company_name-error" class="field-error"></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Company Email</label>
                                <input type="email" name="email" id="email" class="form-control neumorphic-input"
                                    required value="<?= escape($_POST['email'] ?? '') ?>">
                                <span id="email-error" class="field-error"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control neumorphic-input"
                                    required value="<?= escape($_POST['phone'] ?? '') ?>">
                                <span id="phone-error" class="field-error"></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="alert alert-info py-2 small mb-0">
                                    <i class="bi bi-info-circle me-1"></i> A password will be generated and emailed to you once your account is approved.
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn neumorphic-btn btn-primary fw-bold p-3">Register
                                Company</button>
                        </div>
                    </form>

                    <script>
                    // Client-side validation for form fields
                    document.getElementById('name').addEventListener('blur', function() {
                        const value = this.value.trim();
                        const errorEl = document.getElementById('name-error');
                        
                        if (!value) {
                            errorEl.textContent = 'Name is required.';
                            this.classList.add('is-invalid');
                        } else if (!/^[a-zA-Z\s]+$/.test(value)) {
                            errorEl.textContent = 'Name must contain only letters and spaces.';
                            this.classList.add('is-invalid');
                        } else if (value.length < 2) {
                            errorEl.textContent = 'Name must be at least 2 characters long.';
                            this.classList.add('is-invalid');
                        } else {
                            errorEl.textContent = '';
                            this.classList.remove('is-invalid');
                        }
                    });

                    document.getElementById('email').addEventListener('blur', function() {
                        const value = this.value.trim();
                        const errorEl = document.getElementById('email-error');
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        
                        if (!value) {
                            errorEl.textContent = 'Email is required.';
                            this.classList.add('is-invalid');
                        } else if (!emailRegex.test(value)) {
                            errorEl.textContent = 'Please provide a valid email address.';
                            this.classList.add('is-invalid');
                        } else {
                            errorEl.textContent = '';
                            this.classList.remove('is-invalid');
                        }
                    });

                    document.getElementById('phone').addEventListener('blur', function() {
                        const value = this.value.trim();
                        const errorEl = document.getElementById('phone-error');
                        
                        if (!value) {
                            errorEl.textContent = 'Phone number is required.';
                            this.classList.add('is-invalid');
                        } else if (!/^[0-9]+$/.test(value)) {
                            errorEl.textContent = 'Phone number must contain only digits.';
                            this.classList.add('is-invalid');
                        } else if (value.length < 10 || value.length > 20) {
                            errorEl.textContent = 'Phone number must be between 10 and 20 digits.';
                            this.classList.add('is-invalid');
                        } else {
                            errorEl.textContent = '';
                            this.classList.remove('is-invalid');
                        }
                    });
                    </script>

                    <div class="text-center mt-4 border-top pt-3">
                        <p class="mb-0">Already registered? <a href="login.php"
                                class="text-decoration-none fw-bold">Login here</a></p>
                        <p class="mt-2 text-muted"><a href="../index.php" class="text-decoration-none text-muted">Back
                                to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
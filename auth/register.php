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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } else {
        // Check if email exists in agents or requests
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM agents WHERE email = :email UNION SELECT id FROM company_requests WHERE email = :email_req");
        $stmt->execute(['email' => $email, 'email_req' => $email]);

        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {


            try {
                // Insert into company_requests instead of agents directly
                $insertStmt = $pdo->prepare("INSERT INTO company_requests (name, company_name, email, phone) VALUES (:name, :company, :email, :phone)");
                $insertStmt->execute([
                    'name' => $name,
                    'company' => $company_name,
                    'email' => $email,
                    'phone' => $phone
                ]);

                $success = "Registration request submitted successfully! An Admin will review your request shortly.";

            } catch (PDOException $e) {
                error_log("Registration Error: " . $e->getMessage());
                $error = "A system error occurred during registration. Please try again.";
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
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="company_name"
                                    class="form-control neumorphic-input" required
                                    value="<?= escape($_POST['company_name'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Company Email</label>
                                <input type="email" name="email" id="email" class="form-control neumorphic-input"
                                    required value="<?= escape($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control neumorphic-input"
                                    required value="<?= escape($_POST['phone'] ?? '') ?>">
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
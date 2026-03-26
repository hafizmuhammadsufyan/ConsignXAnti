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
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ConsignX Web App</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
    <style>
        /* Strict Viewport height stabilization */
        html, body { 
            height: 100vh; 
            margin: 0; 
            padding: 0; 
            overflow: hidden !important; 
            background-color: var(--bg-color); 
        }

        .login-screen-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card-container {
            height: 95vh;
            max-height: 800px; /* Cap for very large screens */
            width: 95vw;
            max-width: 1100px;
            display: flex;
            border-radius: var(--border-radius-card);
            box-shadow: var(--shadow-light-up);
            overflow: hidden;
            background-color: var(--bg-color);
        }

        .login-left { flex: 1.2; background: #000; height: 100%; }
        .login-right { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            padding: 2rem; 
            height: 100%;
            overflow: hidden;
        }

        /* Compact elements to fit 90vh */
        .login-brand-text { font-size: 2.8rem; }
        .role-selector { gap: 10px; margin-bottom: 1.5rem; }
        .role-btn { padding: 8px !important; font-size: 0.8rem; }
        .role-btn i { font-size: 1.1rem !important; }
        .form-label { margin-bottom: 0.25rem !important; }
        .login-input-group { margin-bottom: 1rem !important; }
        
        .login-theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 100;
        }

        @media (max-width: 991.98px) {
            .login-left { display: none; }
            .login-card-container { max-width: 450px; height: auto; max-height: 90vh; }
        }
    </style>
</head>

<body>
    <div class="login-theme-toggle">
        <label class="theme-switch m-0 neumorphic-card p-2" style="border-radius: 50px; display: flex;">
            <input type="checkbox" id="theme-checkbox">
            <span class="slider round"></span>
        </label>
    </div>

    <div class="login-screen-wrapper">
        <div class="login-card-container">
            <!-- Left: Carousel -->
            <div class="login-left">
                <div id="loginCarousel" class="carousel slide h-100 carousel-fade" data-bs-ride="carousel">
                    <div class="carousel-inner h-100">
                        <div class="carousel-item active h-100">
                            <img src="../assets/images/carousel/slide1.png" class="d-block w-100 h-100" style="object-fit: cover;" alt="Logistics">
                            <div class="carousel-caption d-none d-md-block text-start" style="bottom: 8%; left: 8%;">
                                <h2 class="fw-bold">Smart Logistics</h2>
                                <p class="small">Next-gen automation for your supply chain.</p>
                            </div>
                        </div>
                        <div class="carousel-item h-100">
                            <img src="../assets/images/carousel/slide2.png" class="d-block w-100 h-100" style="object-fit: cover;" alt="Network">
                            <div class="carousel-caption d-none d-md-block text-start" style="bottom: 8%; left: 8%;">
                                <h2 class="fw-bold">Global Reach</h2>
                                <p class="small">Connecting you to every corner of the world.</p>
                            </div>
                        </div>
                        <div class="carousel-item h-100">
                            <img src="../assets/images/carousel/slide3.png" class="d-block w-100 h-100" style="object-fit: cover;" alt="Delivery">
                            <div class="carousel-caption d-none d-md-block text-start" style="bottom: 8%; left: 8%;">
                                <h2 class="fw-bold">Fast Delivery</h2>
                                <p class="small">Reliable service that keeps your customers happy.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Login Form -->
            <div class="login-right">
                <div class="login-form-box px-3">
                    <div class="text-center mb-4">
                        <h1 class="fw-bold login-brand-text text-primary mb-1">ConsignX</h1>
                        <p class="text-muted small mb-0">Portal Login</p>
                    </div>

                    <?= $error ? display_alert($error, 'danger', 'py-2 mb-3 small') : '' ?>

                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="role" id="selectedRole" value="customer">

                        <div class="role-selector d-flex mb-3">
                            <div class="role-btn active neumorphic-btn flex-grow-1 text-center" data-role="customer" style="cursor: pointer;">
                                <i class="bi bi-person d-block"></i> User
                            </div>
                            <div class="role-btn neumorphic-btn flex-grow-1 text-center" data-role="agent" style="cursor: pointer;">
                                <i class="bi bi-building d-block"></i> Agent
                            </div>
                            <div class="role-btn neumorphic-btn flex-grow-1 text-center" data-role="admin" style="cursor: pointer;">
                                <i class="bi bi-shield-lock d-block"></i> Admin
                            </div>
                        </div>

                        <div class="login-input-group mb-3">
                            <label class="form-label small fw-bold text-muted">Username / Email</label>
                            <input type="text" name="email" class="form-control neumorphic-input py-2" placeholder="e.g. jdoe" required autofocus>
                        </div>

                        <div class="login-input-group mb-3">
                            <label class="form-label small fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control neumorphic-input py-2" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn neumorphic-btn btn-primary w-100 py-2 fw-bold mt-2">
                            SIGN IN <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <div class="mt-4 pt-3 text-center border-top border-secondary border-opacity-10">
                        <p class="small text-muted mb-1">New courier company?</p>
                        <a href="register.php" class="text-primary text-decoration-none fw-bold small">Register Here</a>
                        <div class="mt-3">
                            <a href="../index.php" class="text-muted text-decoration-none extra-small">
                                <i class="bi bi-house me-1"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle Logic
        const toggle = document.getElementById('theme-checkbox');
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
        toggle.checked = (theme === 'dark');

        toggle.addEventListener('change', function() {
            const next = this.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });

        // Role Logic
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active', 'btn-primary', 'text-white'));
                this.classList.add('active');
                if (document.documentElement.getAttribute('data-theme') === 'light' || true) {
                    // We'll use a class handle in CSS for active role to be cleaner but for now:
                    this.style.backgroundColor = 'var(--primary-color)';
                    this.style.color = '#fff';
                    // Reset others
                    document.querySelectorAll('.role-btn:not(.active)').forEach(b => {
                        b.style.backgroundColor = 'var(--bg-color)';
                        b.style.color = 'var(--text-primary)';
                    });
                }
                document.getElementById('selectedRole').value = this.dataset.role;
            });
        });
        // Init first active
        document.querySelector('.role-btn.active').click();
    </script>
</body>
</html>
<?php
// FILE: /consignxAnti/includes/middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Verifies role and redirects if unauthorized.
 * 
 * @param string|array $allowed_roles Role(s) permitted to access the page
 * @param string $redirect_path Path to redirect to if unauthorized
 */
function require_role($allowed_roles, $redirect_path = '../auth/login.php')
{
    if (!is_logged_in()) {
        header("Location: $redirect_path");
        exit;
    }

    $roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];

    if (!in_array($_SESSION['user_role'], $roles)) {
        header("Location: $redirect_path");
        exit;
    }
}

/**
 * Specifically for the login page to redirect users who are already logged in
 */
function redirect_if_logged_in()
{
    if (is_logged_in()) {
        $role = $_SESSION['user_role'];
        if ($role === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($role === 'agent') {
            header("Location: ../agent/dashboard.php");
        } elseif ($role === 'customer') {
            header("Location: ../customer/dashboard.php");
        }
        exit;
    }
}

/**
 * Enforces HTTPS - useful for production environments
 */
function require_https()
{
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        if (!empty($_SERVER['HTTP_HOST'])) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

/**
 * Helper to get the logged-in user ID
 */
function current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Helper to get the logged-in user Role
 */
function current_user_role()
{
    return $_SESSION['user_role'] ?? null;
}
?>
<?php
// FILE: /consignxAnti/includes/auth.php

// Ensure session is started and includes db.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates CSRF token from POST
 * @param string $token Token from form
 * @return bool
 */
function validate_csrf_token($token)
{
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Attempts to login a user and sets session variables securely
 * Supported Roles: 'admin', 'agent', 'customer'
 *
 * @param string $email Login email (or username for admin fallback but strictly emails per db)
 * @param string $password Clear text password
 * @param string $role User role attempted
 * @return array ['success' => bool, 'message' => string]
 */
function attempt_login($email, $password, $role)
{
    global $pdo;

    $table = '';
    $identifier = 'email';

    // Map role to respective table
    if ($role === 'admin') {
        $table = 'admins';
        $identifier = 'username'; // Note admins can login via username according to DB setup, or email
    } elseif ($role === 'agent') {
        $table = 'agents';
    } elseif ($role === 'customer') {
        $table = 'customers';
    } else {
        return ['success' => false, 'message' => 'Invalid role specified.'];
    }

    try {
        // Find user by email or username
        if ($role === 'admin') {
            $query = "SELECT * FROM admins WHERE username = :identifier OR email = :identifier_email LIMIT 1";
            $params = ['identifier' => $email, 'identifier_email' => $email];
        } else {
            $query = "SELECT * FROM {$table} WHERE email = :identifier LIMIT 1";
            $params = ['identifier' => $email];
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $user = $stmt->fetch();

        // Check user existence and verify hashed password
        if ($user && password_verify($password, $user['password_hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            // Populate session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $role;
            $_SESSION['user_name'] = $user['name'] ?? $user['username'];

            // Additional role specific properties
            if ($role === 'agent') {
                $_SESSION['company_name'] = $user['company_name'];
                if ($user['status'] !== 'active') {
                    logout();
                    return ['success' => false, 'message' => 'Agent account is inactive. Please contact admin.'];
                }
            }

            return ['success' => true, 'message' => 'Login successful.'];
        }

        return ['success' => false, 'message' => 'Invalid credentials provided.'];
    } catch (PDOException $e) {
        error_log("Login Error ($role): " . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred. Please try again later.'];
    }
}

/**
 * Logs out the current user and securely destroys the session
 */
function logout()
{
    $_SESSION = array(); // Clear all session variables
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generates a strong random password hash using BCRYPT
 */
function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}
?>
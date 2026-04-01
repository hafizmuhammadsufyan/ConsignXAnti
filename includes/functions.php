<?php
// FILE: /consignxAnti/includes/functions.php

/**
 * Escapes HTML output securely
 */

// function redirect_if_logged_in()
// {
//     if (!empty($_SESSION['user_role'])) {

//         if ($_SESSION['user_role'] === 'admin') {
//             header("Location: ../admin/dashboard.php");
//             exit;
//         }

//         if ($_SESSION['user_role'] === 'agent') {
//             header("Location: ../agent/dashboard.php");
//             exit;
//         }

//         if ($_SESSION['user_role'] === 'customer') {
//             header("Location: ../customer/dashboard.php");
//             exit;
//         }
//     }
// }

function escape($string)
{
    if (is_null($string))
        return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validates tracking number format (C-XXXX-XXXX)
 */
function is_valid_tracking_number($tracking_number)
{
    return preg_match('/^C-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $tracking_number);
}

/**
 * Generates a unique tracking number
 */
function generate_tracking_number()
{
    global $pdo;

    do {
        $p1 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        $p2 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        $tracking = "C-{$p1}-{$p2}";

        $stmt = $pdo->prepare("SELECT id FROM shipments WHERE tracking_number = :tracking");
        $stmt->execute(['tracking' => $tracking]);
    } while ($stmt->fetch());

    return $tracking;
}

/**
 * Gets all cities for dropdowns
 */
function get_cities()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM cities ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Calculates distance between two points using Haversine formula
 */
function get_distance($lat1, $lon1, $lat2, $lon2)
{
    $earth_radius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

/**
 * Automatically calculates shipment price based on weight and cities
 */
function calculate_shipment_price($origin_id, $dest_id, $weight)
{
    global $pdo;
    
    // Sample coordinates for Pakistani cities (stored in code or could be in DB)
    $coords = [
        'Karachi' => [24.86, 67.00],
        'Lahore' => [31.52, 74.35],
        'Islamabad' => [33.68, 73.04],
        'Rawalpindi' => [33.56, 73.01],
        'Faisalabad' => [31.45, 73.13],
        'Multan' => [30.15, 71.52],
        'Peshawar' => [34.01, 71.52],
        'Quetta' => [30.17, 66.97],
        'Hyderabad' => [25.39, 68.37],
        'Sialkot' => [32.49, 74.52],
        'Gujranwala' => [32.18, 74.19],
        'Bahawalpur' => [29.35, 71.69]
    ];

    $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE id IN (?, ?)");
$stmt->execute([$origin_id, $dest_id]);
$city_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $origin_name = '';
    $dest_name = '';
    
    // Manual mapping check (simple way since IDs might vary)
    // Better: Add coordinates to the 'cities' table.
    
    // Fetching from DB again to be sure of order
    $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE id = ?");
    $stmt->execute([$origin_id]);
    $origin_name = $stmt->fetchColumn();
    $stmt->execute([$dest_id]);
    $dest_name = $stmt->fetchColumn();

    $distance = 100; // Default if not found
    if (isset($coords[$origin_name]) && isset($coords[$dest_name])) {
        $distance = get_distance($coords[$origin_name][0], $coords[$origin_name][1], $coords[$dest_name][0], $coords[$dest_name][1]);
    }

    $base_price = 150.00;
    $rate_per_kg = 80.00;
    $rate_per_km = 0.50;

    $total = $base_price + ($weight * $rate_per_kg) + ($distance * $rate_per_km);
    return round($total, 2);
}

/**
 * Helper to display alert messages securely
 */
function display_alert($message, $type = 'success')
{
    if (empty($message))
        return '';
    $type_class = escape($type);
    $msg = escape($message);

    return "<div class='alert alert-{$type_class} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}

/**
 * Formats a given amount into PKR currency string
 */
function format_currency($amount)
{
    return 'Rs. ' . number_format((float) $amount, 2);
}

/**
 * Formats timestamps nicely
 */
function format_date($timestamp)
{
    return date('M d, Y h:i A', strtotime($timestamp));
}

/**
 * Gets the profile image path for a user
 */
function get_user_profile_image($image_name)
{
    if (!empty($image_name)) {
        $path = 'assets/uploads/profiles/' . $image_name;
        // Adjust path if called from deep directory
        if (!file_exists($path)) {
            $path = '../' . $path;
        }
        if (file_exists($path)) return $path;
    }
    return '../assets/images/default-avatar.png'; // Placeholder if no image set
}

/**
 * ============================================
 * GLOBAL FORM VALIDATION FUNCTIONS
 * ============================================
 */

/**
 * Validates name (alphabets and spaces only)
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_name($name)
{
    $name = trim($name);
    
    if (empty($name)) {
        return ['valid' => false, 'message' => 'Name is required.'];
    }
    
    // Allow only alphabets and spaces
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        return ['valid' => false, 'message' => 'Name must contain only letters and spaces.'];
    }
    
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Name must be at least 2 characters long.'];
    }
    
    if (strlen($name) > 100) {
        return ['valid' => false, 'message' => 'Name must not exceed 100 characters.'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validates email format
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_email($email)
{
    $email = trim($email);
    
    if (empty($email)) {
        return ['valid' => false, 'message' => 'Email is required.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Please provide a valid email address.'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validates phone number (digits only, 10-20 characters)
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_phone($phone)
{
    $phone = trim($phone);
    
    if (empty($phone)) {
        return ['valid' => false, 'message' => 'Phone number is required.'];
    }
    
    // Allow only digits
    if (!preg_match('/^[0-9]+$/', $phone)) {
        return ['valid' => false, 'message' => 'Phone number must contain only digits.'];
    }
    
    if (strlen($phone) < 10 || strlen($phone) > 20) {
        return ['valid' => false, 'message' => 'Phone number must be between 10 and 20 digits.'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Displays inline validation error
 * @param string $field_id HTML element ID
 * @param string $message Error message
 * @return string HTML error span
 */
function display_field_error($field_id, $message = '')
{
    if (empty($message)) {
        return '';
    }
    return "<span id='{$field_id}-error' class='d-block text-danger small mt-1' style='font-size: 12px;'>" . escape($message) . "</span>";
}

/**
 * Checks if email is blocked from registration
 * @return bool true if blocked, false otherwise
 */
function is_email_blocked($email)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM company_requests WHERE email = ? AND status = 'blocked'");
        $stmt->execute([trim($email)]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email block check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds email to block list
 * @param string $email Email to block
 * @return bool Success status
 */
function block_email($email)
{
    global $pdo;
    
    try {
        // Mark as blocked in company_requests by status
        $stmt = $pdo->prepare("INSERT INTO company_requests (name, company_name, email, phone, status) VALUES ('BLOCKED', 'BLOCKED', ?, 'BLOCKED', 'blocked') ON DUPLICATE KEY UPDATE status = 'blocked'");
        $stmt->execute([trim($email)]);
        return true;
    } catch (PDOException $e) {
        error_log("Email block error: " . $e->getMessage());
        return false;
    }
}

/**
 * Unblocks an email
 * @param string $email Email to unblock
 * @return bool Success status
 */
function unblock_email($email)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM company_requests WHERE email = ? AND status = 'blocked'");
        $stmt->execute([trim($email)]);
        return true;
    } catch (PDOException $e) {
        error_log("Email unblock error: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks if phone is blocked from registration (same as email blocking)
 * @return bool true if blocked, false otherwise
 */
function is_phone_blocked($phone)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM company_requests WHERE phone = ? AND status = 'blocked'");
        $stmt->execute([trim($phone)]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Phone block check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds phone to block list
 * @param string $phone Phone to block
 * @return bool Success status
 */
function block_phone($phone)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO company_requests (name, company_name, email, phone, status) VALUES ('BLOCKED', 'BLOCKED', 'blocked@', ?, 'blocked') ON DUPLICATE KEY UPDATE status = 'blocked'");
        $stmt->execute([trim($phone)]);
        return true;
    } catch (PDOException $e) {
        error_log("Phone block error: " . $e->getMessage());
        return false;
    }
}

/**
 * Unblocks a phone number
 * @param string $phone Phone to unblock
 * @return bool Success status
 */
function unblock_phone($phone)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM company_requests WHERE phone = ? AND status = 'blocked'");
        $stmt->execute([trim($phone)]);
        return true;
    } catch (PDOException $e) {
        error_log("Phone unblock error: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks if agent has active shipments
 * @param int $agent_id Agent ID
 * @return array ['has_active' => bool, 'count' => int]
 */
function agent_has_active_shipments($agent_id)
{
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM shipments 
            WHERE agent_id = ? 
            AND status NOT IN ('Delivered', 'Cancelled', 'Returned')
        ");
        $stmt->execute([$agent_id]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
        
        return ['has_active' => $count > 0, 'count' => $count];
    } catch (PDOException $e) {
        error_log("Active shipment check error: " . $e->getMessage());
        return ['has_active' => true, 'count' => 0]; // Default to blocking deletion on error
    }
}
?>
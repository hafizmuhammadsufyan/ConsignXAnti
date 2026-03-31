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
 * Validates name field - only alphabets and spaces
 */
function validate_name($name)
{
    $name = trim($name);
    return !empty($name) && preg_match('/^[A-Za-z\s]+$/', $name);
}

/**
 * Validates phone field - only digits, length 10-15
 */
function validate_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15 && preg_match('/^[0-9]+$/', $phone);
}

/**
 * Validates email format using PHP's built-in filter
 */
function validate_email($email)
{
    return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Checks if email is blocked
 */
function is_email_blocked($email)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM blocked_emails WHERE email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gets the reason why an email is blocked
 */
function get_email_block_reason($email)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT reason FROM blocked_emails WHERE email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $result = $stmt->fetch();
        return $result ? $result['reason'] : '';
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return '';
    }
}

/**
 * Checks if an agent has active (non-completed) shipments
 * Returns count of active shipments or 0 if none
 */
function get_agent_active_shipments_count($agent_id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM shipments 
            WHERE agent_id = ? 
            AND status NOT IN ('Delivered', 'Cancelled', 'Returned')
        ");
        $stmt->execute([$agent_id]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Gets list of active (non-completed) shipments for an agent
 */
function get_agent_active_shipments($agent_id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT tracking_number, status, created_at FROM shipments 
            WHERE agent_id = ? 
            AND status NOT IN ('Delivered', 'Cancelled', 'Returned')
            ORDER BY created_at DESC
        ");
        $stmt->execute([$agent_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Blocks an email with optional reason
 */
function block_email($email, $reason = '')
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO blocked_emails (email, reason) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE reason = ?
        ");
        $stmt->execute([strtolower(trim($email)), $reason, $reason]);
        return true;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Unblocks an email
 */
function unblock_email($email)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM blocked_emails WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return true;
    } catch (PDOException $e) {
        error_log('Query Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Displays premium error message with neumorphic style
 */
function display_premium_error($message, $field_id = '')
{
    $msg = escape($message);
    $field_highlight = !empty($field_id) ? "data-field='{$field_id}'" : '';
    
    return "<div class='premium-error-alert' role='alert' {$field_highlight}>
                <div class='premium-error-header'>
                    <svg class='premium-error-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                        <circle cx='12' cy='12' r='10'></circle>
                        <line x1='12' y1='8' x2='12' y2='12'></line>
                        <line x1='12' y1='16' x2='12.01' y2='16'></line>
                    </svg>
                    <span class='premium-error-title'>Error</span>
                </div>
                <p class='premium-error-message'>{$msg}</p>
            </div>";
}

/**
 * Displays premium success message with neumorphic style
 */
function display_premium_success($message)
{
    $msg = escape($message);
    
    return "<div class='premium-success-alert' role='alert'>
                <div class='premium-success-header'>
                    <svg class='premium-success-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                        <path d='M22 11.08V12a10 10 0 1 1-5.93-9.14'></path>
                        <polyline points='22 4 12 14.01 9 11.01'></polyline>
                    </svg>
                    <span class='premium-success-title'>Success</span>
                </div>
                <p class='premium-success-message'>{$msg}</p>
            </div>";
}

/**
 * Displays premium warning message with neumorphic style
 */
function display_premium_warning($message)
{
    $msg = escape($message);
    
    return "<div class='premium-warning-alert' role='alert'>
                <div class='premium-warning-header'>
                    <svg class='premium-warning-icon' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                        <path d='M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z'></path>
                        <line x1='12' y1='9' x2='12' y2='13'></line>
                        <line x1='12' y1='17' x2='12.01' y2='17'></line>
                    </svg>
                    <span class='premium-warning-title'>Warning</span>
                </div>
                <p class='premium-warning-message'>{$msg}</p>
            </div>";
}
?>
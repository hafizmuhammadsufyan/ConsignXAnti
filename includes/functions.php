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
?>
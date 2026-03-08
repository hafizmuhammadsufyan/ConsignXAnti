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
 * Formats a given amount into USD currency string
 */
function format_currency($amount)
{
    return '$' . number_format((float) $amount, 2);
}

/**
 * Formats timestamps nicely
 */
function format_date($timestamp)
{
    return date('M d, Y h:i A', strtotime($timestamp));
}
?>
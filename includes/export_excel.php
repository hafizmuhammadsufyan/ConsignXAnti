<?php
// FILE: /consignxAnti/includes/export_excel.php

require_once 'config.php';
require_once 'db.php';
require_once 'middleware.php';
require_once 'functions.php';

// Secure the route
if (!is_logged_in() || !in_array(current_user_role(), ['admin', 'agent'])) {
    die("Unauthorized access.");
}

$role = current_user_role();
$user_id = current_user_id();

// Capture filters from GET
$where_clauses = ["1=1"];
$params = [];

// Apply role-based restrictions
if ($role === 'agent') {
    $where_clauses[] = "s.agent_id = ?";
    $params[] = $user_id;
} else if ($role === 'admin' && !empty($_GET['agent_id'])) {
    $where_clauses[] = "s.agent_id = ?";
    $params[] = $_GET['agent_id'];
}

// Add filters
if (!empty($_GET['date_from'])) {
    $where_clauses[] = "s.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where_clauses[] = "s.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}
if (!empty($_GET['city_id'])) {
    $where_clauses[] = "(s.origin_city_id = ? OR s.destination_city_id = ?)";
    $params[] = $_GET['city_id'];
    $params[] = $_GET['city_id'];
}
if (!empty($_GET['status'])) {
    $where_clauses[] = "s.status = ?";
    $params[] = $_GET['status'];
}

$where_sql = implode(" AND ", $where_clauses);

try {
    $stmt = $pdo->prepare("
        SELECT s.tracking_number, s.created_at, 
               c.name as customer_name, c.email as customer_email,
               a.company_name as agent_name,
               orig.name as origin_city, dest.name as dest_city,
               s.weight, s.price, s.status
        FROM shipments s
        LEFT JOIN agents a ON s.agent_id = a.id
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        WHERE $where_sql
        ORDER BY s.created_at DESC
    ");
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
}

if (empty($results)) {
    die("No data found for the selected filters.");
}

// Generate CSV
$filename = "ConsignX_Shipments_" . date('Y-m-d_His') . ".csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

// Header row
fputcsv($output, [
    'Tracking ID',
    'Date Requested',
    'Customer Name',
    'Customer Email',
    'Handling Agent',
    'Origin',
    'Destination',
    'Weight (kg)',
    'Amount (PKR)',
    'Status'
]);

// Data rows
foreach ($results as $row) {
    fputcsv($output, [
        $row['tracking_number'],
        date('Y-m-d H:i', strtotime($row['created_at'])),
        $row['customer_name'],
        $row['customer_email'],
        $row['agent_name'] ?? 'Direct Admin',
        $row['origin_city'],
        $row['dest_city'],
        number_format($row['weight'], 2),
        number_format($row['price'], 2),
        $row['status']
    ]);
}

fclose($output);
exit;

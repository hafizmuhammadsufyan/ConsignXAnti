<?php
// FILE: /consignxAnti/admin/api/filter_shipments.php

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/middleware.php';

// Secure the route
require_role(['admin', 'agent', 'customer']);

$u_id = current_user_id();
$u_role = current_user_role();

try {
    $where = ["1=1"];
    $params = [];

    // Role-based restrictions
    if ($u_role === 'agent') {
        $where[] = "s.agent_id = ?";
        $params[] = $u_id;
    } elseif ($u_role === 'customer') {
        $where[] = "s.customer_id = ?";
        $params[] = $u_id;
    }

    // Additional filters
    if (!empty($_GET['status'])) {
        $where[] = "s.status = ?";
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['agent_id']) && $u_role === 'admin') {
        $where[] = "s.agent_id = ?";
        $params[] = (int)$_GET['agent_id'];
    }
    if (!empty($_GET['route_id'])) {
        $where[] = "(s.origin_city_id = ? OR s.destination_city_id = ?)";
        $params[] = (int)$_GET['route_id'];
        $params[] = (int)$_GET['route_id'];
    }
    if (!empty($_GET['min_amount'])) {
        $where[] = "s.price >= ?";
        $params[] = (float)$_GET['min_amount'];
    }

    // Dashboard specific logic for customers
    $is_dashboard = isset($_GET['dashboard']) && $_GET['dashboard'] == '1';
    if ($u_role === 'customer' && $is_dashboard && empty($_GET['status'])) {
        $where[] = "s.status != 'Delivered'";
    }

    $where_sql = implode(" AND ", $where);
    $limit = (int)($_GET['limit'] ?? ($u_role === 'customer' ? 20 : 6));

    $sql = "
        SELECT s.*, 
               orig.name as origin_city, dest.name as dest_city,
               a.company_name as agent_name,
               c.name as customer_name
        FROM shipments s
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        LEFT JOIN agents a ON s.agent_id = a.id
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE $where_sql
        ORDER BY s.created_at DESC
        LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();

    if (empty($shipments)) {
        $cols = ($u_role === 'customer') ? 7 : 6;
        echo '<tr><td colspan="' . $cols . '" class="text-center text-muted py-5">No matching shipments found.</td></tr>';
    } else {
        foreach ($shipments as $ship) {
            $bg = match ($ship['status']) {
                'Pending' => 'status-pending',
                'Delivered' => 'status-delivered',
                'Cancelled' => 'status-cancelled',
                'Returned' => 'status-returned',
                'Picked Up' => 'status-picked-up',
                'Out For Delivery' => 'status-out-delivery',
                default => 'status-transit'
            };
            
            echo '<tr class="shipment-row">';
            echo '<td class="fw-bold text-primary">' . escape($ship['tracking_number']) . '</td>';
            echo '<td class="small fw-bold text-muted">' . escape($ship['agent_name'] ?? 'Direct Admin') . '</td>';
            echo '<td><div class="fw-bold small">' . escape($ship['customer_name']) . '</div></td>';
            echo '<td class="fw-medium">' . escape($ship['origin_city']) . ' &rarr; ' . escape($ship['dest_city']) . '</td>';
            echo '<td class="fw-bold">' . format_currency($ship['price']) . '</td>';
            echo '<td><span class="badge-neumorphic ' . $bg . ' small fw-bold">' . escape($ship['status']) . '</span></td>';
            if ($u_role === 'customer') {
                echo '<td class="text-end"><a href="track_shipment.php?id=' . $ship['id'] . '" class="btn-track"><i class="bi bi-geo-alt-fill me-1"></i> Track</a></td>';
            }
            echo '</tr>';
        }
    }
} catch (PDOException $e) {
    error_log("AJAX Filter Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error processing request.";
}

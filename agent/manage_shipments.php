<?php
// FILE: /consignxAnti/agent/manage_shipments.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Secure the route
require_role('agent');

$agent_id = current_user_id();
$company_name = $_SESSION['company_name'];
$msg = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $shipment_id = (int) $_POST['shipment_id'];
        $new_status = $_POST['new_status'];
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING) ?? '';
        $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING) ?? '';

        try {
            // Verify ownership first
            $stmt = $pdo->prepare("SELECT id, price FROM shipments WHERE id = ? AND agent_id = ?");
            $stmt->execute([$shipment_id, $agent_id]);
            $shipment = $stmt->fetch();

            if ($shipment) {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE shipments SET status = :status WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $shipment_id]);

                $stmt = $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, location, remarks, changed_by_role, changed_by_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shipment_id, $new_status, $location, $remarks, 'agent', $agent_id]);

                // Record Revenue if Delivered
                if ($new_status === 'Delivered') {
                    $revCheck = $pdo->prepare("SELECT id FROM revenue WHERE shipment_id = ?");
                    $revCheck->execute([$shipment_id]);
                    if (!$revCheck->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO revenue (shipment_id, agent_id, amount, transaction_date) VALUES (?, ?, ?, CURDATE())");
                        $stmt->execute([$shipment_id, $agent_id, $shipment['price']]);
                    }
                }

                $pdo->commit();
                $msg = display_alert("Status updated successfully to $new_status.", "success");
            } else {
                $msg = display_alert("Unauthorized action or shipment not found.", "danger");
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = display_alert("Failed to update status: " . escape($e->getMessage()), "danger");
        }
    }
}

// Filtering Logic
$where_clauses = ["s.agent_id = ?"];
$params = [$agent_id];

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
        SELECT s.*, 
               c.name as customer_name, c.email as customer_email,
               orig.name as origin_city, dest.name as dest_city
        FROM shipments s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        WHERE $where_sql
        ORDER BY s.created_at DESC
    ");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();
    
    $cities = get_cities();
} catch (PDOException $e) {
    $msg = display_alert("Error loading data.", "danger");
    $shipments = [];
    $cities = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shipments - ConsignX Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php 
        $role = 'agent';
        $active_page = 'manage_shipments.php';
        require_once '../includes/sidebar.php'; 
        ?>


        <!-- Main Content -->
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-primary mb-0">Manage Your Shipments</h2>
                <a href="create_shipment.php" class="btn neumorphic-btn btn-primary fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Shipment
                </a>
            </div>

            <?= $msg ?>

            <!-- Filters Section -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">From Date</label>
                        <input type="date" name="date_from" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">To Date</label>
                        <input type="date" name="date_to" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">City</label>
                        <select name="city_id" class="form-select neumorphic-input py-2">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $ct): ?>
                                <option value="<?= $ct['id'] ?>" <?= (isset($_GET['city_id']) && $_GET['city_id'] == $ct['id']) ? 'selected' : '' ?>>
                                    <?= escape($ct['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Picked Up" <?= ($_GET['status'] ?? '') == 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="In Transit" <?= ($_GET['status'] ?? '') == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                            <option value="Out For Delivery" <?= ($_GET['status'] ?? '') == 'Out For Delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                            <option value="Delivered" <?= ($_GET['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary neumorphic-btn flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                        <a href="manage_shipments.php" class="btn btn-secondary neumorphic-btn"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <a href="../includes/export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success neumorphic-btn fw-bold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                </a>
            </div>

            <div class="neumorphic-card p-4">
                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th class="text-end">Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shipments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No shipments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shipments as $ship): ?>
                                    <tr>
                                        <td class="fw-bold text-primary">
                                            <?= escape($ship['tracking_number']) ?>
                                        </td>
                                        <td><small class="text-muted">
                                                <?= date('M d, Y', strtotime($ship['created_at'])) ?>
                                            </small></td>
                                        <td>
                                            <div class="fw-bold">
                                                <?= escape($ship['customer_name']) ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= escape($ship['customer_email']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="text-muted">
                                                    <?= escape($ship['origin_city']) ?>
                                                </span>
                                                <i class="bi bi-arrow-right mx-2 text-primary"></i>
                                                <span class="fw-medium">
                                                    <?= escape($ship['dest_city']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $bg = match ($ship['status']) {
                                                'Pending' => 'bg-warning text-dark',
                                                'Picked Up', 'In Transit', 'Out For Delivery' => 'bg-info text-dark',
                                                'Delivered' => 'bg-success text-white',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge rounded-pill <?= $bg ?> px-3 py-2">
                                                <?= escape($ship['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm neumorphic-btn" type="button"
                                                    data-bs-toggle="dropdown" <?= $ship['status'] === 'Delivered' ? 'disabled' : '' ?>>
                                                    <i class="bi bi-pencil-square me-1"></i> Update
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-2 rounded-3"
                                                    style="width: 250px;">
                                                    <li>
                                                        <form method="POST" class="px-2 py-1">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?= escape($_SESSION['csrf_token']) ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="shipment_id" value="<?= $ship['id'] ?>">

                                                            <label class="form-label small fw-bold">New Status</label>
                                                            <select name="new_status" class="form-select form-select-sm mb-2"
                                                                required>
                                                                <option value="" disabled>Select Status...</option>
                                                                <option value="Picked Up" <?= $ship['status'] == 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                                                                <option value="In Transit" <?= $ship['status'] == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                                                                <option value="Out For Delivery"
                                                                    <?= $ship['status'] == 'Out For Delivery' ? 'selected' : '' ?>>Out
                                                                    For Delivery</option>
                                                                <option value="Delivered">Delivered</option>
                                                            </select>

                                                            <input type="text" name="location"
                                                                class="form-control form-control-sm mb-2"
                                                                placeholder="Current City/Location">
                                                            <input type="text" name="remarks"
                                                                class="form-control form-control-sm mb-2"
                                                                placeholder="Notes (Optional)">

                                                            <button type="submit" class="btn btn-sm btn-primary w-100">Save
                                                                Update</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>
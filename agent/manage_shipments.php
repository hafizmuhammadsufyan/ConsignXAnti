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

// Fetch Agents Shipments
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.name as customer_name, c.email as customer_email,
               orig.name as origin_city, dest.name as dest_city
        FROM shipments s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        WHERE s.agent_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$agent_id]);
    $shipments = $stmt->fetchAll();
} catch (PDOException $e) {
    $msg = display_alert("Error loading data.", "danger");
    $shipments = [];
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
        <nav class="sidebar d-flex flex-column justify-content-between neumorphic-card m-3 border-0">
            <div>
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">ConsignX</h3>
                    <small class="text-muted">Agent Portal</small>
                </div>

                <div class="text-center mt-3 mb-4">
                    <span class="badge rounded-pill bg-primary px-3 py-2 fw-medium text-uppercase shadow-sm">
                        <i class="bi bi-building me-1"></i>
                        <?= escape($company_name) ?>
                    </span>
                </div>

                <ul class="nav flex-column gap-2 mt-4">
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="create_shipment.php">
                            <i class="bi bi-plus-circle me-2"></i> New Shipment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="manage_shipments.php">
                            <i class="bi bi-box-seam me-2"></i> Manage Shipments
                        </a>
                    </li>
                </ul>
            </div>
            <div class="mt-auto pt-3 border-top border-secondary border-opacity-10">
                <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                    <span class="text-muted small fw-bold">Dark Mode</span>
                    <label class="theme-switch">
                        <input type="checkbox">
                        <span class="slider round"></span>
                    </label>
                </div>
                <a href="../auth/logout.php" class="btn neumorphic-btn btn-danger w-100 fw-bold">Logout</a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-primary mb-0">Manage Your Shipments</h2>
                <a href="create_shipment.php" class="btn neumorphic-btn btn-primary fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Shipment
                </a>
            </div>

            <?= $msg ?>

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
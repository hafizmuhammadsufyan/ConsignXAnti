<?php
// FILE: /consignxAnti/agent/dashboard.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Secure the route
require_role('agent');

$agent_id = current_user_id();
$agent_name = $_SESSION['user_name'];
$company_name = $_SESSION['company_name'] ?? 'Your Company';

// Fetch KPIs specific to this agent
try {
    // Total Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_rev FROM revenue WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $total_revenue = $stmt->fetch()['total_rev'] ?? 0;

    // Total Shipments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shipments WHERE agent_id = ?");
    $stmt->execute([$agent_id]);
    $total_shipments = $stmt->fetch()['count'] ?? 0;

    // Total Delivered Shipments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shipments WHERE agent_id = ? AND status = 'Delivered'");
    $stmt->execute([$agent_id]);
    $delivered_shipments = $stmt->fetch()['count'] ?? 0;

    // Latest Shipments (Limit 5)
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.name as customer_name,
               orig.name as origin_city,
               dest.name as dest_city
        FROM shipments s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        WHERE s.agent_id = ?
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$agent_id]);
    $latest_shipments = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Agent Dashboard Error: " . $e->getMessage());
    $error = "Error loading dashboard metrics.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - ConsignX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
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
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="create_shipment.php">
                            <i class="bi bi-plus-circle me-2"></i> New Shipment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="manage_shipments.php">
                            <i class="bi bi-box-seam me-2"></i> Manage Shipments
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Bottom Controls -->
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

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-primary mb-1">Hello,
                        <?= escape($agent_name) ?>!
                    </h2>
                    <p class="text-muted mb-0">Manage your company's logistics and shipments.</p>
                </div>
                <div class="text-end">
                    <span class="text-muted fw-medium">
                        <?= date('l, F j, Y') ?>
                    </span>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <?= display_alert($error, 'danger') ?>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Generated Revenue</div>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= format_currency($total_revenue) ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Total Shipments Handled</div>
                        <h3 class="fw-bold text-success mb-0">
                            <?= number_format($total_shipments) ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Successfully Delivered</div>
                        <h3 class="fw-bold text-info mb-0">
                            <?= number_format($delivered_shipments) ?>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Latest Shipments Table -->
            <div class="neumorphic-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Recent Shipments Managed by You</h5>
                    <div>
                        <a href="create_shipment.php" class="btn btn-sm neumorphic-btn btn-primary fw-bold me-2"><i
                                class="bi bi-plus-lg me-1"></i> Create</a>
                        <a href="manage_shipments.php" class="btn btn-sm neumorphic-btn text-primary">View All</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latest_shipments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">You haven't processed any shipments
                                        yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_shipments as $ship): ?>
                                    <tr>
                                        <td class="fw-bold text-primary">
                                            <?= escape($ship['tracking_number']) ?>
                                        </td>
                                        <td>
                                            <?= date('M d', strtotime($ship['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?= escape($ship['customer_name']) ?>
                                        </td>
                                        <td>
                                            <?= escape($ship['origin_city']) ?> &rarr;
                                            <?= escape($ship['dest_city']) ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?= format_currency($ship['price']) ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Badges logic based on status
                                            $bg = match ($ship['status']) {
                                                'Pending' => 'bg-warning text-dark',
                                                'Picked Up', 'In Transit', 'Out For Delivery' => 'bg-info text-white',
                                                'Delivered' => 'bg-success text-white',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge rounded-pill <?= $bg ?> px-3 py-2">
                                                <?= escape($ship['status']) ?>
                                            </span>
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
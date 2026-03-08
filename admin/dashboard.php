<?php
// FILE: /consignxAnti/admin/dashboard.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Secure the route
require_role('admin');

$admin_id = current_user_id();
$admin_name = $_SESSION['user_name'];

// Fetch KPIs
try {
    // Total Revenue (From delivered shipments or generally recorded)
    $stmt = $pdo->query("SELECT SUM(amount) as total_rev FROM revenue");
    $total_revenue = $stmt->fetch()['total_rev'] ?? 0;

    // Total Shipments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM shipments");
    $total_shipments = $stmt->fetch()['count'] ?? 0;

    // Total Pending Shipments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM shipments WHERE status = 'Pending'");
    $pending_shipments = $stmt->fetch()['count'] ?? 0;

    // Total Active Agents
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM agents WHERE status = 'active'");
    $active_agents = $stmt->fetch()['count'] ?? 0;

    // Latest Shipments (Limit 5)
    $stmt = $pdo->query("
        SELECT s.*, 
               a.company_name as agent_name, 
               c.name as customer_name,
               orig.name as origin_city,
               dest.name as dest_city
        FROM shipments s
        LEFT JOIN agents a ON s.agent_id = a.id
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        ORDER BY s.created_at DESC 
        LIMIT 5
    ");
    $latest_shipments = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $error = "Error loading dashboard metrics.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ConsignX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Main Sidebar Navigation -->
        <nav class="sidebar d-flex flex-column justify-content-between neumorphic-card m-3 border-0">
            <div>
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">ConsignX</h3>
                    <small class="text-muted">Admin Portal</small>
                </div>

                <ul class="nav flex-column gap-2 mt-4">
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="manage_shipments.php">
                            <i class="bi bi-box-seam me-2"></i> Shipments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="manage_agents.php">
                            <i class="bi bi-building me-2"></i> Agents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="reports.php">
                            <i class="bi bi-graph-up me-2"></i> Reports
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
                <a href="../auth/logout.php" class="btn neumorphic-btn btn-danger w-100 fw-bold">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-primary mb-1">Welcome back,
                        <?= escape($admin_name) ?>!
                    </h2>
                    <p class="text-muted mb-0">Here's what's happening today.</p>
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
                <div class="col-md-3">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Total Revenue</div>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= format_currency($total_revenue) ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Total Shipments</div>
                        <h3 class="fw-bold text-success mb-0">
                            <?= number_format($total_shipments) ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Pending Dispatch</div>
                        <h3 class="fw-bold text-warning mb-0">
                            <?= number_format($pending_shipments) ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="neumorphic-card p-4 text-center">
                        <div class="text-muted mb-2 fw-bold text-uppercase small">Active Agents</div>
                        <h3 class="fw-bold text-info mb-0">
                            <?= number_format($active_agents) ?>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Latest Shipments Table -->
            <div class="neumorphic-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Recent Shipments</h5>
                    <a href="manage_shipments.php" class="btn btn-sm neumorphic-btn text-primary">View All</a>
                </div>

                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Agent</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($latest_shipments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No shipments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_shipments as $ship): ?>
                                    <tr>
                                        <td class="fw-medium text-primary">
                                            <?= escape($ship['tracking_number']) ?>
                                        </td>
                                        <td>
                                            <?= escape($ship['agent_name'] ?? 'Direct Admin') ?>
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
                                            <span class="badge rounded-pill <?= $bg ?>">
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
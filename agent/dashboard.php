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

    // Month wise shipment metrics for Agent charts
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month_label,
            COUNT(id) as total_count,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'In Transit' THEN 1 ELSE 0 END) as transit_count,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_count
        FROM shipments
        WHERE agent_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_label
        ORDER BY month_label ASC
    ");
    $stmt->execute([$agent_id]);
    $monthly_shipments = $stmt->fetchAll();

    // Month wise revenue metrics for Agent charts
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month_label,
            SUM(amount) as total_rev
        FROM revenue
        WHERE agent_id = ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_label
        ORDER BY month_label ASC
    ");
    $stmt->execute([$agent_id]);
    $monthly_revenue = $stmt->fetchAll();

    // Prepare chart data
    $chart_labels = [];
    $chart_total = [];
    $chart_pending = [];
    $chart_transit = [];
    $chart_delivered = [];
    foreach ($monthly_shipments as $row) {
        $chart_labels[] = date('M Y', strtotime($row['month_label'] . '-01'));
        $chart_total[] = $row['total_count'];
        $chart_pending[] = $row['pending_count'];
        $chart_transit[] = $row['transit_count'];
        $chart_delivered[] = $row['delivered_count'];
    }

    $rev_labels = [];
    $rev_data = [];
    foreach ($monthly_revenue as $row) {
        $rev_labels[] = date('M Y', strtotime($row['month_label'] . '-01'));
        $rev_data[] = $row['total_rev'];
    }

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
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <?php 
        $role = 'agent';
        $active_page = 'dashboard.php';
        require_once '../includes/sidebar.php'; 
        ?>


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

            <!-- Charts Section -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="neumorphic-card p-4">
                        <h5 class="fw-bold mb-4">Shipments Performance</h5>
                        <div id="agentShipmentsChart" style="min-height: 250px;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="neumorphic-card p-4">
                        <h5 class="fw-bold mb-4">Revenue Earnings</h5>
                        <div id="agentRevenueChart" style="min-height: 250px;"></div>
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const commonOptions = {
                chart: {
                    height: 280,
                    type: 'line',
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: <?= json_encode($chart_labels) ?>,
                    axisBorder: { show: false },
                },
                colors: ['#0d6efd', '#198754'],
                noData: { text: 'No shipment data found' }
            };

            // Shipments Chart
            new ApexCharts(document.querySelector("#agentShipmentsChart"), {
                ...commonOptions,
                series: [{
                    name: 'Total Shipments',
                    data: <?= json_encode($chart_total) ?>
                }],
                colors: ['#0d6efd']
            }).render();

            // Revenue Chart
            new ApexCharts(document.querySelector("#agentRevenueChart"), {
                ...commonOptions,
                xaxis: {
                    categories: <?= json_encode($rev_labels) ?>,
                    axisBorder: { show: false },
                },
                series: [{
                    name: 'Revenue (PKR)',
                    data: <?= json_encode($rev_data) ?>
                }],
                colors: ['#198754'],
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return "Rs." + value.toLocaleString();
                        }
                    }
                }
            }).render();
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>

</html>
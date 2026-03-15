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

    // Month wise shipment metrics for charts
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month_label,
            COUNT(id) as total_count,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'In Transit' THEN 1 ELSE 0 END) as transit_count,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_count
        FROM shipments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_label
        ORDER BY month_label ASC
    ");
    $monthly_shipments = $stmt->fetchAll();

    // Month wise revenue metrics
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month_label,
            SUM(amount) as total_rev
        FROM revenue
        WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_label
        ORDER BY month_label ASC
    ");
    $monthly_revenue = $stmt->fetchAll();

    // Chart Data Preparation
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
    <!-- ApexCharts JS -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn btn-primary sidebar-toggle-btn shadow-sm" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Main Sidebar Navigation -->
        <?php 
        $role = 'admin';
        $active_page = 'dashboard.php';
        require_once '../includes/sidebar.php'; 
        ?>


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

            <!-- Charts Section -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="neumorphic-card p-4">
                        <h5 class="fw-bold mb-4">Shipments Overview (Monthly)</h5>
                        <div id="shipmentsChart" style="min-height: 250px; width:100%;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="neumorphic-card p-4">
                        <h5 class="fw-bold mb-4">Revenue Overview (Monthly)</h5>
                        <div id="revenueChart" style="min-height: 250px; width:100%;"></div>
                    </div>
                </div>
                <div class="col-12 mt-4">
                    <div class="neumorphic-card p-4">
                        <h5 class="fw-bold mb-4">Shipments Comparison (Total, Delivered, Pending, In Transit)</h5>
                        <div id="compChart" style="min-height: 250px; width:100%;"></div>
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const chartOptions = {
                chart: {
                    height: 300,
                    type: 'area',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'inherit'
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                grid: {
                    borderColor: 'rgba(0,0,0,0.05)',
                    xaxis: { lines: { show: true } }
                },
                markers: { size: 4 },
                xaxis: {
                    categories: <?= json_encode($chart_labels) ?>,
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                colors: ['#0d6efd', '#198754', '#ffc107', '#0dcaf0'],
                noData: {
                    text: 'No data available',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { fontSize: '14px' }
                }
            };

            // 1. Total Shipments Chart
            new ApexCharts(document.querySelector("#shipmentsChart"), {
                ...chartOptions,
                series: [{
                    name: 'Total Shipments',
                    data: <?= json_encode($chart_total) ?>
                }],
                colors: ['#0d6efd']
            }).render();

            // 2. Revenue Overview Chart
            new ApexCharts(document.querySelector("#revenueChart"), {
                ...chartOptions,
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

            // 3. Status Comparison Chart
            new ApexCharts(document.querySelector("#compChart"), {
                ...chartOptions,
                series: [
                    { name: 'Total', data: <?= json_encode($chart_total) ?> },
                    { name: 'Delivered', data: <?= json_encode($chart_delivered) ?> },
                    { name: 'Pending', data: <?= json_encode($chart_pending) ?> },
                    { name: 'In Transit', data: <?= json_encode($chart_transit) ?> }
                ]
            }).render();
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>

</html>
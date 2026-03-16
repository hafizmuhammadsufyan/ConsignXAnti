<?php
// FILE: /consignxAnti/customer/dashboard.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Secure the route
require_role('customer');

$customer_id = current_user_id();
$customer_name = $_SESSION['user_name'];
$msg = '';

// Fetch all shipments belonging to this customer
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               orig.name as origin_city, dest.name as dest_city
        FROM shipments s
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        WHERE s.customer_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $shipments = $stmt->fetchAll();


    // // Calculate Active vs Delivered
    $active1 = 0;
    $delivered1 = 0;
    foreach ($shipments as $s) {
        if ($s['status'] === 'Delivered') {
            $delivered1++;
        } else {
            $active1++;
        }
    }
} catch (PDOException $e) {
    $msg = display_alert("Failed to load your shipments.", "danger");
    $shipments = [];
    $active1 = 0;
    $delivered1 = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shipments - ConsignX Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
</head>

<body class="glass-bg">

    <div class="admin-wrapper">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn btn-primary sidebar-toggle-btn shadow-sm" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Sidebar Navigation -->
        <?php
        $role = 'customer';
        $active_page = 'dashboard.php';
        require_once '../includes/sidebar.php';
        ?>

        <!-- Main Content Area -->
        <main class="main-content">


            <div class="container pb-5">
                <div class="row mb-5 align-items-center">
                    <div class="col-md-6">
                        <h2 class="fw-bold text-primary mb-1">Welcome back,
                            <?= escape($customer_name) ?>!
                        </h2>
                        <p class="text-muted">Track and manage your incoming and outgoing shipments.</p>
                    </div>
                    <div class="col-md-6">
                        <!-- Quick Track Input -->
                        <form action="track.php" method="GET"
                            class="d-flex glass-card p-2 rounded-pill shadow-sm"
                            style="max-width: 450px; margin-left: auto;">
                            <input type="text" name="id" class="form-control border-0 bg-transparent shadow-none px-3"
                                placeholder="Enter Tracking Number (e.g. C-XXXX-XXXX)" required
                                pattern="C-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-none"
                                style="white-space: nowrap;">Track</button>
                        </form>
                    </div>
                </div>

                <?= $msg ?>

                <!-- Summary Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="glass-card p-4 d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary me-4">
                                <i class="bi bi-box-seam fs-3"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-primary mb-0">
                                    <?= number_format($active1) ?>
                                </h3>
                                <span class="text-muted fw-medium text-uppercase small">Active Shipments</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card p-4 d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success me-4">
                                <i class="bi bi-check-circle fs-3"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-success mb-0">
                                    <?= number_format($delivered1) ?>
                                </h3>
                                <span class="text-muted fw-medium text-uppercase small">Delivered Shipments</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipment History Table -->
                <div class="glass-card p-4 p-md-5">
                    <h5 class="fw-bold mb-4"><i class="bi bi-clock-history me-2"></i>Shipment History</h5>

                    <div class="table-responsive">
                        <table class="table glass-table table-borderless align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Created Date</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shipments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5 border-0">You don't have any shipments
                                            linked to your account yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($shipments as $ship): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <?= escape($ship['tracking_number']) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($ship['created_at'])) ?>
                                            </td>
                                            <td>
                                                <?= escape($ship['origin_city']) ?>
                                            </td>
                                            <td><span class="fw-medium">
                                                    <?= escape($ship['dest_city']) ?>
                                                </span></td>
                                            <td>
                                                <?= escape($ship['recipient_name']) ?>
                                            </td>
                                            <td>
                                                <?php
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
                                            <td class="text-end">
                                                <a href="track.php?id=<?= urlencode($ship['tracking_number']) ?>"
                                                    class="btn btn-sm glass-btn btn-primary px-3 fw-bold">Track Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>

</body>

</html>
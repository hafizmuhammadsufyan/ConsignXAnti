<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Only customers can see their own shipments
require_role('customer');

$customer_id = current_user_id();
$customer_name = $_SESSION['user_name'];
$msg = '';

// Get all shipments for this customer
try {
    $where = ["s.customer_id = ?"];
    $params = [$customer_id];

    // Allow filtering by status
    if (!empty($_GET['status'])) {
        $where[] = "s.status = ?";
        $params[] = $_GET['status'];
    }
    // Filter by agent if selected
    if (!empty($_GET['agent_id'])) {
        $where[] = "s.agent_id = ?";
        $params[] = (int)$_GET['agent_id'];
    }
    // Filter by price range
    if (!empty($_GET['min_amount'])) {
        $where[] = "s.price >= ?";
        $params[] = (float)$_GET['min_amount'];
    }

    $where_sql = implode(" AND ", $where);

    $stmt = $pdo->prepare("
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
    ");
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();

    // Fetch agents for filter
    $agents_list = $pdo->query("SELECT id, company_name FROM agents ORDER BY company_name")->fetchAll();

} catch (PDOException $e) {
    $msg = display_alert("Failed to load your shipments archive.", "danger");
    $shipments = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment History - ConsignX Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <?php
        $role = 'customer';
        $active_page = 'dashboard.php';
        require_once '../includes/sidebar.php';
        ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php 
            $page_title = 'Shipment History';
            require_once '../includes/top_header.php'; 
            ?>

            <div class="container-fluid px-0">
                <?= $msg ?>

                <div class="neumorphic-card p-4 p-md-5 mb-5">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <div class="d-flex align-items-center mb-1">
                                <a href="dashboard.php" class="btn btn-sm neumorphic-btn me-3 px-2">
                                    <i class="bi bi-arrow-left"></i>
                                </a>
                                <h4 class="fw-bold mb-0">Entire Archive</h4>
                            </div>
                            <p class="text-muted small mb-0 ms-5">Viewing all historical shipment records.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Filter Trigger -->
                            <div class="dropdown">
                                <button
                                    class="btn btn-sm <?= (isset($_GET['status']) || isset($_GET['agent_id']) || isset($_GET['min_amount'])) ? 'btn-primary' : 'neumorphic-btn' ?> dropdown-toggle px-3 fw-bold"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                    <i class="bi bi-funnel me-1"></i> Filters
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-4 mt-2 glass-dropdown"
                                    style="width: 320px;">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Refine Archive</h6>
                                    <div class="mb-3">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">By Status</label>
                                        <select id="filterStatus"
                                            class="form-select neumorphic-input py-2 small fw-bold">
                                            <option value="">All Statuses</option>
                                            <option value="Pending"
                                                <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                            <option value="Picked Up"
                                                <?= ($_GET['status'] ?? '') == 'Picked Up' ? 'selected' : '' ?>>Picked
                                                Up</option>
                                            <option value="In Transit"
                                                <?= ($_GET['status'] ?? '') == 'In Transit' ? 'selected' : '' ?>>In
                                                Transit</option>
                                            <option value="Out For Delivery"
                                                <?= ($_GET['status'] ?? '') == 'Out For Delivery' ? 'selected' : '' ?>>
                                                Out For Delivery</option>
                                            <option value="Delivered"
                                                <?= ($_GET['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>
                                                Delivered</option>
                                            <option value="Cancelled"
                                                <?= ($_GET['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>
                                                Cancelled</option>
                                            <option value="Returned"
                                                <?= ($_GET['status'] ?? '') == 'Returned' ? 'selected' : '' ?>>Returned
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">Min Price (Rs.)</label>
                                        <input type="number" id="filterMinAmount"
                                            class="form-control neumorphic-input py-2 small fw-bold"
                                            placeholder="e.g. 500" value="<?= escape($_GET['min_amount'] ?? '') ?>">
                                    </div>
                                    <button id="applyFilters"
                                        class="btn btn-primary w-100 neumorphic-btn py-2 fw-bold">Update View</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="premium-table-container mt-4">
                        <div class="table-responsive" id="archiveTableContainer">
                            <table class="premium-table">
                                <thead>
                                    <tr>
                                        <th>Tracking ID <br> Date</th>
                                        <!-- <th>Date</th> -->
                                        <th>Agent</th>
                                        <th>Customer</th>
                                        <th>Route</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($shipments)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">No shipments found in the
                                            archive.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($shipments as $ship): ?>
                                    <tr class="shipment-row">
                                        <td class="fw-bold text-primary">
                                            <?= escape($ship['tracking_number']), "<br>", date('M d, Y', strtotime($ship['created_at'])) ?>
                                        </td>
                                        <td class="small fw-bold text-muted">
                                            <?= escape($ship['agent_name'] ?? 'Direct Admin')?></td>
                                        <td>
                                            <div class="fw-bold small"><?= escape($ship['customer_name']) ?></div>
                                        </td>
                                        <td class="fw-medium"><?= escape($ship['origin_city']) ?> &rarr;
                                            <?= escape($ship['dest_city']) ?></td>
                                        <td class="fw-bold"><?= format_currency($ship['price']) ?></td>
                                        <td>
                                            <?php
                                                $bg = match ($ship['status']) {
                                                    'Pending' => 'status-pending',
                                                    'Delivered' => 'status-delivered',
                                                    'Cancelled' => 'status-cancelled',
                                                    'Returned' => 'status-returned',
                                                    'Picked Up' => 'status-picked-up',
                                                    'Out For Delivery' => 'status-out-delivery',
                                                    default => 'status-transit'
                                                };
                                                ?>
                                            <span
                                                class="badge-neumorphic <?= $bg ?> small fw-bold"><?= escape($ship['status']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="track_shipment.php?tracking_number=<?= $ship['tracking_number'] ?>" class="btn-track">
                                                <i class="bi bi-geo-alt-fill me-1"></i> Track
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const tableBody = document.querySelector('.premium-table tbody');
    const filters = ['filterStatus', 'filterMinAmount'];

    const applyFilters = async () => {
        const status = document.getElementById('filterStatus').value;
        const minAmount = document.getElementById('filterMinAmount').value;

        const params = new URLSearchParams();
        if (status) params.set('status', status);
        if (minAmount) params.set('min_amount', minAmount);
        // Archive page shows all, so no dashboard=1 or limit=6
        params.set('limit', '100');

        try {
            tableBody.innerHTML =
                '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary spinner-border-sm me-2"></div> Filtering...</td></tr>';

            const response = await fetch(`../admin/api/filter_shipments.php?${params.toString()}`);
            if (!response.ok) throw new Error('Filter failed');

            const html = await response.text();
            tableBody.innerHTML = html;
        } catch (error) {
            console.error('AJAX Error:', error);
            tableBody.innerHTML =
                '<tr><td colspan="7" class="text-center text-danger py-4">Error loading shipments. Please try again.</td></tr>';
        }
    };

    document.getElementById('applyFilters').addEventListener('click', (e) => {
        e.preventDefault();
        applyFilters();
        // Close dropdown if on mobile
        const dropdown = bootstrap.Dropdown.getInstance(document.querySelector('.dropdown-toggle'));
        if (dropdown) dropdown.hide();
    });

    // Trigger on enter key in amount field
    document.getElementById('filterMinAmount').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') applyFilters();
    });
    </script>

    <script src="../assets/js/main.js"></script>
</body>

</html>
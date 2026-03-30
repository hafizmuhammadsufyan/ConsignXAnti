<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Only customers can see this
require_role('customer');

$customer_id = current_user_id();
$customer_name = $_SESSION['user_name'];
$msg = '';

// Get all this customer's shipments
try {
    // Get recent shipments (not yet delivered)
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
        WHERE s.customer_id = ? AND s.status != 'Delivered'
        ORDER BY s.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$customer_id]);
    $shipments = $stmt->fetchAll();

    // Summary Metrics (Total Active vs Total Delivered)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ? AND status NOT IN ('Delivered', 'Cancelled', 'Returned')");
    $stmt->execute([$customer_id]);
    $active_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE customer_id = ? AND status = 'Delivered'");
    $stmt->execute([$customer_id]);
    $delivered_count = $stmt->fetchColumn();

    // 1.1 Fetch Status Comparison Data (For this Customer)
    $all_possible_statuses = ['Pending', 'Picked Up', 'In Transit', 'Out For Delivery', 'Delivered', 'Cancelled', 'Returned'];
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM shipments WHERE customer_id = ? GROUP BY status");
    $stmt->execute([$customer_id]);
    $db_status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $status_data = [];
    foreach ($all_possible_statuses as $s) {
        $status_data[$s] = $db_status_data[$s] ?? 0;
    }

    // 1.2 Fetch Last 6 Months Shipments Data (For this Customer)
    $months = [];
    $chart_labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $months[$m] = 0;
        $chart_labels[] = date('M', strtotime("-$i months"));
    }

    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM shipments 
        WHERE customer_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
    ");
    $stmt->execute([$customer_id]);
    $monthly_shipments = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $shipments_series = array_values(array_merge($months, $monthly_shipments));

    // Fetch agents for filter (Standardized)
    $agents_list = $pdo->query("SELECT id, company_name FROM agents ORDER BY company_name")->fetchAll();

} catch (PDOException $e) {
    $msg = display_alert("Failed to load your shipments.", "danger");
    $shipments = [];
    $active_count = 0;
    $delivered_count = 0;
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
            $page_title = 'My Shipments';
            require_once '../includes/top_header.php'; 
            ?>

            <div class="container-fluid px-0">
                <?= $msg ?>

                <!-- Summary Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="neumorphic-card p-4 d-flex align-items-center kpi-card animate-fade-in"
                            style="animation-delay: 0.1s;">
                            <div class="kpi-icon me-4">
                                <i class="bi bi-box-seam text-primary fs-1"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-primary mb-1 kpi-number" data-target="<?= $active_count ?>">
                                    <?= number_format($active_count) ?></h3>
                                <span class="text-muted fw-bold text-uppercase smaller letter-spacing-1">Active
                                    Shipments</span>
                                <br><small class="text-muted opacity-75">In transit or pending</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="neumorphic-card p-4 d-flex align-items-center kpi-card animate-fade-in"
                            style="animation-delay: 0.2s;">
                            <div class="kpi-icon me-4">
                                <i class="bi bi-check-circle text-success fs-1"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold text-success mb-1 kpi-number" data-target="<?= $delivered_count ?>">
                                    <?= number_format($delivered_count) ?></h3>
                                <span class="text-muted fw-bold text-uppercase smaller letter-spacing-1">Delivered
                                    Shipments</span>
                                <br><small class="text-muted opacity-75">Successfully completed</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Shipments section (EXACT CLONE OF ADMIN) -->
                <div class="neumorphic-card p-4 p-md-5 mb-5">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Recent
                                Shipments</h4>
                            <p class="text-muted small mb-0">Priority view of latest active status.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="all_shipments.php"
                                class="btn btn-sm btn-outline-primary px-3 fw-bold me-2 rounded-3 border-2">
                                <i class="bi bi-list-ul me-1"></i> View All
                            </a>
                            <!-- Redesigned Filter Trigger + Clear Button -->
                            <a href="dashboard.php" id="clearFiltersBtn"
                                class="btn btn-sm neumorphic-btn text-danger px-3 fw-bold me-1"
                                style="<?= (isset($_GET['status']) || isset($_GET['agent_id']) || isset($_GET['route_id']) || isset($_GET['min_amount'])) ? '' : 'display:none;' ?>">
                                <i class="bi bi-x-lg me-1"></i> Clear Filters
                            </a>

                            <div class="dropdown">
                                <button
                                    class="btn btn-sm <?= (isset($_GET['status']) || isset($_GET['agent_id']) || isset($_GET['route_id']) || isset($_GET['min_amount'])) ? 'btn-primary' : 'neumorphic-btn' ?> dropdown-toggle px-3 fw-bold"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                    <i class="bi bi-funnel me-1"></i> Advanced Filters
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-4 mt-2 glass-dropdown"
                                    style="width: 320px;">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">Refine Results</h6>
                                    <div class="mb-3">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">By Status</label>
                                        <select id="filterStatus"
                                            class="form-select neumorphic-input py-2 small fw-bold">
                                            <option value="">All Statuses</option>
                                            <option value="Pending"
                                                <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                            <option value="In Transit"
                                                <?= ($_GET['status'] ?? '') == 'In Transit' ? 'selected' : '' ?>>In
                                                Transit</option>
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
                                    <div class="mb-3">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">By Agent</label>
                                        <select id="filterAgent"
                                            class="form-select neumorphic-input py-2 small fw-bold">
                                            <option value="">All Agents</option>
                                            <?php foreach($agents_list as $a): ?>
                                            <option value="<?= $a['id'] ?>"
                                                <?= ($_GET['agent_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                                <?= escape($a['company_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">By Route</label>
                                        <select id="filterRoute"
                                            class="form-select neumorphic-input py-2 small fw-bold">
                                            <option value="">All Routes</option>
                                            <?php 
                                            $cities = get_cities();
                                            foreach($cities as $c): ?>
                                            <option value="<?= $c['id'] ?>"
                                                <?= ($_GET['route_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                                <?= escape($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="smaller fw-bold text-muted mb-1 d-block">Min Price (Rs.)</label>
                                        <input type="number" id="filterMinAmount"
                                            class="form-control neumorphic-input py-2 small fw-bold"
                                            placeholder="e.g. 500" value="<?= escape($_GET['min_amount'] ?? '') ?>">
                                    </div>
                                    <button id="applyAdvancedFilters"
                                        class="btn btn-primary w-100 neumorphic-btn py-2 fw-bold">Apply Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="premium-table-container mt-4">
                        <div class="table-responsive" id="shipmentsTableContainer">
                            <table class="premium-table" id="recentShipmentsTable">
                                <thead>
                                    <tr>
                                        <th>Tracking ID <br> Date</th>
                                        <th>Agent</th>
                                        <th>Customer</th>
                                        <th>Route</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="shipmentsTableBody">
                                    <?php if (empty($shipments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">No matching shipments found.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach (array_slice($shipments, 0, 6) as $ship): ?>
                                    <tr class="shipment-row">
                                        <td class="fw-bold text-primary">
                                            <?= escape($ship['tracking_number']), "<br>",date('M d, Y', strtotime($ship['created_at']))  ?>
                                        </td>
                                        <td class="small fw-bold text-muted">
                                            <?= escape($ship['agent_name'] ?? 'Direct Admin') ?></td>
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
                                            <a href="track_shipment.php?tracking_number=<?= $ship['tracking_number'] ?>"
                                                class="btn-track">
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
    <script src="../assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // AJAX Filters Logic (EXACT CLONE OF ADMIN)
        const applyFilters = async (params) => {
            const btn = document.getElementById('applyAdvancedFilters');
            const btnContent = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';
                btn.disabled = true;
            }

            try {
                // Pass dashboard=1 and limit=6 for the Recent Shipments view
                params.set('dashboard', '1');
                params.set('limit', '6');
                params.set('customer_id', <?= $customer_id ?>);
                const response = await fetch(`../admin/api/filter_shipments.php?${params.toString()}`);
                const html = await response.text();

                const tableBody = document.getElementById('shipmentsTableBody');
                if (tableBody) {
                    tableBody.style.opacity = '0';
                    setTimeout(() => {
                        tableBody.innerHTML = html;
                        tableBody.style.opacity = '1';
                    }, 300);
                }

                if (btn) {
                    setTimeout(() => {
                        btn.innerHTML = btnContent;
                        btn.disabled = false;
                    }, 300);
                }

                // Update URL without reload
                const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() :
                    '');
                window.history.pushState({
                    path: newUrl
                }, '', newUrl);

                // Toggle Filter Button style
                const filterBtnDropdown = document.querySelector('.dropdown-toggle');
                const clearBtn = document.getElementById('clearFiltersBtn');

                if (params.toString()) {
                    if (filterBtnDropdown) {
                        filterBtnDropdown.classList.add('btn-primary');
                        filterBtnDropdown.classList.remove('neumorphic-btn');
                    }
                    if (clearBtn) clearBtn.style.display = 'inline-block';
                } else {
                    if (filterBtnDropdown) {
                        filterBtnDropdown.classList.remove('btn-primary');
                        filterBtnDropdown.classList.add('neumorphic-btn');
                    }
                    if (clearBtn) clearBtn.style.display = 'none';
                }
            } catch (error) {
                console.error('Filter error:', error);
                window.location.reload();
            }
        };

        const filterBtn = document.getElementById('applyAdvancedFilters');
        if (filterBtn) {
            filterBtn.addEventListener('click', () => {
                const status = document.getElementById('filterStatus').value;
                const agent = document.getElementById('filterAgent').value;
                const route = document.getElementById('filterRoute').value;
                const amount = document.getElementById('filterMinAmount').value;

                let params = new URLSearchParams();
                if (status) params.set('status', status);
                if (agent) params.set('agent_id', agent);
                if (route) params.set('route_id', route);
                if (amount) params.set('min_amount', amount);

                applyFilters(params);
            });
        }

        const clearBtn = document.getElementById('clearFiltersBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                ['filterStatus', 'filterAgent', 'filterRoute', 'filterMinAmount'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                applyFilters(new URLSearchParams());
            });
        }



    });
    </script>
</body>

</html>
<?php
// FILE: /consignxAnti/admin/reports.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Secure the route
require_role('admin');

$admin_name = $_SESSION['user_name'];
$msg = '';

// 1. Calculate Date Ranges (Default 7 days)
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$city_filter = $_GET['city'] ?? '';
$agent_filter = $_GET['agent_id'] ?? '';
$status_filter = $_GET['status'] ?? '';
$is_filtered = !empty($_GET['start_date']) || !empty($_GET['city']) || !empty($agent_filter) || !empty($status_filter);

// 2. AJAX Load More Logic
$limit = 15;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$where = ["s.created_at BETWEEN ? AND ?"];
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if (!empty($city_filter)) {
    $where[] = "(s.origin_city_id = ? OR s.destination_city_id = ?)";
    $params[] = (int)$city_filter;
    $params[] = (int)$city_filter;
}

if (!empty($agent_filter)) {
    $where[] = "s.agent_id = ?";
    $params[] = (int)$agent_filter;
}

if (!empty($status_filter)) {
    $where[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(" AND ", $where);

try {
    // We need to get all cities and agents for the filter dropdowns
    $cities = get_cities();
    $agents = $pdo->query("SELECT id, company_name FROM agents WHERE status = 'active' ORDER BY company_name ASC")->fetchAll();

    $sql = "SELECT s.*, 
                   c.name as customer_name,
                   orig.name as origin_city, 
                   dest.name as dest_city,
                   a.company_name as agent_company
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN cities orig ON s.origin_city_id = orig.id
            LEFT JOIN cities dest ON s.destination_city_id = dest.id
            LEFT JOIN agents a ON s.agent_id = a.id
            WHERE $where_sql
            ORDER BY s.created_at DESC
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    // If AJAX request, return only table rows and exit
    if (isset($_GET['ajax'])) {
        if (empty($reports)) {
            exit(''); // No more data
        }
        foreach ($reports as $ship) {
            include '../includes/report_row_template.php';
        }
        exit;
    }

} catch (PDOException $e) {
    $msg = display_alert("Query Error: " . $e->getMessage(), 'danger');
    $reports = [];
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "consignx_report_" . date('Ymd') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tracking ID', 'Date', 'Customer', 'Agent', 'Origin', 'Destination', 'Weight(kg)', 'Price(PKR)', 'Status']);

    // For export, we fetch EVERYTHING matching filters (no limit)
    $export_sql = "SELECT s.*, c.name as customer_name, orig.name as origin_city, dest.name as dest_city, a.company_name as agent_company
                   FROM shipments s
                   LEFT JOIN customers c ON s.customer_id = c.id
                   LEFT JOIN cities orig ON s.origin_city_id = orig.id
                   LEFT JOIN cities dest ON s.destination_city_id = dest.id
                   LEFT JOIN agents a ON s.agent_id = a.id
                   WHERE $where_sql
                   ORDER BY s.created_at DESC";
    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($params);
    
    while ($row = $export_stmt->fetch()) {
        fputcsv($output, [
            $row['tracking_number'],
            $row['created_at'],
            $row['customer_name'],
            $row['agent_company'] ?? 'Admin',
            $row['origin_city'],
            $row['dest_city'],
            $row['weight'],
            $row['price'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ConsignX Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>
<body class="neumorphic-bg">
    <div class="admin-wrapper">
        <?php 
        $role = 'admin';
        $active_page = 'reports.php';
        require_once '../includes/sidebar.php'; 
        ?>

        <main class="main-content">
            <?php 
            $page_title = 'Shipment Reports';
            require_once '../includes/top_header.php'; 
            ?>

            <?= $msg ?>

            <div class="d-flex justify-content-end gap-2 mb-3">
                <a href="reports.php" class="btn btn-sm neumorphic-btn text-muted fw-bold">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </a>
                <a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&city=<?= urlencode($city_filter) ?>&agent_id=<?= urlencode($agent_filter) ?>&status=<?= urlencode($status_filter) ?>"
                    class="btn btn-sm neumorphic-btn text-success fw-bold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                </a>
            </div>

            <!-- Filters -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Date From</label>
                        <input type="date" name="start_date" class="form-control neumorphic-input py-2"
                            value="<?= escape($start_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Date To</label>
                        <input type="date" name="end_date" class="form-control neumorphic-input py-2"
                            value="<?= escape($end_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">City</label>
                        <select name="city" class="form-select neumorphic-input py-2">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= $city_filter == $city['id'] ? 'selected' : '' ?>>
                                    <?= escape($city['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Agent</label>
                        <select name="agent_id" class="form-select neumorphic-input py-2">
                            <option value="">All Agents</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?= $agent['id'] ?>" <?= $agent_filter == $agent['id'] ? 'selected' : '' ?>>
                                    <?= escape($agent['company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Picked Up" <?= $status_filter === 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="In Transit" <?= $status_filter === 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                            <option value="Out For Delivery" <?= $status_filter === 'Out For Delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                            <option value="Delivered" <?= $status_filter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="Returned" <?= $status_filter === 'Returned' ? 'selected' : '' ?>>Returned</option>
                            <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn neumorphic-btn btn-primary flex-grow-1 py-2">Apply</button>
                        <a href="reports.php" class="btn neumorphic-btn btn-secondary py-2" data-bs-toggle="tooltip" title="Reset Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            <div class="premium-table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Analysis Results</h6>
                    <div class="small text-muted">Default: Last 7 Days</div>
                </div>
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>ID <br> Date</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No records found for this period.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $ship): ?>
                                    <?php include '../includes/report_row_template.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($reports) >= $limit): ?>
                    <div class="text-center mt-5">
                        <button id="loadMoreBtn" class="btn neumorphic-btn px-5 py-3 fw-bold text-primary">
                            <i class="bi bi-arrow-down-circle me-2"></i> Load More Shipments
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    let offset = <?= $limit ?>;
                    const loadMoreBtn = document.getElementById('loadMoreBtn');
                    const tableBody = document.querySelector('.premium-table tbody');

                    if (loadMoreBtn) {
                        loadMoreBtn.addEventListener('click', function() {
                            const originalText = loadMoreBtn.innerHTML;
                            loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Loading...';
                            loadMoreBtn.disabled = true;

                            const url = new URL(window.location.href);
                            url.searchParams.set('ajax', '1');
                            url.searchParams.set('offset', offset);

                            fetch(url)
                                .then(response => response.text())
                                .then(data => {
                                    if (data.trim() === '') {
                                        loadMoreBtn.innerHTML = 'All Records Loaded';
                                        loadMoreBtn.classList.add('text-muted');
                                        loadMoreBtn.disabled = true;
                                        return;
                                    }
                                    tableBody.insertAdjacentHTML('beforeend', data);
                                    offset += <?= $limit ?>;
                                    loadMoreBtn.innerHTML = originalText;
                                    loadMoreBtn.disabled = false;
                                })
                                .catch(err => {
                                    console.error(err);
                                    loadMoreBtn.innerHTML = 'Error Loading More';
                                    loadMoreBtn.disabled = false;
                                });
                        });
                    }
                });
            </script>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
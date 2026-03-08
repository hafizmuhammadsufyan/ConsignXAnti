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

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$city_filter = $_GET['city'] ?? '';

// Build Query
$query = "
    SELECT s.tracking_number, s.created_at, s.status, s.weight, s.price,
           a.company_name as agent_name, 
           c.name as customer_name,
           orig.name as origin_city, dest.name as dest_city
    FROM shipments s
    LEFT JOIN agents a ON s.agent_id = a.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN cities orig ON s.origin_city_id = orig.id
    LEFT JOIN cities dest ON s.destination_city_id = dest.id
    WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
";

$params = [
    'start_date' => $start_date,
    'end_date' => $end_date
];

if (!empty($city_filter)) {
    $query .= " AND (s.origin_city_id = :city OR s.destination_city_id = :city)";
    $params['city'] = $city_filter;
}

$query .= " ORDER BY s.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    $cities = get_cities();
} catch (PDOException $e) {
    $msg = display_alert("Data error: " . escape($e->getMessage()), "danger");
    $reports = [];
    $cities = [];
}

// Handle Export to CSV (Readable by Excel as XLSX alternative natively in PHP without bloat)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "consignx_report_" . date('Ymd') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // headers
    fputcsv($output, ['Tracking ID', 'Date', 'Customer', 'Agent', 'Origin', 'Destination', 'Weight(kg)', 'Price($)', 'Status']);

    foreach ($reports as $row) {
        fputcsv($output, [
            $row['tracking_number'],
            $row['created_at'],
            $row['customer_name'],
            $row['agent_name'] ?? 'Admin',
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
        <!-- Main Sidebar -->
        <nav class="sidebar d-flex flex-column justify-content-between neumorphic-card m-3 border-0">
            <div>
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">ConsignX</h3>
                    <small class="text-muted">Admin Portal</small>
                </div>
                <ul class="nav flex-column gap-2 mt-4">
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="dashboard.php">
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
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active" href="reports.php">
                            <i class="bi bi-graph-up me-2"></i> Reports
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
                <h2 class="fw-bold text-primary mb-0">Shipment Reports</h2>
                <a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&city=<?= urlencode($city_filter) ?>"
                    class="btn neumorphic-btn btn-success fw-bold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Data
                </a>
            </div>

            <?= $msg ?>

            <!-- Filters -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Date From</label>
                        <input type="date" name="start_date" class="form-control neumorphic-input py-2"
                            value="<?= escape($start_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Date To</label>
                        <input type="date" name="end_date" class="form-control neumorphic-input py-2"
                            value="<?= escape($end_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Filter by City (Origin/Dest)</label>
                        <select name="city" class="form-select neumorphic-input py-2">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= $city_filter == $city['id'] ? 'selected' : '' ?>>
                                    <?= escape($city['name']) ?>,
                                    <?= escape($city['state']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn neumorphic-btn btn-primary w-100 py-2">Generate</button>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            <div class="neumorphic-card p-4">
                <h6 class="fw-bold mb-4">Showing
                    <?= count($reports) ?> Results
                </h6>
                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No records found for this period.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $row): ?>
                                    <tr>
                                        <td class="fw-bold text-primary">
                                            <?= escape($row['tracking_number']) ?>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?= escape($row['customer_name']) ?>
                                        </td>
                                        <td>
                                            <?= escape($row['origin_city']) ?> &rarr;
                                            <?= escape($row['dest_city']) ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?= format_currency($row['price']) ?>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-light text-dark border">
                                                <?= escape($row['status']) ?>
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
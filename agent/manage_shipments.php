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
        $new_status = trim($_POST['new_status'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        try {
            if (empty($new_status)) {
                throw new Exception("Status cannot be empty");
            }

            $allowed_statuses = ['Pending', 'Picked Up', 'In Transit', 'Out For Delivery', 'Delivered', 'Returned', 'Cancelled'];

            if (!in_array($new_status, $allowed_statuses)) {
                throw new Exception("Invalid status value");
            }

            // Verify ownership first
            $stmt = $pdo->prepare("SELECT id, price, status FROM shipments WHERE id = ? AND agent_id = ?");
            $stmt->execute([$shipment_id, $agent_id]);
            $shipment = $stmt->fetch();

            if ($shipment) {
                $current_status = $shipment['status'];

                if (in_array($current_status, ['Delivered', 'Returned', 'Cancelled'])) {
                    throw new Exception("This shipment is locked and cannot be modified.");
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE shipments SET status = :status WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $shipment_id]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("Status update failed or no rows affected.");
                }

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
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = display_alert("Failed to update status: " . escape($e->getMessage()), "danger");
        }
    }
}

// Filtering Logic
$where_clauses = ["s.agent_id = ?"];
$params = [$agent_id];

if (!empty($_GET['date_from'])) {
    $where_clauses[] = "s.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where_clauses[] = "s.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}
if (!empty($_GET['city_id'])) {
    $where_clauses[] = "(s.origin_city_id = ? OR s.destination_city_id = ?)";
    $params[] = $_GET['city_id'];
    $params[] = $_GET['city_id'];
}
if (!empty($_GET['status'])) {
    $where_clauses[] = "s.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['tracking_id'])) {
    $where_clauses[] = "s.tracking_number LIKE ?";
    $params[] = "%" . $_GET['tracking_id'] . "%";
}

$where_sql = implode(" AND ", $where_clauses);

// AJAX Load More Logic
$limit = 15;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $sql = "SELECT s.*, 
                   c.name as customer_name, c.email as customer_email,
                   orig.name as origin_city, dest.name as dest_city
            FROM shipments s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN cities orig ON s.origin_city_id = orig.id
            LEFT JOIN cities dest ON s.destination_city_id = dest.id
            WHERE $where_sql
            ORDER BY s.created_at DESC
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $shipments = $stmt->fetchAll();

    // AJAX Response
    if (isset($_GET['ajax'])) {
        if (empty($shipments)) exit('');
        foreach ($shipments as $ship) {
            include '../includes/agent_shipment_row_template.php';
        }
        exit;
    }
    
    $cities = get_cities();
} catch (PDOException $e) {
    $msg = display_alert("Error loading data.", "danger");
    $shipments = [];
    $cities = [];
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
        <?php 
        $role = 'agent';
        $active_page = 'manage_shipments.php';
        require_once '../includes/sidebar.php'; 
        ?>


        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Manage Your Shipments</h2>
                    <p class="text-muted mb-0 small">Track and update processed packages.</p>
                </div>
                <a href="create_shipment.php" class="btn neumorphic-btn btn-primary fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> New Shipment
                </a>
            </header>

            <?= $msg ?>

            <!-- Filters Section -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">From Date</label>
                        <input type="date" name="date_from" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">To Date</label>
                        <input type="date" name="date_to" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Tracking ID</label>
                        <input type="text" name="tracking_id" class="form-control neumorphic-input py-2" placeholder="By Tracking ID" value="<?= escape($_GET['tracking_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">City</label>
                        <select name="city_id" class="form-select neumorphic-input py-2">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $ct): ?>
                                <option value="<?= $ct['id'] ?>" <?= (isset($_GET['city_id']) && $_GET['city_id'] == $ct['id']) ? 'selected' : '' ?>>
                                    <?= escape($ct['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Picked Up" <?= ($_GET['status'] ?? '') == 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="In Transit" <?= ($_GET['status'] ?? '') == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                            <option value="Out For Delivery" <?= ($_GET['status'] ?? '') == 'Out For Delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                            <option value="Delivered" <?= ($_GET['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="Returned" <?= ($_GET['status'] ?? '') == 'Returned' ? 'selected' : '' ?>>Returned</option>
                            <option value="Cancelled" <?= ($_GET['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary neumorphic-btn w-100 py-2"><i class="bi bi-filter"></i> Filter</button>
                    </div>
                    <div class="col-md-auto">
                        <a href="manage_shipments.php" class="btn btn-secondary neumorphic-btn py-2" data-bs-toggle="tooltip" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <a href="../includes/export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success neumorphic-btn fw-bold">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                </a>
            </div>

            <div class="premium-table-container">
                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Tracking ID <br> Date</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th class="text-end">Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shipments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No shipments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shipments as $ship): ?>
                                    <?php include '../includes/agent_shipment_row_template.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($shipments) >= $limit): ?>
                    <div class="text-center mt-4">
                        <button id="loadMoreBtn" class="btn neumorphic-btn px-5 py-2 fw-bold text-primary">
                            <i class="bi bi-arrow-down-circle me-1"></i> Load More
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
<?php
// FILE: /consignxAnti/admin/manage_shipments.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

require_once '../includes/auth.php';
require_once '../includes/mailer.php';

// Secure the route
require_role('admin');

$admin_id = current_user_id();
$admin_name = $_SESSION['user_name'];
$msg = '';

// Handle Actions (Create Shipment, Update Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf)) {
        $msg = display_alert("Invalid security token.", "danger");
    } else {
        $action = $_POST['action'];

        if ($action === 'create_shipment') {
            // Admin created shipment (direct customer interaction or oversight)
            $customer_name = trim($_POST['customer_name'] ?? '');
            $customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL);
            $customer_phone = trim($_POST['customer_phone'] ?? '');

            $recipient_name = trim($_POST['recipient_name'] ?? '');
            $recipient_phone = trim($_POST['recipient_phone'] ?? '');
            $recipient_address = trim($_POST['recipient_address'] ?? '');

            $origin_city = (int) $_POST['origin_city_id'];
            $dest_city = (int) $_POST['destination_city_id'];
            $weight = (float) $_POST['weight'];
            $price = (float) $_POST['price'];

            try {
                $pdo->beginTransaction();

                // 1. Find or create customer
                $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $customer_email]);
                $customer = $stmt->fetch();

                if ($customer) {
                    $customer_id = $customer['id'];
                    $is_new_customer = false;
                } else {
                    $temp_password = strtolower(str_replace(' ', '', $customer_name)) . rand(100, 999);
                    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (:name, :email, :phone, :pass)");
                    $stmt->execute(['name' => $customer_name, 'email' => $customer_email, 'phone' => $customer_phone, 'pass' => $hashed_password]);
                    $customer_id = $pdo->lastInsertId();
                    $is_new_customer = true;
                }

                // 2. Generate Tracking
                $tracking_number = generate_tracking_number();

                // 3. Trigger Email
                if ($is_new_customer) {
                    send_shipment_notification_new($customer_email, $customer_name, $temp_password, $tracking_number);
                } else {
                    send_shipment_notification_existing($customer_email, $customer_name, $tracking_number);
                }

                // 3. Create Shipment (Agent ID is null since admin created it)
                $stmt = $pdo->prepare("
                    INSERT INTO shipments 
                    (tracking_number, customer_id, origin_city_id, destination_city_id, recipient_name, recipient_phone, recipient_address, weight, price, status) 
                    VALUES (:tracking, :cust_id, :origin, :dest, :rec_name, :rec_phone, :rec_address, :weight, :price, 'Pending')
                ");
                $stmt->execute([
                    'tracking' => $tracking_number,
                    'cust_id' => $customer_id,
                    'origin' => $origin_city,
                    'dest' => $dest_city,
                    'rec_name' => $recipient_name,
                    'rec_phone' => $recipient_phone,
                    'rec_address' => $recipient_address,
                    'weight' => $weight,
                    'price' => $price
                ]);
                $shipment_id = $pdo->lastInsertId();

                // 4. Create Initial Status History
                $stmt = $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, remarks, changed_by_role, changed_by_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$shipment_id, 'Pending', 'Shipment Created by Admin', 'admin', $admin_id]);

                $pdo->commit();
                $msg = display_alert("Shipment ($tracking_number) created successfully.", "success");

            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = display_alert("Failed to create shipment: " . escape($e->getMessage()), "danger");
            }

        } elseif ($action === 'update_status') {
            $shipment_id = (int) $_POST['shipment_id'];
            $new_status = trim($_POST['new_status'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');
            $location = trim($_POST['location'] ?? '');

            try {
                if (empty($new_status)) {
                    throw new Exception("Status cannot be empty");
                }

                $allowed_statuses = [
                    'Pending',
                    'Picked Up',
                    'In Transit',
                    'Out For Delivery',
                    'Delivered',
                    'Returned',
                    'Cancelled'
                ];

                if (!in_array($new_status, $allowed_statuses)) {
                    throw new Exception("Invalid status value");
                }

                // Check current status from DB
                $stmt = $pdo->prepare("SELECT status FROM shipments WHERE id = ?");
                $stmt->execute([$shipment_id]);
                $current_status = $stmt->fetchColumn();

                if (!$current_status) {
                    throw new Exception("Shipment not found.");
                }

                if (in_array($current_status, ['Delivered', 'Returned', 'Cancelled'])) {
                    throw new Exception("This shipment is locked and cannot be modified.");
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE shipments SET status = :status WHERE id = :id");
                $stmt->execute([
                    'status' => $new_status,
                    'id' => $shipment_id
                ]);

                $stmt = $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, location, remarks, changed_by_role, changed_by_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$shipment_id, $new_status, $location, $remarks, 'admin', $admin_id]);

                // If Delivered, record revenue
                if ($new_status === 'Delivered') {
                    $stmt = $pdo->prepare("SELECT price, agent_id FROM shipments WHERE id = ?");
                    $stmt->execute([$shipment_id]);
                    $shipData = $stmt->fetch();

                    // Check if already recorded
                    $revCheck = $pdo->prepare("SELECT id FROM revenue WHERE shipment_id = ?");
                    $revCheck->execute([$shipment_id]);
                    if (!$revCheck->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO revenue (shipment_id, agent_id, amount, transaction_date) VALUES (?, ?, ?, CURDATE())");
                        $stmt->execute([$shipment_id, $shipData['agent_id'], $shipData['price']]);
                    }
                }

                $pdo->commit();
                $msg = display_alert("Shipment status updated to $new_status.", "success");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = display_alert("Failed to update status: " . escape($e->getMessage()), "danger");
            }
        } elseif ($action === 'delete_shipment') {
            $shipment_id = (int) $_POST['shipment_id'];
            try {
                // Check current status from DB
                $stmt = $pdo->prepare("SELECT status FROM shipments WHERE id = ?");
                $stmt->execute([$shipment_id]);
                $current_status = $stmt->fetchColumn();

                if (!$current_status) {
                    throw new Exception("Shipment not found.");
                }

                if (in_array($current_status, ['Delivered', 'Returned', 'Cancelled'])) {
                    throw new Exception("This shipment cannot be deleted.");
                }

                $pdo->beginTransaction();

                // Cascading delete handles history/revenue
                $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = :id");
                $stmt->execute(['id' => $shipment_id]);

                $pdo->commit();
                $msg = display_alert("Shipment deleted successfully.", "success");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = display_alert("Failed to delete shipment: " . escape($e->getMessage()), "danger");
            }
        }
    }
}

// Filtering Logic
$where_clauses = ["1=1"];
$params = [];

if (!empty($_GET['date_from'])) {
    $where_clauses[] = "s.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where_clauses[] = "s.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}
if (!empty($_GET['agent_id'])) {
    $where_clauses[] = "s.agent_id = ?";
    $params[] = $_GET['agent_id'];
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
                   a.company_name as agent_name, 
                   c.name as customer_name, c.email as customer_email,
                   orig.name as origin_city, dest.name as dest_city
            FROM shipments s
            LEFT JOIN agents a ON s.agent_id = a.id
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
            include '../includes/shipment_row_template.php';
        }
        exit;
    }

    // Fetch agents for filter dropdown
    $agents = $pdo->query("SELECT id, company_name FROM agents WHERE status = 'active' ORDER BY company_name ASC")->fetchAll();
    
    $cities = get_cities();
} catch (PDOException $e) {
    $msg = display_alert("Error loading data: " . escape($e->getMessage()), 'danger');
    $shipments = [];
    $cities = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shipments - ConsignX Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">

        <!-- Main Sidebar -->
        <?php 
        $role = 'admin';
        $active_page = 'manage_shipments.php';
        require_once '../includes/sidebar.php'; 
        ?>


        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Manage Shipments</h2>
                    <p class="text-muted mb-0 small">Overview and tracking of all logistics.</p>
                </div>
                <button class="btn neumorphic-btn btn-primary fw-bold" data-bs-toggle="modal"
                    data-bs-target="#createShipmentModal">
                    <i class="bi bi-plus-lg me-1"></i> New Shipment
                </button>
            </header>

            <?= $msg ?>

            <!-- Filters Section -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">From Date</label>
                        <input type="date" name="date_from" class="form-control neumorphic-input py-2"
                            value="<?= escape($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">To Date</label>
                        <input type="date" name="date_to" class="form-control neumorphic-input py-2"
                            value="<?= escape($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Agent</label>
                        <select name="agent_id" class="form-select neumorphic-input py-2">
                            <option value="">All Agents</option>
                            <?php foreach ($agents as $ag): ?>
                            <option value="<?= $ag['id'] ?>"
                                <?= (isset($_GET['agent_id']) && $_GET['agent_id'] == $ag['id']) ? 'selected' : '' ?>>
                                <?= escape($ag['company_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">City</label>
                        <select name="city_id" class="form-select neumorphic-input py-2">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $ct): ?>
                            <option value="<?= $ct['id'] ?>"
                                <?= (isset($_GET['city_id']) && $_GET['city_id'] == $ct['id']) ? 'selected' : '' ?>>
                                <?= escape($ct['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Tracking ID</label>
                        <input type="text" name="tracking_id" class="form-control neumorphic-input py-2" placeholder="Search by Tracking ID" value="<?= escape($_GET['tracking_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>
                                Pending</option>
                            <option value="Picked Up" <?= ($_GET['status'] ?? '') == 'Picked Up' ? 'selected' : '' ?>>
                                Picked Up</option>
                            <option value="In Transit" <?= ($_GET['status'] ?? '') == 'In Transit' ? 'selected' : '' ?>>
                                In Transit</option>
                            <option value="Out For Delivery"
                                <?= ($_GET['status'] ?? '') == 'Out For Delivery' ? 'selected' : '' ?>>Out For Delivery
                            </option>
                            <option value="Delivered" <?= ($_GET['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>
                                Delivered</option>
                            <option value="Returned" <?= ($_GET['status'] ?? '') == 'Returned' ? 'selected' : '' ?>>
                                Returned</option>
                            <option value="Cancelled" <?= ($_GET['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>
                                Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary neumorphic-btn flex-grow-1"><i
                                class="bi bi-filter"></i> Apply Filter</button>
                        <a href="manage_shipments.php" class="btn btn-secondary neumorphic-btn"><i
                                class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <a href="../includes/export_excel.php?<?= http_build_query($_GET) ?>"
                    class="btn btn-success neumorphic-btn fw-bold">
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
                                <th>Agent</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shipments)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No shipments found matching your
                                    criteria.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($shipments as $ship): ?>
                            <?php include '../includes/shipment_row_template.php'; ?>
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
                        loadMoreBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2"></span> Loading...';
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

    <!-- Create Shipment Modal -->
    <div class="modal fade" id="createShipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content neumorphic-card border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary">Create New Shipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create_shipment">

                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Customer Details -->
                            <div class="col-12">
                                <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">Sender (Customer) Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Name</label>
                                        <input type="text" name="customer_name"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Email (Auto-creates account)</label>
                                        <input type="email" name="customer_email"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Phone</label>
                                        <input type="text" name="customer_phone"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Recipient Details -->
                            <div class="col-12">
                                <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">Recipient Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Name</label>
                                        <input type="text" name="recipient_name"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Phone</label>
                                        <input type="text" name="recipient_phone"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Delivery Address</label>
                                        <textarea name="recipient_address" rows="2"
                                            class="form-control neumorphic-input py-2" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Routing & Billing Details -->
                            <div class="col-12">
                                <h6 class="fw-bold text-muted mb-3 border-bottom pb-2">Routing & Billing</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Origin City</label>
                                        <select name="origin_city_id" class="form-select neumorphic-input py-2"
                                            required>
                                            <option value="">Select Origin...</option>
                                            <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>">
                                                <?= escape($city['name']) ?>,
                                                <?= escape($city['state']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Destination City</label>
                                        <select name="destination_city_id" class="form-select neumorphic-input py-2"
                                            required>
                                            <option value="">Select Destination...</option>
                                            <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>">
                                                <?= escape($city['name']) ?>,
                                                <?= escape($city['state']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Weight (kg)</label>
                                        <input type="number" step="0.01" name="weight"
                                            class="form-control neumorphic-input py-2" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Shipping Fee (PKR)</label>
                                        <div class="input-group">
                                            <span
                                                class="input-group-text border-0 bg-transparent text-muted small fw-bold">Rs.</span>
                                            <input type="text" name="price" id="admin_price_display"
                                                class="form-control neumorphic-input py-2" required readonly
                                                placeholder="Auto-calculated">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn text-muted fw-bold px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn neumorphic-btn btn-primary px-5">Generate Shipment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    // Auto-pricing logic for Admin Modal
    const coords = {
        'Karachi': [24.86, 67.00],
        'Lahore': [31.52, 74.35],
        'Islamabad': [33.68, 73.04],
        'Rawalpindi': [33.56, 73.01],
        'Faisalabad': [31.45, 73.13],
        'Multan': [30.15, 71.52],
        'Peshawar': [34.01, 71.52],
        'Quetta': [30.17, 66.97],
        'Hyderabad': [25.39, 68.37],
        'Sialkot': [32.49, 74.52],
        'Gujranwala': [32.18, 74.19],
        'Bahawalpur': [29.35, 71.69]
    };

    function calculateAdminPrice() {
        const modal = document.querySelector('#createShipmentModal');
        const originEl = modal.querySelector('select[name="origin_city_id"] option:checked');
        const destEl = modal.querySelector('select[name="destination_city_id"] option:checked');
        const weightEl = modal.querySelector('input[name="weight"]');
        const priceOut = modal.querySelector('#admin_price_display');

        if (!originEl || !destEl || !weightEl || !priceOut) return;

        const origin = originEl.text.split(',')[0].trim();
        const dest = destEl.text.split(',')[0].trim();
        const weight = parseFloat(weightEl.value) || 0;

        if (!origin || !dest || weight <= 0) {
            priceOut.value = '';
            return;
        }

        let distance = 100;
        if (coords[origin] && coords[dest]) {
            const lat1 = coords[origin][0],
                lon1 = coords[origin][1];
            const lat2 = coords[dest][0],
                lon2 = coords[dest][1];
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            distance = R * c;
        }

        const base = 150,
            rWeight = 80,
            rDist = 0.5;
        const total = base + (weight * rWeight) + (distance * rDist);
        priceOut.value = total.toFixed(2);
    }
                                            

    document.querySelectorAll('#createShipmentModal select, #createShipmentModal input[name="weight"]').forEach(el => {
        el.addEventListener('change', calculateAdminPrice);
        el.addEventListener('input', calculateAdminPrice);
    });
                                            
    </script>
</body>

</html>
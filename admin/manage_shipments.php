<?php
// FILE: /consignxAnti/admin/manage_shipments.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

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
            $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING);
            $customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL);
            $customer_phone = filter_input(INPUT_POST, 'customer_phone', FILTER_SANITIZE_STRING);

            $recipient_name = filter_input(INPUT_POST, 'recipient_name', FILTER_SANITIZE_STRING);
            $recipient_phone = filter_input(INPUT_POST, 'recipient_phone', FILTER_SANITIZE_STRING);
            $recipient_address = filter_input(INPUT_POST, 'recipient_address', FILTER_SANITIZE_STRING);

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
                } else {
                    $temp_password = hash_password(bin2hex(random_bytes(8))); // Auto-gen password
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (:name, :email, :phone, :pass)");
                    $stmt->execute(['name' => $customer_name, 'email' => $customer_email, 'phone' => $customer_phone, 'pass' => $temp_password]);
                    $customer_id = $pdo->lastInsertId();
                    // TODO: Trigger Email to customer with auto-gen pass
                }

                // 2. Generate Tracking
                $tracking_number = generate_tracking_number();

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
            $new_status = $_POST['new_status'];
            $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING) ?? '';
            $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING) ?? '';

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE shipments SET status = :status WHERE id = :id");
                $stmt->execute(['status' => $new_status, 'id' => $shipment_id]);

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
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = display_alert("Failed to update status: " . escape($e->getMessage()), "danger");
            }
        } elseif ($action === 'delete_shipment') {
            $shipment_id = (int) $_POST['shipment_id'];
            try {
                // Cascading delete handles history/revenue
                $stmt = $pdo->prepare("DELETE FROM shipments WHERE id = :id");
                $stmt->execute(['id' => $shipment_id]);
                $msg = display_alert("Shipment deleted successfully.", "success");
            } catch (PDOException $e) {
                $msg = display_alert("Failed to delete shipment: " . escape($e->getMessage()), "danger");
            }
        }
    }
}

// Fetch all shipments
try {
    $stmt = $pdo->query("
        SELECT s.*, 
               a.company_name as agent_name, 
               c.name as customer_name, c.email as customer_email,
               orig.name as origin_city, dest.name as dest_city
        FROM shipments s
        LEFT JOIN agents a ON s.agent_id = a.id
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN cities orig ON s.origin_city_id = orig.id
        LEFT JOIN cities dest ON s.destination_city_id = dest.id
        ORDER BY s.created_at DESC
    ");
    $shipments = $stmt->fetchAll();

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
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="manage_shipments.php">
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
                <h2 class="fw-bold text-primary mb-0">Manage Shipments</h2>
                <button class="btn neumorphic-btn btn-primary fw-bold" data-bs-toggle="modal"
                    data-bs-target="#createShipmentModal">
                    <i class="bi bi-plus-lg me-1"></i> New Shipment
                </button>
            </div>

            <?= $msg ?>

            <div class="neumorphic-card p-4">
                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tracking ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Agent</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shipments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No shipments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shipments as $ship): ?>
                                    <tr>
                                        <td class="fw-bold text-primary">
                                            <?= escape($ship['tracking_number']) ?>
                                        </td>
                                        <td><small class="text-muted">
                                                <?= date('M d, Y', strtotime($ship['created_at'])) ?>
                                            </small></td>
                                        <td>
                                            <div class="fw-bold">
                                                <?= escape($ship['customer_name']) ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= escape($ship['customer_email']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= escape($ship['agent_name'] ?? 'Direct Admin') ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="text-muted">
                                                    <?= escape($ship['origin_city']) ?>
                                                </span>
                                                <i class="bi bi-arrow-right mx-2 text-primary"></i>
                                                <span class="fw-medium">
                                                    <?= escape($ship['dest_city']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $bg = match ($ship['status']) {
                                                'Pending' => 'bg-warning text-dark',
                                                'Picked Up', 'In Transit', 'Out For Delivery' => 'bg-info text-dark',
                                                'Delivered' => 'bg-success text-white',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge rounded-pill <?= $bg ?> px-3 py-2">
                                                <?= escape($ship['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm neumorphic-btn" type="button"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-2 rounded-3">
                                                    <!-- Form for Status Update inside Dropdown -->
                                                    <li>
                                                        <h6 class="dropdown-header">Update Status</h6>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="px-3 py-1">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?= escape($_SESSION['csrf_token']) ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="shipment_id" value="<?= $ship['id'] ?>">

                                                            <select name="new_status" class="form-select form-select-sm mb-2"
                                                                required>
                                                                <option value="" disabled>Select Status...</option>
                                                                <option value="Picked Up" <?= $ship['status'] == 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                                                                <option value="In Transit" <?= $ship['status'] == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                                                                <option value="Out For Delivery"
                                                                    <?= $ship['status'] == 'Out For Delivery' ? 'selected' : '' ?>>Out
                                                                    For Delivery</option>
                                                                <option value="Delivered" <?= $ship['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                            </select>

                                                            <input type="text" name="location"
                                                                class="form-control form-control-sm mb-2"
                                                                placeholder="Current Location (Optional)">
                                                            <input type="text" name="remarks"
                                                                class="form-control form-control-sm mb-2"
                                                                placeholder="Remarks (Optional)">

                                                            <button type="submit"
                                                                class="btn btn-sm btn-primary w-100">Save</button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="px-3"
                                                            onsubmit="return confirm('WARNING: Are you sure you want to delete this shipment? This cannot be undone.');">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?= escape($_SESSION['csrf_token']) ?>">
                                                            <input type="hidden" name="action" value="delete_shipment">
                                                            <input type="hidden" name="shipment_id" value="<?= $ship['id'] ?>">
                                                            <button type="submit"
                                                                class="btn btn-sm text-danger text-start w-100 p-0 border-0 bg-transparent">
                                                                <i class="bi bi-trash me-2"></i> Delete Shipment
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
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
                                        <label class="form-label small">Price/Fee ($)</label>
                                        <input type="number" step="0.01" name="price"
                                            class="form-control neumorphic-input py-2" required>
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
</body>

</html>
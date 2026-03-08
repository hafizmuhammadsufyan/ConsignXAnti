<?php
// FILE: /consignxAnti/agent/create_shipment.php

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

$cities = get_cities();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf)) {
        $msg = display_alert("Invalid security token.", "danger");
    } else {
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
                // Auto-generate password for new customer
                $plain_pass = substr(md5(uniqid()), 0, 8);
                $temp_password = hash_password($plain_pass);

                $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (:name, :email, :phone, :pass)");
                $stmt->execute(['name' => $customer_name, 'email' => $customer_email, 'phone' => $customer_phone, 'pass' => $temp_password]);
                $customer_id = $pdo->lastInsertId();

                // TODO: Send Email via PHPMailer with $plain_pass
            }

            // 2. Generate Tracking
            $tracking_number = generate_tracking_number();

            // 3. Create Shipment linked to AGENT
            $stmt = $pdo->prepare("
                INSERT INTO shipments 
                (tracking_number, agent_id, customer_id, origin_city_id, destination_city_id, recipient_name, recipient_phone, recipient_address, weight, price, status) 
                VALUES (:tracking, :agent_id, :cust_id, :origin, :dest, :rec_name, :rec_phone, :rec_address, :weight, :price, 'Pending')
            ");
            $stmt->execute([
                'tracking' => $tracking_number,
                'agent_id' => $agent_id,
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
            $stmt->execute([$shipment_id, 'Pending', 'Shipment Created by Agent', 'agent', $agent_id]);

            $pdo->commit();
            $msg = display_alert("Shipment ($tracking_number) created successfully.", "success");

            // Clear selections but keep msg
            $_POST = array();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = display_alert("Failed to create shipment: " . escape($e->getMessage()), "danger");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Shipment - ConsignX Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column justify-content-between neumorphic-card m-3 border-0">
            <div>
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary mb-0">ConsignX</h3>
                    <small class="text-muted">Agent Portal</small>
                </div>

                <div class="text-center mt-3 mb-4">
                    <span class="badge rounded-pill bg-primary px-3 py-2 fw-medium text-uppercase shadow-sm">
                        <i class="bi bi-building me-1"></i>
                        <?= escape($company_name) ?>
                    </span>
                </div>

                <ul class="nav flex-column gap-2 mt-4">
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="create_shipment.php">
                            <i class="bi bi-plus-circle me-2"></i> New Shipment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="manage_shipments.php">
                            <i class="bi bi-box-seam me-2"></i> Manage Shipments
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
                <h2 class="fw-bold text-primary mb-0">Create Shipment</h2>
            </div>

            <?= $msg ?>

            <div class="neumorphic-card p-4 p-md-5">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">

                    <div class="row g-5">
                        <!-- Sender Details -->
                        <div class="col-md-6">
                            <h5 class="fw-bold text-muted mb-4 border-bottom pb-2"><i
                                    class="bi bi-person me-2"></i>Sender (Customer) Details</h5>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="customer_name" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="customer_email" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_email'] ?? '') ?>">
                                <div class="form-text small">An account will be created automatically if they are new.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="tel" name="customer_phone" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_phone'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Recipient Details -->
                        <div class="col-md-6">
                            <h5 class="fw-bold text-muted mb-4 border-bottom pb-2"><i
                                    class="bi bi-person-check me-2"></i>Recipient Details</h5>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="recipient_name" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['recipient_name'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="tel" name="recipient_phone" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['recipient_phone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Delivery Address</label>
                                <textarea name="recipient_address" rows="3" class="form-control neumorphic-input py-2"
                                    required><?= escape($_POST['recipient_address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Logistics & Route -->
                        <div class="col-12 mt-4">
                            <h5 class="fw-bold text-muted mb-4 border-bottom pb-2"><i
                                    class="bi bi-truck me-2"></i>Logistics & Routing</h5>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Origin City</label>
                                    <select name="origin_city_id" class="form-select neumorphic-input py-2" required>
                                        <option value="" disabled selected>Select Origin...</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>" <?= (isset($_POST['origin_city_id']) && $_POST['origin_city_id'] == $city['id']) ? 'selected' : '' ?>>
                                                <?= escape($city['name']) ?>,
                                                <?= escape($city['state']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Destination City</label>
                                    <select name="destination_city_id" class="form-select neumorphic-input py-2"
                                        required>
                                        <option value="" disabled selected>Select Destination...</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= $city['id'] ?>" <?= (isset($_POST['destination_city_id']) && $_POST['destination_city_id'] == $city['id']) ? 'selected' : '' ?>>
                                                <?= escape($city['name']) ?>,
                                                <?= escape($city['state']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Weight (kg)</label>
                                    <div class="input-group neumorphic-inset p-0 overflow-hidden"
                                        style="border-radius: var(--border-radius-input);">
                                        <input type="number" step="0.01" name="weight"
                                            class="form-control border-0 bg-transparent shadow-none" required
                                            value="<?= escape($_POST['weight'] ?? '') ?>">
                                        <span
                                            class="input-group-text border-0 bg-transparent fw-bold text-muted">kg</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Shipping Fee ($)</label>
                                    <div class="input-group neumorphic-inset p-0 overflow-hidden"
                                        style="border-radius: var(--border-radius-input);">
                                        <span
                                            class="input-group-text border-0 bg-transparent fw-bold text-muted">$</span>
                                        <input type="number" step="0.01" name="price"
                                            class="form-control border-0 bg-transparent shadow-none" required
                                            value="<?= escape($_POST['price'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-end border-top pt-4">
                        <button type="submit" class="btn neumorphic-btn btn-primary fw-bold px-5 py-3 fs-5">Generate
                            Shipment & Waybill</button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>
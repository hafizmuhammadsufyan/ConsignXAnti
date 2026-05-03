<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

// Only agents can create shipments
require_role('agent');

$agent_id = current_user_id();
$company_name = $_SESSION['company_name'];
$msg = '';

$cities = get_cities();

// Process shipment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf)) {
        $msg = display_alert("Invalid security token.", "danger");
    } else {
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_SANITIZE_EMAIL);
        $customer_phone = trim($_POST['customer_phone'] ?? '');

        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $recipient_phone = trim($_POST['recipient_phone'] ?? '');
        $recipient_address = trim($_POST['recipient_address'] ?? '');

        $origin_city = (int) $_POST['origin_city_id'];
        $dest_city = (int) $_POST['destination_city_id'];
        $weight = (float) $_POST['weight'];
        
        // Validate inputs using global validation functions
        $customer_name_validation = validate_name($customer_name);
        $customer_email_validation = validate_email($customer_email);
        $customer_phone_validation = validate_phone($customer_phone);
        $recipient_name_validation = validate_name($recipient_name);
        $recipient_phone_validation = validate_phone($recipient_phone);
        
        if (!$customer_name_validation['valid']) {
            $msg = display_alert("Customer name validation failed: " . $customer_name_validation['message'], "danger");
        } elseif (!$customer_email_validation['valid']) {
            $msg = display_alert("Customer email validation failed: " . $customer_email_validation['message'], "danger");
        } elseif (!$customer_phone_validation['valid']) {
            $msg = display_alert("Customer phone validation failed: " . $customer_phone_validation['message'], "danger");
        } elseif (!$recipient_name_validation['valid']) {
            $msg = display_alert("Recipient name validation failed: " . $recipient_name_validation['message'], "danger");
        } elseif (!$recipient_phone_validation['valid']) {
            $msg = display_alert("Recipient phone validation failed: " . $recipient_phone_validation['message'], "danger");
        } else {
            // Use the client-calculated price shown to the user
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
                    // Auto-generate password for new customer
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
            $email_result = ['success' => false, 'error' => 'Not sent'];
            if ($is_new_customer) {
                $email_result = send_shipment_notification_new($customer_email, $customer_name, $temp_password, $tracking_number);
            } else {
                $email_result = send_shipment_notification_existing($customer_email, $customer_name, $tracking_number);
            }

            // 4. Create Shipment linked to AGENT
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

            // 5. Create Initial Status History
            $stmt = $pdo->prepare("INSERT INTO shipment_status_history (shipment_id, status, remarks, changed_by_role, changed_by_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$shipment_id, 'Pending', 'Shipment Created by Agent', 'agent', $agent_id]);

            $pdo->commit();
            
            // Build success message
            $success_msg = "Shipment ($tracking_number) created successfully.";
            if (!$email_result['success']) {
                $success_msg .= " Warning: Failed to send notification email: " . $email_result['error'];
                $msg = display_alert($success_msg, "warning");
            } else {
                $msg = display_alert($success_msg, "success");
            }

            // Clear selections but keep msg
            $_POST = array();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = display_alert("Failed to create shipment: " . escape($e->getMessage()), "danger");
        }
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
        <?php 
        $role = 'agent';
        $active_page = 'create_shipment.php';
        require_once '../includes/sidebar.php'; 
        ?>


        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Create Shipment</h2>
                    <p class="text-muted mb-0 small">Generate new shipping waybills.</p>
                </div>
            </header>

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
                                <input type="text" id="customer_name" name="customer_name" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_name'] ?? '') ?>">
                                <span id="customer_name-error" class="field-error"></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" id="customer_email" name="customer_email" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_email'] ?? '') ?>">
                                <span id="customer_email-error" class="field-error"></span>
                                <div class="form-text small">An account will be created automatically if they are new.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="tel" id="customer_phone" name="customer_phone" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['customer_phone'] ?? '') ?>">
                                <span id="customer_phone-error" class="field-error"></span>
                            </div>
                        </div>

                        <!-- Recipient Details -->
                        <div class="col-md-6">
                            <h5 class="fw-bold text-muted mb-4 border-bottom pb-2"><i
                                    class="bi bi-person-check me-2"></i>Recipient Details</h5>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" id="recipient_name" name="recipient_name" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['recipient_name'] ?? '') ?>">
                                <span id="recipient_name-error" class="field-error"></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control neumorphic-input py-2"
                                    required value="<?= escape($_POST['recipient_phone'] ?? '') ?>">
                                <span id="recipient_phone-error" class="field-error"></span>
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
                                    <label class="form-label small fw-bold">Shipping Fee (PKR)</label>
                                    <div class="input-group neumorphic-inset p-0 overflow-hidden"
                                        style="border-radius: var(--border-radius-input);">
                                        <span
                                            class="input-group-text border-0 bg-transparent fw-bold text-muted">Rs.</span>
                                        <input type="text" name="price" id="price_display"
                                            class="form-control border-0 bg-transparent shadow-none" readonly
                                            placeholder="Auto-calculated"
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
    <script>
        const coords = {
            'Karachi': [24.86, 67.00], 'Lahore': [31.52, 74.35], 'Islamabad': [33.68, 73.04],
            'Rawalpindi': [33.56, 73.01], 'Faisalabad': [31.45, 73.13], 'Multan': [30.15, 71.52],
            'Peshawar': [34.01, 71.52], 'Quetta': [30.17, 66.97], 'Hyderabad': [25.39, 68.37],
            'Sialkot': [32.49, 74.52], 'Gujranwala': [32.18, 74.19], 'Bahawalpur': [29.35, 71.69]
        };

        function calculatePrice() {
            const originEl = document.querySelector('select[name="origin_city_id"] option:checked');
            const destEl = document.querySelector('select[name="destination_city_id"] option:checked');
            const weightEl = document.querySelector('input[name="weight"]');
            
            if(!originEl || !destEl || !weightEl) return;

            const origin = originEl.text.split(',')[0].trim();
            const dest = destEl.text.split(',')[0].trim();
            const weight = parseFloat(weightEl.value) || 0;
            
            if(!origin || !dest || weight <= 0) {
                document.getElementById('price_display').value = '';
                return;
            }

            let distance = 100;
            if(coords[origin] && coords[dest]) {
                const lat1 = coords[origin][0], lon1 = coords[origin][1];
                const lat2 = coords[dest][0], lon2 = coords[dest][1];
                const R = 6371;
                const dLat = (lat2-lat1) * Math.PI / 180;
                const dLon = (lon2-lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                distance = R * c;
            }

            const base = 150, rWeight = 80, rDist = 0.5;
            const total = base + (weight * rWeight) + (distance * rDist);
            document.getElementById('price_display').value = total.toFixed(2);
        }

        document.querySelectorAll('select[name="origin_city_id"], select[name="destination_city_id"], input[name="weight"]').forEach(el => {
            el.addEventListener('change', calculatePrice);
            el.addEventListener('keyup', calculatePrice);
            el.addEventListener('input', calculatePrice);
        });
        
        // REAL-TIME validation for agent shipment creation form
        function validateNameFieldAgent(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            
            if (!value) {
                errorEl.textContent = 'Name is required.';
                input.classList.add('is-invalid');
            } else if (!/^[a-zA-Z\s]+$/.test(value)) {
                errorEl.textContent = 'Name must contain only letters and spaces.';
                input.classList.add('is-invalid');
            } else if (value.length < 2) {
                errorEl.textContent = 'Name must be at least 2 characters long.';
                input.classList.add('is-invalid');
            } else {
                errorEl.textContent = '';
                input.classList.remove('is-invalid');
            }
        }
        
        function validateEmailFieldAgent(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!value) {
                errorEl.textContent = 'Email is required.';
                input.classList.add('is-invalid');
            } else if (!emailRegex.test(value)) {
                errorEl.textContent = 'Please provide a valid email address.';
                input.classList.add('is-invalid');
            } else {
                errorEl.textContent = '';
                input.classList.remove('is-invalid');
            }
        }
        
        function validatePhoneFieldAgent(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            
            if (!value) {
                errorEl.textContent = 'Phone number is required.';
                input.classList.add('is-invalid');
            } else if (!/^[0-9]+$/.test(value)) {
                errorEl.textContent = 'Phone number must contain only digits.';
                input.classList.add('is-invalid');
            } else if (value.length < 10 || value.length > 20) {
                errorEl.textContent = 'Phone number must be between 10 and 20 digits.';
                input.classList.add('is-invalid');
            } else {
                errorEl.textContent = '';
                input.classList.remove('is-invalid');
            }
        }
        
        // REAL-TIME validation on input
        document.getElementById('customer_name').addEventListener('input', function() {
            validateNameFieldAgent(this);
        });
        document.getElementById('customer_email').addEventListener('input', function() {
            validateEmailFieldAgent(this);
        });
        document.getElementById('customer_phone').addEventListener('input', function() {
            validatePhoneFieldAgent(this);
        });
        document.getElementById('recipient_name').addEventListener('input', function() {
            validateNameFieldAgent(this);
        });
        document.getElementById('recipient_phone').addEventListener('input', function() {
            validatePhoneFieldAgent(this);
        });
        
        // Also validate on blur
        ['customer_name', 'customer_email', 'customer_phone', 'recipient_name', 'recipient_phone'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('blur', function() {
                    if (id.includes('name')) validateNameFieldAgent(this);
                    else if (id.includes('email')) validateEmailFieldAgent(this);
                    else if (id.includes('phone')) validatePhoneFieldAgent(this);
                });
            }
        });
    </script>
</body>


</html>
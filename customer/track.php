<?php
// FILE: /consignxAnti/customer/track.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Note: We don't require middleware/auth here. Tracking is public if you have the ID.
// But we can check if they are logged in to show the correct navbar properties.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'customer';

$tracking_id = trim($_GET['id'] ?? '');
$shipment = null;
$history = [];
$error = '';

if (!empty($tracking_id)) {
    if (!is_valid_tracking_number($tracking_id)) {
        $error = "Invalid tracking number format.";
    } else {
        try {
            // Fetch Shipment Details
            $stmt = $pdo->prepare("
                SELECT s.*, 
                       orig.name as origin_city, dest.name as dest_city,
                       a.company_name as agent_name
                FROM shipments s
                LEFT JOIN cities orig ON s.origin_city_id = orig.id
                LEFT JOIN cities dest ON s.destination_city_id = dest.id
                LEFT JOIN agents a ON s.agent_id = a.id
                WHERE s.tracking_number = ?
            ");
            $stmt->execute([$tracking_id]);
            $shipment = $stmt->fetch();

            if ($shipment) {
                // Security check for logged in customers
                if ($is_logged_in && $user_role === 'customer' && $shipment['customer_id'] !== $_SESSION['user_id']) {
                    $error = "You don't have permission to view this shipment.";
                    $shipment = null;
                } else {
                    // Fetch History
                    $hStmt = $pdo->prepare("SELECT * FROM shipment_status_history WHERE shipment_id = ? ORDER BY created_at DESC");
                    $hStmt->execute([$shipment['id']]);
                    $history = $hStmt->fetchAll();
                }
            } else {
                $error = "Tracking number $tracking_id not found.";
            }

        } catch (PDOException $e) {
            $error = "An error occurred while tracking. Please try again later.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Shipment - ConsignX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">

    <style>
    .timeline {
        position: relative;
        padding-left: 3rem;
        list-style: none;
    }

    .timeline::before {
        content: "";
        position: absolute;
        left: 15px;
        top: 5px;
        bottom: 5px;
        width: 3px;
        background-color: var(--primary-color);
        opacity: 0.2;
        border-radius: 5px;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 2.5rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-item::before {
        content: "";
        position: absolute;
        left: -3rem;
        top: 5px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background-color: var(--primary-color);
        border: 3px solid var(--bg-color);
        box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.2);
        z-index: 1;
    }

    .timeline-item:not(:first-child)::before {
        background-color: var(--text-muted);
        box-shadow: none;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
            color: black !important;
            padding: 0 !important;
        }

        .neumorphic-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }

        .main-content {
            padding: 0 !important;
            margin: 0 !important;
        }

        .container {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
        }

        .timeline::before {
            display: none;
        }

        .timeline-item::before {
            border: 1px solid #000;
        }
    }
    </style>
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar if logged in, else simple nav -->
        <?php if ($is_logged_in): ?>
        <?php 
            $role = $user_role;
            $active_page = 'track.php';
            require_once '../includes/sidebar.php'; 
            ?>
        <?php endif; ?>

        <main class="main-content <?= $is_logged_in ? '' : 'ms-0 w-100 pt-3' ?>">
            <?php 
            $page_title = 'Track & Trace';
            require_once '../includes/top_header.php'; 
            ?>

            <div class="container pb-5">
                <!-- Tracking Search Bar (Already in top_header but keeping a focused one for landing tracking) -->
                <?php if (empty($tracking_id)): ?>
                <div class="row justify-content-center mb-5 no-print">
                    <div class="col-md-8">
                        <div class="neumorphic-card p-5 text-center">
                            <i class="bi bi-geo-alt fs-1 text-primary mb-3"></i>
                            <h3 class="fw-bold mb-4">Track Your Package</h3>
                            <form action="" method="GET" class="d-flex gap-2 mx-auto justify-content-center"
                                style="max-width: 500px;">
                                <input type="text" name="id"
                                    class="form-control neumorphic-input w-100 fs-5 text-center fw-bold"
                                    placeholder="e.g. C-XXXX-XXXX" required>
                                <button type="submit"
                                    class="btn neumorphic-btn btn-primary fw-bold px-4">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?= $error ? display_alert($error, 'danger') : '' ?>

                <?php if ($shipment): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-11">
                        <div class="d-flex justify-content-between align-items-end mb-4 px-2">
                            <div>
                                <span class="text-muted text-uppercase fw-bold smaller letter-spacing-1">Ref ID:
                                    #<?= escape($shipment['id']) ?></span>
                                <h2 class="fw-bold text-primary mb-0"><?= escape($shipment['tracking_number']) ?></h2>
                            </div>
                            <div class="no-print d-flex gap-2">
                                <button onclick="window.history.back()" class="btn neumorphic-btn fw-bold">
                                    <i class="bi bi-arrow-left text-primary me-2"></i> Back
                                </button>
                                <button onclick="window.print()" class="btn neumorphic-btn fw-bold">
                                    <i class="bi bi-printer text-primary me-2"></i> Print Receipt
                                </button>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="neumorphic-card p-4 h-100">
                                    <div
                                        class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                                        <h5 class="fw-bold mb-0 flex-grow-1"><i
                                                class="bi bi-box-seam me-2 text-primary"></i>Shipment Details</h5>
                                        <?php 
                                                $status_class = match($shipment['status']) {
                                                    'Pending' => 'status-pending',
                                                    'Delivered' => 'status-delivered',
                                                    'Cancelled' => 'bg-danger text-white',
                                                    'Returned' => 'bg-warning text-dark',
                                                    default => 'status-transit'
                                                };
                                            ?>
                                        <span
                                            class="badge-neumorphic <?= $status_class ?> px-4 py-2 fw-bold"><?= escape($shipment['status']) ?></span>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-sm-6">
                                            <div class="text-muted smaller fw-bold mb-1 uppercase text-uppercase">From
                                                (Origin)</div>
                                            <div class="fw-bold fs-5"><?= escape($shipment['origin_city']) ?></div>
                                        </div>
                                        <div class="col-sm-6 text-sm-end">
                                            <div class="text-muted smaller fw-bold mb-1 uppercase text-uppercase">To
                                                (Destination)</div>
                                            <div class="fw-bold fs-5"><?= escape($shipment['dest_city']) ?></div>
                                        </div>

                                        <div class="col-12 py-3 px-4">
                                            <div class="position-relative w-100 bg-secondary bg-opacity-10 rounded-pill"
                                                style="height: 8px; box-shadow: inset 2px 2px 5px rgba(0,0,0,0.1);">
                                                <?php 
                                                        $prog = match($shipment['status']) {
                                                            'Pending' => '5%',
                                                            'Picked Up' => '25%',
                                                            'In Transit' => '50%',
                                                            'Out For Delivery' => '75%',
                                                            'Delivered' => '100%',
                                                            'Cancelled' => '100%',
                                                            'Returned' => '100%',
                                                            default => '5%'
                                                        };
                                                    ?>
                                                <div class="bg-primary position-absolute rounded-pill h-100 transition"
                                                    style="width: <?= $prog ?>; box-shadow: 0 0 10px var(--primary-color);">
                                                </div>
                                                <div class="bg-white border border-primary border-2 rounded-circle position-absolute top-50 translate-middle shadow"
                                                    style="left: <?= $prog ?>; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-truck text-primary" style="font-size: 0.75rem;"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6 mt-4">
                                            <div class="text-muted smaller fw-bold mb-1 text-uppercase">Recipient
                                                Information</div>
                                            <div class="fw-bold"><?= escape($shipment['recipient_name']) ?></div>
                                            <div class="small fw-medium text-muted">
                                                <?= escape($shipment['recipient_phone']) ?></div>
                                        </div>
                                        <div class="col-sm-6 mt-4 text-sm-end">
                                            <div class="text-muted smaller fw-bold mb-1 text-uppercase">Delivery Address
                                            </div>
                                            <div class="fw-bold small">
                                                <?= nl2br(escape($shipment['recipient_address'])) ?></div>
                                        </div>

                                        <div class="col-sm-6 mt-4">
                                            <div class="text-muted smaller fw-bold mb-1 text-uppercase">Courier /
                                                Logistics</div>
                                            <div class="fw-bold text-primary"><i
                                                    class="bi bi-building me-1"></i><?= escape($shipment['agent_name'] ?? 'ConsignX HQ') ?>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 mt-4 text-sm-end">
                                            <div class="text-muted smaller fw-bold mb-1 text-uppercase">Booking Date
                                            </div>
                                            <div class="fw-bold">
                                                <?= date('M d, Y', strtotime($shipment['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="neumorphic-card p-4 h-100">
                                    <h5 class="fw-bold mb-4 pb-3 border-bottom border-secondary border-opacity-10"><i
                                            class="bi bi-geo me-2 text-primary"></i>Status Timeline</h5>
                                    <div class="ps-2">
                                        <ul class="timeline">
                                            <?php foreach ($history as $item): ?>
                                            <li class="timeline-item">
                                                <div class="fw-bold text-primary mb-0"><?= escape($item['status']) ?>
                                                </div>
                                                <div class="smaller text-muted fw-bold mb-2">
                                                    <?= date('M d, Y - h:i A', strtotime($item['created_at'])) ?></div>

                                                <?php if (!empty($item['location'])): ?>
                                                <div class="small fw-bold"><i
                                                        class="bi bi-geo-alt me-1 text-muted"></i><?= escape($item['location']) ?>
                                                </div>
                                                <?php endif; ?>

                                                <?php if (!empty($item['remarks'])): ?>
                                                <div
                                                    class="small text-muted fst-italic py-2 px-3 bg-primary bg-opacity-5 rounded mt-2 border-start border-3 border-primary">
                                                    "<?= escape($item['remarks']) ?>"
                                                </div>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>
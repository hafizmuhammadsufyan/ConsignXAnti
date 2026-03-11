<?php
// FILE: /consignxAnti/customer/track.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Note: We don't require middleware/auth here. Tracking is public if you have the ID.
// But we can check if they are logged in to show the correct navbar.
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';

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
                // Security check for logged in customers: if logged in, they can only track THEIR own shipments.
                // However, standard tracking is public. The prompt requested: "Enhancing customer tracking security" 
                // "Customer Tracking Security: Implement access control so customers can only track their own shipments."
                if ($is_logged_in && $shipment['customer_id'] !== $_SESSION['user_id']) {
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
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    
    <style>
        /* Custom Timeline CSS */
        .timeline {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 0;
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
        /* Top item gets solid fill, others slightly faded based on neumorph theme */
        .timeline-item:not(:first-child)::before {
            background-color: var(--text-muted);
            box-shadow: none;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .glass-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body class="glass-bg">

<!-- Navbar depending on auth state -->
<nav class="navbar navbar-expand-lg border-bottom border-light border-opacity-10 mb-5 py-3 shadow-sm bg-transparent no-print">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary fs-4" href="<?= $is_logged_in ? 'dashboard.php' : '../index.php' ?>">ConsignX</a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navTrack">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navTrack">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-medium <?= $is_logged_in ? '' : 'd-none' ?>" href="dashboard.php">My Shipments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium active" href="track.php">Track & Trace</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-4">
                <div class="theme-switch-wrapper">
                    <span class="text-muted small fw-bold me-2">Dark Mode</span>
                    <label class="theme-switch">
                        <input type="checkbox">
                        <span class="slider round"></span>
                    </label>
                </div>
                <?php if($is_logged_in): ?>
                    <div class="dropdown">
                        <a class="btn glass-btn dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= escape($user_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3 p-2 rounded-3">
                            <li><a class="dropdown-item fw-bold text-danger" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn glass-btn text-primary fw-bold">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <!-- Tracking Input Bar -->
    <div class="row justify-content-center mb-5 no-print">
        <div class="col-md-8">
            <div class="glass-card p-4 text-center">
                <h4 class="fw-bold mb-4">Track Your Shipment</h4>
                <form action="" method="GET" class="d-flex gap-2 mx-auto justify-content-center" style="max-width: 500px;">
                    <input type="text" name="id" class="form-control glass-input w-100 fs-5 text-center fw-bold" placeholder="e.g. C-XXXX-XXXX" value="<?= escape($tracking_id) ?>" required>
                    <button type="submit" class="btn glass-btn btn-primary fw-bold px-4 shadow-none">Search</button>
                </form>
            </div>
        </div>
    </div>

    <?= $error ? display_alert($error, 'danger') : '' ?>

    <?php if ($shipment): ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-end mb-4 px-2">
                    <div>
                        <span class="text-muted text-uppercase fw-bold small tracking-wider">Tracking Number</span>
                        <h2 class="fw-bold text-primary mb-0"><?= escape($shipment['tracking_number']) ?></h2>
                    </div>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn glass-btn fw-bold">
                            <i class="bi bi-printer text-primary me-2"></i> Print Receipt
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Shipment Details Panel -->
                    <div class="col-md-7">
                        <div class="glass-card p-4 h-100">
                            <div class="d-flex align-items-center mb-4">
                                <h5 class="fw-bold mb-0 flex-grow-1"><i class="bi bi-info-square me-2 text-primary"></i>Shipment Info</h5>
                                <?php 
                                    $bg = match($shipment['status']) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Picked Up', 'In Transit', 'Out For Delivery' => 'bg-info text-white',
                                        'Delivered' => 'bg-success text-white',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge rounded-pill <?= $bg ?> px-4 py-2 fs-6 shadow-sm border border-light border-opacity-25"><?= escape($shipment['status']) ?></span>
                            </div>

                            <div class="row g-4 pt-3 border-top border-secondary border-opacity-10 mt-2">
                                <div class="col-sm-6">
                                    <div class="text-muted small fw-bold mb-1">Origin</div>
                                    <div class="fw-medium fs-5"><?= escape($shipment['origin_city']) ?></div>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <div class="text-muted small fw-bold mb-1">Destination</div>
                                    <div class="fw-medium fs-5"><?= escape($shipment['dest_city']) ?></div>
                                </div>
                                
                                <div class="col-12 py-3">
                                    <div class="position-relative w-100 bg-secondary bg-opacity-25 rounded-pill" style="height: 6px;">
                                        <!-- Visual Progress Bar based on status -->
                                        <?php 
                                            $prog = match($shipment['status']) {
                                                'Pending' => '0%',
                                                'Picked Up' => '25%',
                                                'In Transit' => '50%',
                                                'Out For Delivery' => '75%',
                                                'Delivered' => '100%',
                                                default => '0%'
                                            };
                                        ?>
                                        <div class="bg-primary position-absolute rounded-pill h-100 transition" style="width: <?= $prog ?>; transition: width 1s ease-in-out;"></div>
                                        <i class="bi bg-primary bg-opacity-10 text-primary rounded-circle p-1 position-absolute top-50 translate-middle custom-tooltip bi-truck fs-5" style="left: <?= $prog ?>; width: 35px; height: 35px; line-height: 25px; text-align: center;"></i>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3 text-muted small fw-bold" style="font-size: 0.7rem;">
                                        <span>Pending</span>
                                        <span>In Transit</span>
                                        <span>Delivered</span>
                                    </div>
                                </div>

                                <div class="col-sm-6 mt-4">
                                    <div class="text-muted small fw-bold mb-1">Recipient Name</div>
                                    <div class="fw-medium"><?= escape($shipment['recipient_name']) ?></div>
                                </div>
                                <div class="col-sm-6 mt-4 text-sm-end">
                                    <div class="text-muted small fw-bold mb-1">Delivery Address</div>
                                    <div class="fw-medium"><?= nl2br(escape($shipment['recipient_address'])) ?></div>
                                </div>
                                
                                <div class="col-sm-6 mt-4">
                                    <div class="text-muted small fw-bold mb-1">Courier</div>
                                    <div class="fw-medium"><i class="bi bi-building me-1"></i><?= escape($shipment['agent_name'] ?? 'ConsignX Direct') ?></div>
                                </div>
                                <div class="col-sm-6 mt-4 text-sm-end">
                                    <div class="text-muted small fw-bold mb-1">Creation Date</div>
                                    <div class="fw-medium"><?= date('M d, Y h:i A', strtotime($shipment['created_at'])) ?></div>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Panel -->
                    <div class="col-md-5">
                        <div class="glass-card p-4 h-100">
                            <h5 class="fw-bold mb-4"><i class="bi bi-clock-history me-2 text-primary"></i>Tracking Status</h5>
                            <div class="ps-2">
                                <ul class="timeline mt-4">
                                    <?php foreach ($history as $index => $item): ?>
                                        <li class="timeline-item">
                                            <div class="fw-bold text-primary mb-1"><?= escape($item['status']) ?></div>
                                            <small class="text-muted d-block fw-bold mb-2"><?= date('M d, Y - h:i A', strtotime($item['created_at'])) ?></small>
                                            
                                            <?php if (!empty($item['location'])): ?>
                                                <div class="small fw-medium mb-1"><i class="bi bi-geo-alt me-1 text-muted"></i><?= escape($item['location']) ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['remarks'])): ?>
                                                <div class="small text-muted fst-italic py-1 px-3 bg-opacity-10 bg-secondary rounded mt-2 border-start border-3 border-secondary">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>

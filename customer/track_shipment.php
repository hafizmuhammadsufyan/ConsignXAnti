<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';

// Allow both logged-in customers and guest users
$is_guest = !is_logged_in();
$is_customer = is_logged_in() && current_user_role() === 'customer';

// If logged-in but not a customer, redirect
if (is_logged_in() && !$is_customer) {
    header('Location: ../auth/login.php');
    exit;
}

$customer_id = current_user_id();
$tracking_number = trim($_REQUEST['tracking_number'] ?? '');
$msg = '';
$ship = null;
$history = [];

// View settings
if ($is_customer) {
    $role = 'customer';
    $active_page = 'dashboard.php';
}
$page_title = 'Track Shipment';

// Error Resilience: Attempt data fetch but don't die yet
if ($tracking_number) {
    // Validate tracking number format
    if (!is_valid_tracking_number($tracking_number)) {
        $msg = display_alert("Invalid tracking number format. Use: C-XXXX-XXXX (e.g., C-A1B2-C3D4)", "danger");
    } else {
        try {
            // 1. Fetch Shipment main details
            if ($is_guest) {
                // Guest can view any shipment by tracking number
                $stmt = $pdo->prepare("
                    SELECT s.*, 
                           orig.name as origin_city, dest.name as dest_city,
                           a.company_name as agent_name, a.email as agent_email, a.phone as agent_phone,
                           c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                           (SELECT location FROM shipment_status_history WHERE shipment_id = s.id ORDER BY id DESC LIMIT 1) as current_location
                    FROM shipments s
                    LEFT JOIN cities orig ON s.origin_city_id = orig.id
                    LEFT JOIN cities dest ON s.destination_city_id = dest.id
                    LEFT JOIN agents a ON s.agent_id = a.id
                    LEFT JOIN customers c ON s.customer_id = c.id
                    WHERE s.tracking_number = ?
                ");
                $stmt->execute([$tracking_number]);
            } else {
                // Logged-in customer can only view their own shipments
                $stmt = $pdo->prepare("
                    SELECT s.*, 
                           orig.name as origin_city, dest.name as dest_city,
                           a.company_name as agent_name, a.email as agent_email, a.phone as agent_phone,
                           c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                           (SELECT location FROM shipment_status_history WHERE shipment_id = s.id ORDER BY id DESC LIMIT 1) as current_location
                    FROM shipments s
                    LEFT JOIN cities orig ON s.origin_city_id = orig.id
                    LEFT JOIN cities dest ON s.destination_city_id = dest.id
                    LEFT JOIN agents a ON s.agent_id = a.id
                    LEFT JOIN customers c ON s.customer_id = c.id
                    WHERE s.tracking_number = ? AND s.customer_id = ?
                ");
                $stmt->execute([$tracking_number, $customer_id]);
            }
            $ship = $stmt->fetch();

            if ($ship) {
                // 2. Fetch Status History
                $stmtH = $pdo->prepare("SELECT * FROM shipment_status_history WHERE shipment_id = ? ORDER BY created_at DESC");
                $stmtH->execute([$ship['id']]);
                $history = $stmtH->fetchAll();

                // 3. Fallback: If no history exists, create a virtual 'Pending' entry
                if (empty($history)) {
                    $history = [[
                        'status' => $ship['status'] ?: 'Pending',
                        'location' => $ship['origin_city'] ?: 'Processing Center',
                        'remarks' => 'Shipment information recorded and processing.',
                        'created_at' => $ship['created_at']
                    ]];
                }
            } else {
                $msg = display_alert("Shipment not found.", "danger");
            }

        } catch (PDOException $e) {
            $msg = display_alert("System error tracking shipment details.", "danger");
        }
    }
} else if (!$tracking_number) {
    // No tracking number provided - show helpful message instead of error
    if ($is_guest) {
        $msg = display_alert("Please enter a tracking number to track your shipment.", "info");
    }
}

// Progress mapping
$progress = 0;
if ($ship) {
    $progress = match ($ship['status']) {
        'Pending' => 5,
        'Picked Up' => 25,
        'In Transit' => 50,
        'Out For Delivery' => 85,
        'Delivered' => 100,
        default => 0
    };
    
    $status_class = match ($ship['status']) {
        'Pending' => 'status-pending',
        'Delivered' => 'status-delivered',
        'Cancelled' => 'status-cancelled',
        'Returned' => 'status-returned',
        'Picked Up' => 'status-picked-up',
        'Out For Delivery' => 'status-out-delivery',
        default => 'status-transit'
    };
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
</head>
<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Sidebar Navigation - Only for logged-in customers -->
        <?php if ($is_customer): ?>
            <?php require_once '../includes/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Main Content Area -->
        <main class="main-content <?= $is_guest ? 'w-100' : '' ?>">
            <!-- Top Header - Only for logged-in customers -->
            <?php if ($is_customer): ?>
                <?php require_once '../includes/top_header.php'; ?>
            <?php else: ?>
                <!-- Guest Header with back to home -->
                <div class="p-4 border-bottom border-light" style="background: var(--bg1)">
                    <div class="container-fluid d-flex justify-content-between align-items-center">
                        <a href="../index.php" class="text-decoration-none fw-bold" style="color: var(--a)">
                            <i class="bi bi-arrow-left me-2"></i>Back to Home
                        </a>
                        <h5 class="mb-0" style="color: var(--t1)">Track Shipment</h5>
                        <div style="width: 40px"></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container-fluid">
                <!-- Back Button ALWAYS VISIBLE -->
                <div class="d-flex align-items-center mb-4 mt-4">
                    <a href="<?= $is_guest ? '../index.php' : 'javascript:history.back()' ?>" class="btn-back me-3">
                        <i class="bi bi-arrow-left me-2"></i> <?= $is_guest ? 'Home' : 'Back' ?>
                    </a>
                </div>

                <!-- Search Form -->
                <div class="row mb-4">
                    <div class="col-lg-6 mx-auto">
                        <div class="search-card neumorphic-card p-4">
                            <h5 class="text-center mb-3">Track Your Shipment</h5>
                            <form class="d-flex gap-2" method="GET" action="">
                                <input type="text" name="tracking_number" class="form-control neumorphic-input" placeholder="Enter Tracking Number" value="<?= escape($tracking_number) ?>" required>
                                <button type="submit" class="btn btn-primary neumorphic-btn">
                                    <i class="bi bi-search me-2"></i>Track
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <?= $msg ?>

                <?php if ($ship): ?>
                <div class="row">
                    <div class="col-lg-10 mx-auto">
                        <!-- Logistics Information Card (Enhanced) -->
                        <div class="tracking-info-card alert-card shadow-sm mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="fw-bold mb-1">Shipment Journey</h5>
                                    <p class="text-muted smaller mb-0">Tracking Number: <span class="text-primary fw-bold"><?= escape($ship['tracking_number']) ?></span></p>
                                </div>
                                <div class="text-end">
                                    <span class="badge-neumorphic <?= $status_class ?> px-3 py-2 fw-bold"><?= $ship['status'] ?></span>
                                </div>
                            </div>

                            <div class="row g-4 align-items-center mb-4">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
                                            <i class="bi bi-geo-alt-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="text-muted smaller fw-bold text-uppercase">Origin</div>
                                            <div class="fw-bold fs-5"><?= escape($ship['origin_city']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center d-none d-md-block">
                                    <i class="bi bi-arrow-right fs-3 text-muted opacity-50"></i>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <div class="d-flex align-items-center justify-content-md-end">
                                        <div class="text-md-end me-3">
                                            <div class="text-muted smaller fw-bold text-uppercase">Destination</div>
                                            <div class="fw-bold fs-5"><?= escape($ship['dest_city']) ?></div>
                                        </div>
                                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                                            <i class="bi bi-flag-fill fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Progress Bar -->
                            <div class="progress-track-container mx-0 px-2 mt-5">
                                <div class="progress-track-bar" style="width: <?= $progress ?>%;">
                                    <div class="progress-truck-icon">
                                        <i class="bi bi-truck-flatbed fs-4"></i>
                                    </div>
                                </div>
                                <div class="progress-track-point point-start <?= $progress >= 5 ? 'active' : '' ?>">
                                    <i class="bi bi-building-fill-check"></i>
                                    <span class="progress-point-label">Pending</span>
                                </div>
                                <div class="progress-track-point point-picked <?= $progress >= 25 ? 'active' : '' ?>">
                                    <span class="progress-point-label">Picked Up</span>
                                </div>
                                <div class="progress-track-point point-transit <?= $progress >= 50 ? 'active' : '' ?>">
                                    <span class="progress-point-label">In Transit</span>
                                </div>
                                <div class="progress-track-point point-delivery <?= $progress >= 85 ? 'active' : '' ?>">
                                    <span class="progress-point-label">Out Delivery</span>
                                </div>
                                <div class="progress-track-point point-end <?= $progress >= 100 ? 'active' : '' ?>">
                                    <i class="bi bi-geo-fill"></i>
                                    <span class="progress-point-label">Delivered</span>
                                </div>
                            </div>
                        </div>

                        <!-- Shipment Alerts & ETA Card -->
                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="tracking-info-card alert-card <?= $ship['status'] === 'Delivered' ? 'delivered' : '' ?> h-100 mb-0">
                                    <h6 class="smaller fw-bold text-muted text-uppercase mb-3 letter-spacing-1">Estimated Delivery</h6>
                                    <div class="eta-highlight mb-2">
                                        <?php 
                                            // Simple ETA logic: Created + 3 days
                                            $eta = date('M d, Y', strtotime($ship['created_at'] . ' + 3 days'));
                                            echo ($ship['status'] === 'Delivered') ? 'Completed' : $eta;
                                        ?>
                                    </div>
                                    <p class="text-muted small mb-4">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php if($ship['status'] === 'Delivered'): ?>
                                            Package was successfully delivered. Thank you for using ConsignX!
                                        <?php elseif($ship['status'] === 'Pending'): ?>
                                            Your shipment has been registered and is waiting for courier pickup.
                                        <?php elseif($ship['status'] === 'Out For Delivery'): ?>
                                            Your package is with our delivery partner and will reach you today!
                                        <?php else: ?>
                                            The shipment is currently on schedule and moving towards the destination.
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="courier-info-box p-3 rounded-3 d-flex align-items-center">
                                        <div class="courier-icon-wrap p-2 rounded shadow-sm me-3">
                                            <i class="bi bi-person-badge text-primary fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="smaller text-muted fw-bold">Assigned Courier</div>
                                            <div class="fw-bold small mb-1"><?= escape($ship['agent_name'] ?? 'ConsignX Logistics (HQ)') ?></div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <span class="smaller text-muted"><i class="bi bi-telephone me-1"></i> <?= escape($ship['agent_phone'] ?? 'N/A') ?></span>
                                                <span class="smaller text-muted"><i class="bi bi-envelope me-1"></i> <?= escape($ship['agent_email'] ?? 'N/A') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="tracking-info-card h-100 d-flex flex-column justify-content-center align-items-center text-center mb-0">
                                    <div class="bg-primary bg-opacity-10 text-primary p-4 rounded-circle mb-3">
                                        <i class="bi bi-file-earmark-pdf fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold mb-2">Shipment Report</h6>
                                    <p class="text-muted smaller mb-3 px-3">Download the professional electronic receipt and status report.</p>
                                    <button id="exportPDF" class="btn btn-track w-auto px-4">
                                        <i class="bi bi-download me-2"></i> Export PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Shipment Metrics Row (Restored for original density) -->
                        <div class="row g-4 mt-2">
                            <div class="col-6 col-md-3">
                                <div class="tracking-info-card text-center py-3 mb-0 h-100">
                                    <div class="text-muted smaller fw-bold text-uppercase mb-1">Weight</div>
                                    <div class="fw-bold text-primary"><?= escape($ship['weight']) ?> kg</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="tracking-info-card text-center py-3 mb-0 h-100">
                                    <div class="text-muted smaller fw-bold text-uppercase mb-1">Price</div>
                                    <div class="fw-bold text-primary"><?= format_currency($ship['price']) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="tracking-info-card text-center py-3 mb-0 h-100">
                                    <div class="text-muted smaller fw-bold text-uppercase mb-1">Service</div>
                                    <div class="fw-bold text-primary">Express</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="tracking-info-card text-center py-3 mb-0 h-100">
                                    <div class="text-muted smaller fw-bold text-uppercase mb-1">Created</div>
                                    <div class="fw-bold text-primary"><?= date('M d, Y', strtotime($ship['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- jsPDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const exportBtn = document.getElementById('exportPDF');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    // 1. Professional Header Area
                    doc.setFillColor(44, 62, 80); // Deep Navy
                    doc.rect(0, 0, 210, 50, 'F');
                    
                    doc.setFontSize(32);
                    doc.setTextColor(255, 255, 255);
                    doc.text("CONSIGNX", 14, 25);
                    
                    doc.setFontSize(10);
                    doc.text("PREMIUM ELECTRONIC SHIPMENT REPORT", 14, 33);
                    doc.text("Official Tracking & Delivery Documentation", 14, 38);
                    
                    doc.setFontSize(11);
                    doc.text("Tracking ID:", 140, 20);
                    doc.setFont(undefined, 'bold');
                    doc.text("<?= $ship['tracking_number'] ?>", 165, 20);
                    doc.setFont(undefined, 'normal');
                    doc.text("Report Date:", 140, 27);
                    doc.text(new Date().toLocaleDateString(), 165, 27);
                    doc.text("Current Status:", 140, 34);
                    doc.text("<?= $ship['status'] ?>", 165, 34);

                    // 2. Logistics Details Grid
                    doc.setTextColor(44, 62, 80);
                    doc.setFontSize(16);
                    doc.setFont(undefined, 'bold');
                    doc.text("Shipment Overview", 14, 65);
                    
                    const overviewData = [
                        ["Customer Name", "<?= escape($ship['customer_name']) ?>", "Weight (kg)", "<?= $ship['weight'] ?>"],
                        ["Origin City", "<?= escape($ship['origin_city']) ?>", "Total Value", "<?= format_currency($ship['price']) ?>"],
                        ["Destination", "<?= escape($ship['dest_city']) ?>", "Service Level", "Premium Express"],
                        ["Courier Partner", "<?= escape($ship['agent_name'] ?? 'ConsignX HQ') ?>", "Created At", "<?= date('M d, Y', strtotime($ship['created_at'])) ?>"]
                    ];
                    
                    doc.autoTable({
                        startY: 70,
                        body: overviewData,
                        theme: 'striped',
                        styles: { fontSize: 10, cellPadding: 5 },
                        columnStyles: { 
                            0: { fontStyle: 'bold', fillColor: [245, 245, 245], width: 40 }, 
                            2: { fontStyle: 'bold', fillColor: [245, 245, 245], width: 40 } 
                        }
                    });

                    // 3. Status & ETA Highlights (Phase 11 Special)
                    doc.setFontSize(16);
                    doc.text("Logistics Summary & ETA", 14, doc.lastAutoTable.finalY + 15);
                    
                    const etaData = [
                        ["Current Status", "<?= escape($ship['status']) ?>", "Last Update", "<?= date('M d, Y H:i', strtotime($ship['created_at'])) ?>"],
                        ["Service Partner", "<?= escape($ship['agent_name'] ?? 'ConsignX HQ') ?>", "Est. Arrival", "<?= $ship['status'] == 'Delivered' ? 'Delivered' : date('M d, Y', strtotime('+2 days')) ?>"]
                    ];
                    
                    doc.autoTable({
                        startY: doc.lastAutoTable.finalY + 20,
                        body: etaData,
                        theme: 'grid',
                        styles: { fontSize: 9, cellPadding: 4 },
                        didParseCell: function(data) {
                            if (data.section === 'body' && data.column.index === 1 && data.cell.raw === 'Delivered') {
                                data.cell.styles.textColor = [56, 161, 105];
                                data.cell.styles.fontStyle = 'bold';
                            }
                        }
                    });

                    // 3. Final Summary & Verification
                    const finalY = doc.lastAutoTable.finalY;
                    doc.setFontSize(10);
                    doc.setTextColor(100, 100, 100);
                    doc.text("Official Verification Summary", 14, finalY + 15);
                    doc.setFontSize(9);
                    doc.text("This document confirms the current logistics status for Tracking ID <?= $ship['tracking_number'] ?>.", 14, finalY + 22);
                    doc.text("All logistics milestones are verified via the ConsignX Network integrity system.", 14, finalY + 27);
                    
                    // Final Brand Footer
                    doc.setFontSize(8);
                    doc.text("Generated via ConsignX Customer Portal on " + new Date().toLocaleString(), 105, 285, { align: 'center' });
                    doc.text("ConsignX Logistics - Verified Global Transit Documentation", 105, 290, { align: 'center' });
                    
                    doc.save("ConsignX_Logistics_<?= $ship['tracking_number'] ?>.pdf");
                });
            }
        });
    </script>
</body>
</html>

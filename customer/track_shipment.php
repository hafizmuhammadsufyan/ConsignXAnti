<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Public tracking - no login required
// Only check role if user is logged in; otherwise allow public tracking
$is_public = empty($_SESSION['user_id']);

$customer_id = $is_public ? null : current_user_id();
$tracking_number = trim($_REQUEST['tracking_number'] ?? '');
$msg = '';
$ship = null;
$history = [];

// View settings
$role = $_SESSION['user_role'] ?? 'public';
$active_page = $is_public ? null : 'dashboard.php';
$page_title = 'Track Shipment';

// Error Resilience: Attempt data fetch but don't die yet
if (!empty($tracking_number)) {
    try {
        // 1. Fetch Shipment main details - public access allows any shipment
        if ($is_public) {
            // Public: fetch by tracking number only
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
            // Logged-in: restrict to own shipments
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
            $msg = display_alert("Shipment not found or access denied.", "danger");
        }

    } catch (PDOException $e) {
        $msg = display_alert("System error tracking shipment details.", "danger");
    }
} else {
    $msg = display_alert("Invalid tracking request.", "warning");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
    <style>
        :root {
            --bg0: #05080e;
            --bg1: #080d18;
            --bg2: #0c1525;
            --bg3: #101e34;
            --card: #0f1b2e;
            --muted: #52525b;
            --ln: rgba(255, 255, 255, .07);
            --lnh: rgba(255, 255, 255, .13);
            --t1: #edf0ff;
            --t2: #a1a7b1;
            --bdr: rgba(255, 255, 255, 0.06);
            --t3: #7695be;
            --a: #3b7cfd;
            --am: #6366f1;
            --aw: #f59e0b;
            --at: #14b8a6;
            --fd: 'Syne', sans-serif;
            --fb: 'DM Sans', sans-serif;
            --head: 'Space Grotesk', sans-serif;
            --expo: cubic-bezier(.16, 1, .3, 1);
        }

        /* Light Theme Overrides */
        @media (prefers-color-scheme: light) {
            :root {
                --bg0: #f8f9fa;
                --bg1: #ffffff;
                --bg2: #f0f3f8;
                --bg3: #e8ecf2;
                --card: #ffffff;
                --t1: #1a202c;
                --t2: #4a5568;
                --t3: #718096;
                --ln: rgba(0, 0, 0, 0.08);
                --lnh: rgba(0, 0, 0, 0.15);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            background: var(--bg0);
            color: var(--t1);
            font-family: var(--fb);
            font-size: 16px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Light Theme - Logged In User Fixes */
        @media (prefers-color-scheme: light) {
            .tracking-info-card,
            .neumorphic-card,
            .alert-card {
                background: #ffffff !important;
                color: #1a202c !important;
                border-color: #e2e8f0 !important;
            }

            .text-muted {
                color: #4a5568 !important;
            }

            .smaller {
                color: #4a5568 !important;
            }

            .fw-bold,
            h2, h3, h4, h5, h6 {
                color: #1a202c !important;
            }

            .text-primary {
                color: #3b7cfd !important;
            }

            .form-label {
                color: #1a202c !important;
            }

            .form-control,
            .neumorphic-input,
            input,
            textarea,
            select {
                background-color: #ffffff !important;
                color: #1a202c !important;
                border-color: #e2e8f0 !important;
            }

            .form-control::placeholder,
            input::placeholder {
                color: #9ca3af !important;
            }

            .btn-primary,
            .neumorphic-btn {
                background-color: #3b7cfd !important;
                color: #ffffff !important;
                border-color: #3b7cfd !important;
            }

            .btn-track {
                background-color: #3b7cfd !important;
                color: #ffffff !important;
                border: none !important;
            }

            .badge-neumorphic {
                color: #ffffff !important;
            }

            .bg-opacity-10 {
                opacity: 0.1 !important;
            }

            /* Courier info box */
            .courier-info-box {
                background: #f8f9fa !important;
                border: 1px solid #e2e8f0 !important;
            }

            /* Progress bar styling for light theme */
            .progress-track-bar {
                background: linear-gradient(90deg, #3b7cfd 0%, #6366f1 100%) !important;
            }

            .progress-track-point.active {
                color: #3b7cfd !important;
            }

            /* Status badges background */
            .status-delivered {
                background: rgba(34, 197, 94, 0.2) !important;
                color: #22c55e !important;
            }

            .status-pending {
                background: rgba(245, 158, 11, 0.2) !important;
                color: #f59e0b !important;
            }

            .status-cancelled {
                background: rgba(220, 53, 69, 0.2) !important;
                color: #dc3545 !important;
            }

            .status-in-transit,
            .status-picked-up,
            .status-out-delivery {
                background: rgba(59, 124, 253, 0.2) !important;
                color: #3b7cfd !important;
            }
        }
    </style>
    </style>
</head>
<body class="neumorphic-bg">

    <?php if ($is_public): ?>
        <!-- PUBLIC LAYOUT (No sidebar, no header) -->
        <div class="min-vh-100 d-flex flex-column">
            <!-- Navigation -->
            <nav style="padding: 20px 5%; border-bottom: 1px solid rgba(255,255,255,.07);">
                <div class="container-fluid">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="../index.php" style="font-family: var(--fd); font-size: 20px; font-weight: 800; color: var(--t1); text-decoration: none;">
                            <span style="color: var(--a);">Consign</span>X
                        </a>
                        <a href="../index.php" style="color: var(--t2); text-decoration: none; font-size: 14px;">← Back to Home</a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main style="flex: 1; padding: 60px 5%;">
                <div class="container" style="max-width: 900px;">
                    <div style="text-align: center; margin-bottom: 48px;">
                        <h1 style="font-family: var(--fd); font-size: clamp(32px, 5vw, 48px); font-weight: 800; color: var(--t1); margin-bottom: 12px;">
                            Track Your Shipment
                        </h1>
                        <p style="color: var(--t2); font-size: 16px; max-width: 500px; margin: 0 auto;">
                            Enter your tracking number to see real-time delivery status and location.
                        </p>
                    </div>

                    <!-- Search Form -->
                    <div style="background: var(--card); border: 1px solid var(--ln); border-radius: 16px; padding: 32px; margin-bottom: 40px;">
                        <form method="GET" action="" style="display: flex; gap: 12px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label style="display: block; font-size: 13.5px; font-weight: 600; color: var(--t1); margin-bottom: 8px;">
                                    Tracking Number
                                </label>
                                <input type="text" name="tracking_number" 
                                    placeholder="e.g., C-ABCD-EFGH" 
                                    value="<?= escape($tracking_number) ?>"
                                    pattern="^C-[A-Z0-9]{4}-[A-Z0-9]{4}$"
                                    title="Tracking number format: C-XXXX-XXXX"
                                    required
                                    style="width: 100%; padding: 11px 16px; background: var(--bg2); border: 1px solid var(--ln); border-radius: 10px; color: var(--t1); font-size: 14px; font-family: var(--fb); box-sizing: border-box;">
                            </div>
                            <button type="submit" 
                                style="padding: 11px 28px; background: var(--a); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all .25s;">
                                <i class="bi bi-search me-2"></i>Track
                            </button>
                        </form>
                    </div>

                    <?= $msg ?>

                    <?php if (!$ship && !empty($tracking_number)): ?>
                        <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 24px; text-align: center;">
                            <i class="bi bi-exclamation-circle" style="font-size: 28px; color: #dc3545; margin-bottom: 12px; display: block;"></i>
                            <h4 style="color: var(--t1); margin-bottom: 8px;">Shipment Not Found</h4>
                            <p style="color: var(--t2); margin: 0;">No shipment found with this tracking ID. Please check and try again.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($ship): ?>
                        <!-- Shipment Details Card -->
                        <div style="background: var(--card); border: 1px solid var(--ln); border-radius: 16px; padding: 32px; margin-bottom: 32px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
                                <div>
                                    <h2 style="color: var(--t1); font-weight: 700; font-size: 24px; margin-bottom: 8px;">Shipment Details</h2>
                                    <p style="color: var(--t2); font-size: 14px; margin-bottom: 4px;">Tracking Number: <span style="color: var(--a); font-weight: 600; font-size: 15px;"><?= escape($ship['tracking_number']) ?></span></p>
                                    <p style="color: var(--t2); font-size: 13px; margin: 0;">Created: <?= date('M d, Y g:i A', strtotime($ship['created_at'])) ?></p>
                                </div>
                                <span style="background: <?php 
                                    echo match($ship['status']) {
                                        'Delivered' => 'rgba(34, 197, 94, 0.2)',
                                        'Pending' => 'rgba(245, 158, 11, 0.2)',
                                        'Cancelled' => 'rgba(220, 53, 69, 0.2)',
                                        default => 'rgba(59, 124, 253, 0.2)'
                                    };
                                ?>; color: <?php
                                    echo match($ship['status']) {
                                        'Delivered' => '#22c55e',
                                        'Pending' => '#f59e0b',
                                        'Cancelled' => '#dc3545',
                                        default => 'var(--a)'
                                    };
                                ?>; border-radius: 10px; padding: 12px 24px; font-weight: 700; font-size: 14px; display: inline-block; border: 1px solid currentColor; opacity: 0.6;"><?= escape($ship['status']) ?></span>
                            </div>

                            <!-- From/To with improved styling -->
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 24px; align-items: center; margin-bottom: 40px; background: var(--bg2); padding: 24px; border-radius: 12px;">
                                <div>
                                    <div style="color: var(--t3); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Origin</div>
                                    <div style="color: var(--t1); font-size: 20px; font-weight: 800;">📍 <?= escape($ship['origin_city']) ?></div>
                                </div>
                                <div style="text-align: center; color: var(--t3);">
                                    <i class="bi bi-arrow-right-circle-fill" style="font-size: 32px; opacity: 0.6;"></i>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--t3); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Destination</div>
                                    <div style="color: var(--t1); font-size: 20px; font-weight: 800;">🚩 <?= escape($ship['dest_city']) ?></div>
                                </div>
                            </div>

                            <!-- Shipment Details Grid - Enhanced -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                                <div style="background: linear-gradient(135deg, rgba(59, 124, 253, 0.1) 0%, rgba(59, 124, 253, 0.05) 100%); padding: 20px; border-radius: 12px; border: 1px solid rgba(59, 124, 253, 0.2);">
                                    <div style="color: var(--t2); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Weight</div>
                                    <div style="color: var(--a); font-weight: 800; font-size: 18px;"><?= $ship['weight'] ?> <span style="font-size: 14px;">kg</span></div>
                                </div>
                                <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%); padding: 20px; border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <div style="color: var(--t2); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Shipping Cost</div>
                                    <div style="color: #22c55e; font-weight: 800; font-size: 18px;">PKR <?= number_format($ship['price'], 0) ?></div>
                                </div>
                                <div style="background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(20, 184, 166, 0.05) 100%); padding: 20px; border-radius: 12px; border: 1px solid rgba(20, 184, 166, 0.2);">
                                    <div style="color: var(--t2); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Courier</div>
                                    <div style="color: #14b8a6; font-weight: 800; font-size: 16px;"><?= escape($ship['agent_name'] ?? 'ConsignX') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Timeline / Journey Section -->
                        <?php if (!empty($history)): ?>
                        <div style="background: var(--card); border: 1px solid var(--ln); border-radius: 16px; padding: 32px; margin-bottom: 32px;">
                            <h3 style="color: var(--t1); font-weight: 800; margin-bottom: 28px; font-size: 20px;">🛣️ Shipment Journey</h3>
                            <div style="position: relative;">
                                <?php foreach ($history as $index => $event): ?>
                                <div style="display: flex; margin-bottom: <?= $index === count($history) - 1 ? '0' : '32px' ?>;">
                                    <!-- Timeline Dot -->
                                    <div style="width: 50px; display: flex; flex-direction: column; justify-content: flex-start; flex-shrink: 0; align-items: center;">
                                        <div style="width: 20px; height: 20px; background: linear-gradient(135deg, var(--a) 0%, var(--am) 100%); border-radius: 50%; border: 4px solid var(--card); position: relative; z-index: 3; box-shadow: 0 0 0 4px var(--bg0);"></div>
                                        <?php if ($index < count($history) - 1): ?>
                                        <div style="width: 3px; height: 40px; background: linear-gradient(to bottom, var(--a), rgba(59, 124, 253, 0.2)); margin-top: 4px;"></div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Event Content -->
                                    <div style="margin-left: 24px; flex: 1; padding-top: 2px;">
                                        <div style="background: rgba(59, 124, 253, 0.05); border-left: 3px solid var(--a); padding: 16px; border-radius: 8px;">
                                            <div style="color: var(--t1); font-weight: 700; margin-bottom: 6px; font-size: 16px;">✓ <?= escape($event['status']) ?></div>
                                            <div style="color: var(--t2); font-size: 14px; margin-bottom: 6px; line-height: 1.5;"><?= escape($event['remarks']) ?></div>
                                            <div style="color: var(--t3); font-size: 12px; font-weight: 600;">📅 <?= date('M d, Y \a\t g:i A', strtotime($event['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- PDF Export Card -->
                        <div style="background: linear-gradient(135deg, rgba(59, 124, 253, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%); border: 1px solid var(--ln); border-radius: 16px; padding: 32px; text-align: center;">
                            <div style="margin-bottom: 16px;">
                                <i class="bi bi-file-earmark-pdf" style="font-size: 48px; color: var(--a); opacity: 0.8;"></i>
                            </div>
                            <h4 style="color: var(--t1); font-weight: 700; margin-bottom: 8px;">Download Shipment Report</h4>
                            <p style="color: var(--t2); margin-bottom: 24px; font-size: 14px;">Get your official electronic receipt and complete tracking documentation in PDF format.</p>
                            <button id="exportPDF-public" style="background: var(--a); color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all .3s ease; box-shadow: 0 4px 15px rgba(59, 124, 253, 0.3);">
                                <i class="bi bi-download me-2"></i> Export as PDF
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Footer -->
            <div style="padding: 32px 5%; border-top: 1px solid rgba(255,255,255,.07); text-align: center;">
                <p style="color: var(--t3); font-size: 13px; margin: 0;">
                    © 2026 ConsignX. All rights reserved. | 
                    <a href="../index.php" style="color: var(--a); text-decoration: none;">Home</a>
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- LOGGED-IN CUSTOMER LAYOUT (With sidebar) -->
    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <?php require_once '../includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php require_once '../includes/top_header.php'; ?>

            <div class="container-fluid">
                <!-- Back Button ALWAYS VISIBLE -->
                <div class="d-flex align-items-center mb-4">
                    <a href="javascript:history.back()" class="btn-back me-3">
                        <i class="bi bi-arrow-left me-2"></i> Back
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
    <?php endif; ?>

    <!-- jsPDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // PDF Export Function Shared by Both Public and Logged-In Pages
        function generateShipmentPDF() {
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
                ["Origin City", "<?= escape($ship['origin_city']) ?>", "Total Value", "PKR <?= number_format($ship['price'], 0) ?>"],
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

            // 3. Status & ETA Highlights
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

            // 4. Final Summary & Verification
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
        }

        document.addEventListener('DOMContentLoaded', () => {
            // PDF Export for Logged-In Users
            const exportBtnLoggedIn = document.getElementById('exportPDF');
            if (exportBtnLoggedIn) {
                exportBtnLoggedIn.addEventListener('click', generateShipmentPDF);
            }

            // PDF Export for Public Users
            const exportBtnPublic = document.getElementById('exportPDF-public');
            if (exportBtnPublic) {
                exportBtnPublic.addEventListener('click', generateShipmentPDF);
            }

            // Add hover effects
            document.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('mouseover', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px rgba(59, 124, 253, 0.4)';
                });
                btn.addEventListener('mouseout', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>

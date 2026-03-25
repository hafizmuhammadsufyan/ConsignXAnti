<?php
// FILE: /consignxAnti/admin/company_requests.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

// Secure the route
require_role('admin');

$admin_name = $_SESSION['user_name'];
$msg = '';

// Handle Actions (Approve, Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        
        $request_id = (int) $_POST['request_id'];
        
        // Approve Request
        if ($_POST['action'] === 'approve') {
            try {
                $pdo->beginTransaction();
                
                // Get request details
                $stmt = $pdo->prepare("SELECT * FROM company_requests WHERE id = ?");
                $stmt->execute([$request_id]);
                $req = $stmt->fetch();
                
                if ($req && $req['status'] === 'pending') {
                    // Generate password
                    $generated_password = strtolower(str_replace(' ', '', $req['company_name'])) . rand(100, 999);
                    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                    
                    // Insert to agents
                    $stmt = $pdo->prepare("INSERT INTO agents (name, company_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$req['name'], $req['company_name'], $req['email'], $req['phone'], $hashed_password]);
                    
                    // Update request status
                    $stmt = $pdo->prepare("UPDATE company_requests SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$request_id]);
                    
                    // Send Email
                    send_agent_welcome_email($req['email'], $req['company_name'], 'active', $generated_password);
                    
                    $msg = "<div class='alert alert-success alert-dismissible fade show'>Company request approved. Agent account created and credentials emailed.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to approve request: " . escape($e->getMessage()) . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
        
        // Reject Request
        elseif ($_POST['action'] === 'reject') {
            try {
                $stmt = $pdo->prepare("UPDATE company_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$request_id]);
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Company request rejected.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to reject request.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    }
}

// Fetch Pending Requests
try {
    $stmt = $pdo->query("SELECT * FROM company_requests WHERE status = 'pending' ORDER BY created_at ASC");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $msg = "<div class='alert alert-danger'>Error loading requests: " . escape($e->getMessage()) . "</div>";
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Requests - ConsignX Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/neumorphism.css">
</head>

<body class="neumorphic-bg">

    <div class="admin-wrapper">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn btn-primary sidebar-toggle-btn shadow-sm" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Main Sidebar -->
        <nav class="sidebar d-flex flex-column justify-content-between neumorphic-card m-3 border-0">
            <!-- Desktop Sidebar Toggle -->
            <div class="desktop-toggle-btn text-muted">
                <i class="bi bi-chevron-left fs-5"></i>
            </div>
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
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active" href="company_requests.php">
                            <i class="bi bi-person-lines-fill me-2"></i> Requests
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
            <header class="top-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Company Requests</h2>
                    <p class="text-muted mb-0 small">Review and approve registration requests.</p>
                </div>
            </header>

            <?= $msg ?>

            <div class="neumorphic-card p-4">
                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date Submitted</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No pending company registration requests.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></small>
                                        </td>
                                        <td class="fw-bold">
                                            <?= escape($req['company_name']) ?>
                                        </td>
                                        <td>
                                            <?= escape($req['name']) ?>
                                        </td>
                                        <td><a href="mailto:<?= escape($req['email']) ?>"><?= escape($req['email']) ?></a></td>
                                        <td><?= escape($req['phone']) ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this company?');">
                                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn btn-sm neumorphic-btn text-success py-1 px-2" data-bs-toggle="tooltip" title="Approve & Create Account">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn btn-sm neumorphic-btn text-danger py-1 px-2" data-bs-toggle="tooltip" title="Reject Request">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
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

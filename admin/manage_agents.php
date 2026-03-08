<?php
// FILE: /consignxAnti/admin/manage_agents.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Secure the route
require_role('admin');

$admin_name = $_SESSION['user_name'];
$msg = '';

// Handle Status Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $agent_id = (int) $_POST['agent_id'];
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

        try {
            $stmt = $pdo->prepare("UPDATE agents SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $agent_id]);
            $msg = "<div class='alert alert-success alert-dismissible fade show'>Agent status updated successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to update agent status.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// Fetch Agents
try {
    $stmt = $pdo->query("SELECT * FROM agents ORDER BY created_at DESC");
    $agents = $stmt->fetchAll();
} catch (PDOException $e) {
    $msg = "<div class='alert alert-danger'>Error loading agents: " . escape($e->getMessage()) . "</div>";
    $agents = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents - ConsignX Admin</title>
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
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="manage_shipments.php">
                            <i class="bi bi-box-seam me-2"></i> Shipments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="manage_agents.php">
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
                <h2 class="fw-bold text-primary mb-0">Manage Agents</h2>
                <button class="btn neumorphic-btn btn-primary fw-bold" disabled data-bs-toggle="tooltip"
                    title="Registration is open publicly via homepage">
                    <i class="bi bi-plus-lg me-1"></i> Add Agent
                </button>
            </div>

            <?= $msg ?>

            <div class="neumorphic-card p-4">
                <div class="table-responsive">
                    <table class="table neumorphic-table table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($agents)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No agents registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($agents as $agent): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?= escape($agent['company_name']) ?>
                                        </td>
                                        <td>
                                            <?= escape($agent['name']) ?>
                                        </td>
                                        <td><a href="mailto:<?= escape($agent['email']) ?>">
                                                <?= escape($agent['email']) ?>
                                            </a></td>
                                        <td>
                                            <?= escape($agent['phone']) ?>
                                        </td>
                                        <td>
                                            <?php if ($agent['status'] === 'active'): ?>
                                                <span class="badge rounded-pill bg-success text-white">Active</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary text-white">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= escape($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                <?php if ($agent['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit" class="btn btn-sm neumorphic-btn text-danger py-1 px-2"
                                                        data-bs-toggle="tooltip" title="Deactivate Agent">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="btn btn-sm neumorphic-btn text-success py-1 px-2"
                                                        data-bs-toggle="tooltip" title="Activate Agent">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
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
<?php
// FILE: /consignxAnti/admin/manage_agents.php

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

// Handle Add, Edit, Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        
        // Add Agent
        if ($_POST['action'] === 'add_agent') {
            $company = trim($_POST['company_name']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);

            // Generate password
            $generated_password = strtolower(str_replace(' ', '', $company)) . rand(100, 999);
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO agents (name, company_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $company, $email, $phone, $hashed_password]);
                
                // Send email
                send_agent_welcome_email($email, $company, 'active', $generated_password);
                
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Agent added successfully. Email sent with credentials.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate email
                    $msg = "<div class='alert alert-warning alert-dismissible fade show'>An agent with this email already exists.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                } else {
                    $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to add agent.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            }
        }
        
        // Edit Agent
        elseif ($_POST['action'] === 'edit_agent') {
            $agent_id = (int) $_POST['agent_id'];
            $company = trim($_POST['company_name']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);

            try {
                $stmt = $pdo->prepare("UPDATE agents SET name = ?, company_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $company, $email, $phone, $agent_id]);
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Agent updated successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to update agent.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }

        // Delete Agent
        elseif ($_POST['action'] === 'delete_agent') {
            $agent_id = (int) $_POST['agent_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ?");
                $stmt->execute([$agent_id]);
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Agent deleted successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to delete agent. They might have existing shipments.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    }
}
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
                        <a class="nav-link neumorphic-btn btn-primary text-center text-white active"
                            href="manage_agents.php">
                            <i class="bi bi-building me-2"></i> Agents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link neumorphic-btn text-center text-decoration-none" href="company_requests.php">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-primary mb-0">Manage Agents</h2>
                <button class="btn neumorphic-btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addAgentModal">
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
                                            <button class="btn btn-sm neumorphic-btn text-primary py-1 px-2 ms-1" 
                                                    data-bs-toggle="modal" data-bs-target="#editAgentModal<?= $agent['id'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm neumorphic-btn text-danger py-1 px-2 ms-1" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteAgentModal<?= $agent['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                            <!-- Edit Modal -->
                                            <div class="modal fade text-start" id="editAgentModal<?= $agent['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content neumorphic-card border-0">
                                                        <div class="modal-header border-0">
                                                            <h5 class="modal-title fw-bold">Edit Agent</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                                <input type="hidden" name="action" value="edit_agent">
                                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small fw-bold">Company Name</label>
                                                                    <input type="text" name="company_name" class="form-control neumorphic-input w-100" value="<?= escape($agent['company_name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small fw-bold">Contact Person</label>
                                                                    <input type="text" name="name" class="form-control neumorphic-input w-100" value="<?= escape($agent['name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small fw-bold">Email</label>
                                                                    <input type="email" name="email" class="form-control neumorphic-input w-100" value="<?= escape($agent['email']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small fw-bold">Phone</label>
                                                                    <input type="text" name="phone" class="form-control neumorphic-input w-100" value="<?= escape($agent['phone']) ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn neumorphic-btn" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn neumorphic-btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade text-start" id="deleteAgentModal<?= $agent['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content neumorphic-card border-0">
                                                        <div class="modal-header border-0">
                                                            <h5 class="modal-title fw-bold text-danger">Delete Agent</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete <strong><?= escape($agent['company_name']) ?></strong>? This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer border-0">
                                                            <form method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                                <input type="hidden" name="action" value="delete_agent">
                                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                                <button type="button" class="btn neumorphic-btn" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn neumorphic-btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Agent Modal -->
            <div class="modal fade" id="addAgentModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content neumorphic-card border-0">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">Add New Agent</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="add_agent">
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Company Name</label>
                                    <input type="text" name="company_name" class="form-control neumorphic-input w-100" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Contact Person / Agent Name</label>
                                    <input type="text" name="name" class="form-control neumorphic-input w-100" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Email</label>
                                    <input type="email" name="email" class="form-control neumorphic-input w-100" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Phone</label>
                                    <input type="text" name="phone" class="form-control neumorphic-input w-100" required>
                                </div>
                                <div class="alert alert-info py-2 small">
                                    <i class="bi bi-info-circle me-1"></i> A generated password will be emailed to the agent.
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn neumorphic-btn" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn neumorphic-btn btn-primary">Add Agent</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>
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
                
                $msg = display_alert("Agent added successfully. Credentials emailed.", "success");
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $msg = display_alert("An agent with this email already exists.", "warning");
                } else {
                    $msg = display_alert("Failed to add agent.", "danger");
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
                $msg = display_alert("Agent details updated.", "success");
            } catch (PDOException $e) {
                $msg = display_alert("Update failed.", "danger");
            }
        }

        // Delete Agent
        elseif ($_POST['action'] === 'delete_agent') {
            $agent_id = (int) $_POST['agent_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ?");
                $stmt->execute([$agent_id]);
                $msg = display_alert("Agent removed.", "success");
            } catch (PDOException $e) {
                $msg = display_alert("Cannot delete agent with linked shipments.", "danger");
            }
        }

        // Toggle Status
        elseif ($_POST['action'] === 'toggle_status') {
            $agent_id = (int) $_POST['agent_id'];
            $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
            try {
                $stmt = $pdo->prepare("UPDATE agents SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $agent_id]);
                $msg = display_alert("Status updated to $new_status.", "success");
            } catch (PDOException $e) {
                $msg = display_alert("Status update failed.", "danger");
            }
        }
    }
}

// Fetch Agents
try {
    $stmt = $pdo->query("SELECT * FROM agents ORDER BY created_at DESC");
    $agents = $stmt->fetchAll();
} catch (PDOException $e) {
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
        <?php 
        $role = 'admin';
        $active_page = 'manage_agents.php';
        require_once '../includes/sidebar.php'; 
        ?>

        <main class="main-content">
            <?php 
            $page_title = 'Manage Agents';
            require_once '../includes/top_header.php'; 
            ?>

            <div class="container-fluid px-0">
                <?= $msg ?>

                <div class="neumorphic-card p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1"><i class="bi bi-person-badge me-2 text-primary"></i>Logistics Partners</h5>
                            <p class="text-muted small mb-0">List of registered agents and their statuses.</p>
                        </div>
                        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                            <div class="header-search flex-grow-1" style="max-width: 300px;">
                                <div class="input-group neumorphic-search px-3 py-1">
                                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="agentSearchInput" class="form-control border-0 bg-transparent shadow-none" placeholder="Search by name, email or ID...">
                                </div>
                            </div>
                            <button class="btn neumorphic-btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                                <i class="bi bi-plus-lg me-1"></i> Add New Agent
                            </button>
                        </div>
                    </div>

                    <div class="premium-table-container">
                        <div class="table-responsive">
                            <table class="premium-table" id="agentsTable">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Contact</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agents)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-5">No agents found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($agents as $agent): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= escape($agent['company_name']) ?></div>
                                                <div class="smaller text-muted fw-medium"><?= escape($agent['email']) ?></div>
                                            </td>
                                            <td class="small fw-bold text-muted"><?= escape($agent['name']) ?></td>
                                            <td class="small fw-bold"><?= escape($agent['phone']) ?></td>
                                            <td>
                                                <span class="badge-neumorphic <?= $agent['status'] === 'active' ? 'status-delivered' : 'bg-secondary text-white' ?> small px-3">
                                                    <?= ucfirst(escape($agent['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <!-- Toggle Status -->
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $agent['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                        <button type="submit" class="btn btn-sm neumorphic-btn <?= $agent['status'] === 'active' ? 'text-danger' : 'text-success' ?>" title="Toggle Status">
                                                            <i class="bi bi-power"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm neumorphic-btn text-primary" data-bs-toggle="modal" data-bs-target="#editAgentModal<?= $agent['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm neumorphic-btn text-danger" data-bs-toggle="modal" data-bs-target="#deleteAgentModal<?= $agent['id'] ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editAgentModal<?= $agent['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content neumorphic-card border-0 text-start">
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
                                                                        <label class="form-label text-muted small fw-bold">Agent Name</label>
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
                                                                    <button type="submit" class="btn neumorphic-btn btn-primary">Update Partner</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteAgentModal<?= $agent['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content neumorphic-card border-0 text-start">
                                                            <div class="modal-header border-0 pb-0">
                                                                <h5 class="modal-title fw-bold text-danger">Confirm Removal</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-0">Are you sure you want to remove <strong><?= escape($agent['company_name']) ?></strong> from the platform?</p>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <form method="POST">
                                                                    <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                                                    <input type="hidden" name="action" value="delete_agent">
                                                                    <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                                    <button type="button" class="btn neumorphic-btn" data-bs-dismiss="modal">Back</button>
                                                                    <button type="submit" class="btn neumorphic-btn btn-danger px-4">Remove Forever</button>
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
            </div>
            </div>

            <!-- Add Agent Modal -->
            <div class="modal fade" id="addAgentModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content neumorphic-card border-0">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">Onboard New Logistics Partner</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="add_agent">
                                <div class="mb-3 text-start">
                                    <label class="form-label text-muted small fw-bold">Company Name</label>
                                    <input type="text" name="company_name" class="form-control neumorphic-input w-100" placeholder="e.g. Swift Logistics" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="form-label text-muted small fw-bold">Primary Contact Person</label>
                                    <input type="text" name="name" class="form-control neumorphic-input w-100" placeholder="Agent Full Name" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="form-label text-muted small fw-bold">Official Email</label>
                                    <input type="email" name="email" class="form-control neumorphic-input w-100" placeholder="contact@company.com" required>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="form-label text-muted small fw-bold">Phone Number</label>
                                    <input type="text" name="phone" class="form-control neumorphic-input w-100" placeholder="+123 456 7890" required>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn neumorphic-btn" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn neumorphic-btn btn-primary px-4">Register Agent</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('agentSearchInput');
            const tableBody = document.querySelector('#agentsTable tbody');
            const rows = tableBody.querySelectorAll('tr');

            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let resultsFound = false;

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        resultsFound = true;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Handle no results
                const existingNoResults = tableBody.querySelector('.no-results-row');
                if (!resultsFound) {
                    if (!existingNoResults) {
                        const noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = `<td colspan="5" class="text-center text-muted py-5">No partners match "${query}"</td>`;
                        tableBody.appendChild(noResultsRow);
                    } else {
                        existingNoResults.querySelector('td').innerText = `No partners match "${query}"`;
                        existingNoResults.style.display = '';
                    }
                } else if (existingNoResults) {
                    existingNoResults.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
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
                // Get request details to check status
                $stmt = $pdo->prepare("SELECT status FROM company_requests WHERE id = ?");
                $stmt->execute([$request_id]);
                $req = $stmt->fetch();
                
                if ($req && $req['status'] === 'pending') {
                    $stmt = $pdo->prepare("UPDATE company_requests SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $msg = "<div class='alert alert-success alert-dismissible fade show'>Company request rejected.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                } else {
                    $msg = "<div class='alert alert-warning alert-dismissible fade show'>This request has already been processed and cannot be modified.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to reject request.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Invalid security token.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Filtering Logic
$where_clauses = [];
$params = [];

// Default to showing only pending requests (show others only if explicitly filtered)
if (empty($_GET['status'])) {
    // No status filter provided - default to pending
    $where_clauses[] = "cr.status = ?";
    $params[] = 'pending';
} elseif ($_GET['status'] !== '') {
    // Status filter provided and not empty
    $where_clauses[] = "cr.status = ?";
    $params[] = $_GET['status'];
}
// If status is empty string, show all statuses (no filter added)

if (!empty($_GET['date_from'])) {
    $where_clauses[] = "cr.created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $where_clauses[] = "cr.created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}
if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(cr.name LIKE ? OR cr.email LIKE ? OR cr.phone LIKE ? OR cr.company_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = empty($where_clauses) ? "1=1" : implode(" AND ", $where_clauses);

// Pagination
$limit = 15;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    // Fetch requests with filter
    $sql = "SELECT cr.* FROM company_requests cr
            WHERE $where_sql
            ORDER BY cr.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // AJAX Response
    if (isset($_GET['ajax'])) {
        if (empty($requests)) exit('');
        foreach ($requests as $req) {
            include '../includes/company_request_row_template.php';
        }
        exit;
    }

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

        <!-- Main Sidebar -->
        <?php 
        $role = 'admin';
        $active_page = 'company_requests.php';
        require_once '../includes/sidebar.php';
        ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div>
                    <h2 class="fw-bold text-primary mb-0">Company Requests</h2>
                    <p class="text-muted mb-0 small">Review and approve registration requests.</p>
                </div>
            </header>

            <?= $msg ?>

            <!-- Filters Section -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label small fw-bold"><i class="bi bi-search me-1"></i>Search</label>
                        <input type="text" name="search" class="form-control neumorphic-input py-2" placeholder="Name, Email, or Phone" value="<?= escape($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold"><i class="bi bi-calendar me-1"></i>From Date</label>
                        <input type="date" name="date_from" class="form-control neumorphic-input py-2"
                            value="<?= escape($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold"><i class="bi bi-calendar me-1"></i>To Date</label>
                        <input type="date" name="date_to" class="form-control neumorphic-input py-2"
                            value="<?= escape($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold"><i class="bi bi-tag me-1"></i>Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= (($_GET['status'] ?? 'pending') === 'pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($_GET['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($_GET['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <button type="submit" class="btn btn-primary neumorphic-btn fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="company_requests.php" class="btn btn-secondary neumorphic-btn fw-bold"><i class="bi bi-arrow-clockwise me-1"></i>Clear</a>
                    </div>
                </form>
            </div>

            <div class="premium-table-container">
                <div class="table-responsive">
                    <table class="premium-table" id="companyRequestsTable">
                        <thead>
                            <tr>
                                <th>Company<br><small>Date Submitted</small></th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No company registration requests found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <?php include '../includes/company_request_row_template.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($requests) >= $limit): ?>
                <div class="text-center mt-4">
                    <button id="loadMoreBtn" class="btn neumorphic-btn px-5 py-2 fw-bold text-primary">
                        <i class="bi bi-arrow-down-circle me-1"></i> Load More
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                let offset = <?= $limit ?>;
                const loadMoreBtn = document.getElementById('loadMoreBtn');
                const tableBody = document.querySelector('.premium-table tbody');
                if (loadMoreBtn) {
                    loadMoreBtn.addEventListener('click', function() {
                        const originalText = loadMoreBtn.innerHTML;
                        loadMoreBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2"></span> Loading...';
                        loadMoreBtn.disabled = true;

                        const url = new URL(window.location.href);
                        url.searchParams.set('ajax', '1');
                        url.searchParams.set('offset', offset);

                        fetch(url)
                            .then(response => response.text())
                            .then(data => {
                                if (data.trim() === '') {
                                    loadMoreBtn.innerHTML = 'All Records Loaded';
                                    loadMoreBtn.classList.add('text-muted');
                                    loadMoreBtn.disabled = true;
                                    return;
                                }
                                tableBody.insertAdjacentHTML('beforeend', data);
                                offset += <?= $limit ?>;
                                loadMoreBtn.innerHTML = originalText;
                                loadMoreBtn.disabled = false;
                                // Re-initialize tooltips if needed
                                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                tooltipTriggerList.map(function (tooltipTriggerEl) {
                                    return new bootstrap.Tooltip(tooltipTriggerEl);
                                });
                            })
                            .catch(err => {
                                console.error(err);
                                loadMoreBtn.innerHTML = 'Error Loading More';
                                loadMoreBtn.disabled = false;
                            });
                    });
                }
            });
            </script>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>

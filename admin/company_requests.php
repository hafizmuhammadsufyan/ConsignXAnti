<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

// Only admins can see this page
require_role('admin');

$admin_name = $_SESSION['user_name'];
$msg = '';

// When the admin submits approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $request_id = (int) $_POST['request_id'];
        
        // When admin approves the company
        if ($_POST['action'] === 'approve') {
            try {
                $pdo->beginTransaction();
                
                // Get the request details from the database
                $stmt = $pdo->prepare("SELECT * FROM company_requests WHERE id = ?");
                $stmt->execute([$request_id]);
                $req = $stmt->fetch();
                
                if ($req && $req['status'] === 'pending') {
                    // Create a random password for the agent account
                    $generated_password = strtolower(str_replace(' ', '', $req['company_name'])) . rand(100, 999);
                    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                    
                    // Create the agent account
                    $stmt = $pdo->prepare("INSERT INTO agents (name, company_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$req['name'], $req['company_name'], $req['email'], $req['phone'], $hashed_password]);
                    
                    // Mark request as approved
                    $stmt = $pdo->prepare("UPDATE company_requests SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$request_id]);
                    
                    // Send credentials via email
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
        
        // When admin rejects the company
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

// Build the database query based on filters the user applied
$where_clauses = ["1=1"];
$params = [];

// Show pending requests by default
$status_filter = $_GET['status'] ?? 'pending';

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

// Let admin filter by date range
if (!empty($_GET['date_from'])) {
    $where_clauses[] = "created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
}

if (!empty($_GET['date_to'])) {
    $where_clauses[] = "created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
}

// Search function for finding by name, email, or phone
if (!empty($_GET['search'])) {
    $search_term = "%" . $_GET['search'] . "%";
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = implode(" AND ", $where_clauses);

// Pagination - show 15 requests per load
$limit = 15;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

try {
    $sql = "SELECT * FROM company_requests 
            WHERE $where_sql 
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Handle infinite scroll/load more via AJAX
    if (isset($_GET['ajax'])) {
        if (empty($requests)) exit('');
        foreach ($requests as $req) {
            include '../includes/company_request_row_template.php';
        }
        exit;
    }

} catch (PDOException $e) {
    // Something went wrong with the database
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

            <!-- Filters Section -->
            <div class="neumorphic-card p-4 mb-4">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Search</label>
                        <input type="text" name="search" class="form-control neumorphic-input py-2" placeholder="Name/Email" value="<?= escape($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">From Date</label>
                        <input type="date" name="date_from" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">To Date</label>
                        <input type="date" name="date_to" class="form-control neumorphic-input py-2" value="<?= escape($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Status</label>
                        <select name="status" class="form-select neumorphic-input py-2">
                            <option value="pending" <?= ($status_filter ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($status_filter ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($status_filter ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="" <?= empty($status_filter) ? 'selected' : '' ?>>All Statuses</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn neumorphic-btn btn-primary flex-grow-1 py-2">Apply</button>
                        <a href="company_requests.php" class="btn neumorphic-btn btn-secondary py-2" data-bs-toggle="tooltip" title="Reset Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="premium-table-container">
                <div class="table-responsive">
                    <table class="premium-table" id="companyRequestsTable">
                        <thead>
                            <tr>
                                <th>Date Submitted <br> Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No company registration requests found.</td>
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
                const tableBody = document.querySelector('#companyRequestsTable tbody');
                
                if (loadMoreBtn) {
                    loadMoreBtn.addEventListener('click', function() {
                        const originalText = loadMoreBtn.innerHTML;
                        loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Loading...';
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

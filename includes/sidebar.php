<?php
// FILE: /consignxAnti/includes/sidebar.php

/**
 * Shared Sidebar component for Admin, Agent, and Customer panels.
 */

$current_role = $role ?? $_SESSION['user_role'] ?? '';
$active = $active_page ?? basename($_SERVER['PHP_SELF']);
$company = $_SESSION['company_name'] ?? '';
$u_id = $_SESSION['user_id'] ?? 0;

$menu_items = [];

// Determine base path based on directory depth
$is_shared = strpos($_SERVER['PHP_SELF'], '/shared/') !== false;
$is_auth = strpos($_SERVER['PHP_SELF'], '/auth/') !== false;
$is_root = (substr_count($_SERVER['PHP_SELF'], '/') <= 2); // e.g., /index.php or /track_shipment.php

$base = "";
if ($is_shared || $is_auth) {
    $base = "../" . $current_role . "/";
} elseif ($is_root) {
    if (!empty($current_role)) {
        $base = $current_role . "/";
    }
}

// Fixed links for shared items
$profile_link = ($is_shared || $is_auth || !$is_root) ? "../shared/profile.php" : "shared/profile.php";
$logout_link = ($is_shared || $is_auth || !$is_root) ? "../auth/logout.php" : "auth/logout.php";

if ($current_role === 'admin') {
    $menu_items = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'link' => 'dashboard.php'],
        ['label' => 'Shipments', 'icon' => 'bi-box-seam', 'link' => 'manage_shipments.php'],
        ['label' => 'Agents', 'icon' => 'bi-building', 'link' => 'manage_agents.php'],
        ['label' => 'Requests', 'icon' => 'bi-person-lines-fill', 'link' => 'company_requests.php'],
        ['label' => 'Reports', 'icon' => 'bi-graph-up', 'link' => 'reports.php'],
    ];
} elseif ($current_role === 'agent') {
    $menu_items = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'link' => 'dashboard.php'],
        ['label' => 'New Shipment', 'icon' => 'bi-plus-circle', 'link' => 'create_shipment.php'],
        ['label' => 'Manage Shipments', 'icon' => 'bi-box-seam', 'link' => 'manage_shipments.php'],
    ];
} elseif ($current_role === 'customer') {
    $menu_items = [
        ['label' => 'My Shipments', 'icon' => 'bi-box-seam', 'link' => 'dashboard.php'],
        ['label' => 'Track Shipment', 'icon' => 'bi-search', 'link' => 'track_shipment.php'],
    ];
}
?>

<button class="btn btn-primary sidebar-toggle-btn rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
    <i class="bi bi-list fs-3"></i>
</button>

<nav class="sidebar d-flex flex-column justify-content-between neumorphic-card border-0 shadow-lg">
    <div>
        <div class="text-center mb-1 sidebar-header">
            <h3 class="fw-bold text-primary mb-0">ConsignX</h3>
            <small class="text-muted"><?= ucfirst($current_role) ?> Portal</small>
        </div>

        <?php if ($current_role === 'agent' && !empty($company)): ?>
            <div class="text-center mt-3 mb-4 company-badge-wrapper">
                <span class="badge rounded-pill bg-primary px-3 py-2 fw-medium text-uppercase shadow-sm">
                    <i class="bi bi-building me-1"></i>
                    <span class="company-name"><?= escape($company) ?></span>
                </span>
            </div>
        <?php endif; ?>

        <ul class="nav flex-column gap-2 mt-4">
            <?php foreach ($menu_items as $item): ?>
                <li class="nav-item">
                    <a class="nav-link neumorphic-btn <?= ($active === $item['link']) ? 'btn-primary text-white active' : 'text-decoration-none' ?> text-center"
                        href="<?= $base ?><?= $item['link'] ?>">
                        <i class="bi <?= $item['icon'] ?> me-2"></i> 
                        <span class="link-label"><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Bottom Controls -->
    <div class="mt-auto pt-3 border-top border-secondary border-opacity-10">
        <div class="px-2 mb-3">
            <a href="<?= $profile_link ?>" class="nav-link neumorphic-btn <?= ($active === 'profile.php') ? 'btn-primary text-white active' : 'text-decoration-none text-muted' ?> py-2 text-center w-100">
                <i class="bi bi-person-circle me-2"></i>
                <span class="link-label">My Profile</span>
            </a>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 px-2 dark-mode-toggle">
            <span class="text-muted small fw-bold label-hide">Dark Mode</span>
            <label class="theme-switch">
                <input type="checkbox">
                <span class="slider round"></span>
            </label>
        </div>
        <a href="<?= $logout_link ?>" class="btn neumorphic-btn btn-danger w-100 fw-bold logout-btn">
            <i class="bi bi-box-arrow-right me-2"></i> 
            <span class="link-label">Logout</span>
        </a>
    </div>
</nav>

<?php
// FILE: /consignxAnti/includes/top_header.php

/**
 * Shared Top Header for Admin, Agent, and Customer panels.
 * 
 * Expected variables:
 * @param string $page_title
 * @param string $user_name
 * @param array $user (optional, to get profile_image)
 */

$u_id = $_SESSION['user_id'] ?? 0;
$u_role = $_SESSION['user_role'] ?? '';
$u_name = $_SESSION['user_name'] ?? 'User';

// Re-fetch user if NOT passed to ensure we have the fresh profile_image
if (!isset($user) || !isset($user['profile_image'])) {
    $table = ($u_role === 'admin') ? 'admins' : (($u_role === 'agent') ? 'agents' : 'customers');
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$u_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $user = ['profile_image' => null];
    }
}

$profile_img = ($user['profile_image'] ?? '');
$initials = strtoupper(substr($u_name, 0, 1));
?>

<header class="top-header d-flex align-items-center justify-content-between mb-4">
    <div class="header-left">
        <h2 class="fw-bold text-primary mb-1"><?= $page_title ?? 'Dashboard' ?></h2>
        <p class="text-muted mb-0 small text-uppercase fw-bold letter-spacing-1"><?= date('l, F j, Y') ?></p>
    </div>

    <div class="header-search d-none d-lg-block mx-4 flex-grow-1" style="max-width: 400px;">
        <form action="<?= ($u_role === 'customer') ? '../customer/track_shipment.php' : '../' . $u_role . '/manage_shipments.php' ?>" method="GET">
            <div class="input-group neumorphic-search px-3 py-1">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="<?= ($u_role === 'customer') ? 'tracking_number' : 'tracking_id' ?>" class="form-control border-0 bg-transparent shadow-none" placeholder="Search tracking number...">
            </div>
        </form>
    </div>

    <div class="header-right d-flex align-items-center gap-3">
        <!-- Notification Icon -->
        <div class="dropdown notification-dropdown">
            <button class="btn neumorphic-btn rounded-circle p-0 d-flex align-items-center justify-content-center position-relative" style="width: 45px; height: 45px;" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                <i class="bi bi-bell text-muted fs-5"></i>
                <span class="notification-badge animate__animated animate__pulse animate__infinite" id="notifBadge"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 p-0 rounded-4 overflow-hidden" style="width: 320px;">
                <div class="p-3 bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 small">Notifications</h6>
                    <button class="btn btn-sm p-0 smaller text-primary fw-bold" onclick="markAllRead()">Mark all as read</button>
                </div>
                <div class="notification-list" style="max-height: 350px; overflow-y: auto;">
                    <!-- Notification Item 1 -->
                    <div class="notification-item unread d-flex gap-3 p-3 border-bottom">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold small">New Shipment Added</span>
                                <span class="smaller text-muted">2m ago</span>
                            </div>
                            <p class="smaller text-muted mb-0">Shipment #C-784Y-9921 has been registered by Agent.</p>
                        </div>
                    </div>
                    <!-- Notification Item 2 -->
                    <div class="notification-item unread d-flex gap-3 p-3 border-bottom">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold small">Delivery Alert</span>
                                <span class="smaller text-muted">1h ago</span>
                            </div>
                            <p class="smaller text-muted mb-0">Package #C-1221-8812 was successfully delivered.</p>
                        </div>
                    </div>
                    <!-- Notification Item 3 -->
                    <div class="notification-item d-flex gap-3 p-3">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-person-badge"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold small">New Agent Onboard</span>
                                <span class="smaller text-muted">5h ago</span>
                            </div>
                            <p class="smaller text-muted mb-0">Swift Logistics has joined the platform.</p>
                        </div>
                    </div>
                </div>
                <div class="p-2 bg-light border-top text-center">
                    <a href="#" class="smaller fw-bold text-primary text-decoration-none">View All Activity</a>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown">
            <button class="btn neumorphic-btn d-flex align-items-center gap-2 p-1 pe-3 rounded-pill" data-bs-toggle="dropdown">
                <div class="profile-thumb overflow-hidden" style="width: 38px; height: 38px;">
                    <?php if ($profile_img): ?>
                        <img src="<?= get_user_profile_image($profile_img) ?>" alt="User" class="w-100 h-100 rounded-circle object-fit-cover shadow-sm border border-2 border-white">
                    <?php else: ?>
                        <div class="avatar-fallback"><?= $initials ?></div>
                    <?php endif; ?>
                </div>
                <div class="text-start d-none d-md-block">
                    <p class="mb-0 fw-bold small text-primary line-height-1"><?= escape($u_name) ?></p>
                    <p class="mb-0 text-muted smaller text-uppercase fw-bold opacity-75"><?= ucfirst($u_role) ?></p>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-3 p-2 rounded-4">
                <li><a class="dropdown-item rounded-3 py-2" href="../shared/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item rounded-3 py-2" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider opacity-10"></li>
                <li><a class="dropdown-item rounded-3 py-2 text-danger fw-bold" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>

<script>
function markAllRead() {
    document.querySelectorAll('.notification-item').forEach(item => {
        item.classList.remove('unread');
    });
    const badge = document.getElementById('notifBadge');
    if (badge) badge.style.display = 'none';
}
</script>

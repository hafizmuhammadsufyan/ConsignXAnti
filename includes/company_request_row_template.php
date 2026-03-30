<tr>
    <td>
        <div class="fw-bold text-primary"><?= escape($req['company_name']) ?></div>
        <div class="smaller text-muted fw-medium"><?= date('M d, Y', strtotime($req['created_at'])) ?></div>
    </td>
    <td>
        <div class="fw-bold small"><?= escape($req['name']) ?></div>
        <div class="smaller text-muted fw-medium"><a href="mailto:<?= escape($req['email']) ?>" class="text-decoration-none text-muted"><?= escape($req['email']) ?></a></div>
    </td>
    <td>
        <div class="fw-medium small"><?= escape($req['phone']) ?></div>
    </td>
    <td>
        <span class="badge rounded-pill <?php
            $status_class = match($req['status']) {
                'pending' => 'bg-warning bg-opacity-10 text-warning',
                'approved' => 'bg-success bg-opacity-10 text-success',
                'rejected' => 'bg-danger bg-opacity-10 text-danger',
                default => 'bg-secondary bg-opacity-10 text-secondary'
            };
            echo $status_class;
        ?> border-0 px-3 fw-bold smaller">
            <?= strtoupper(escape($req['status'])) ?>
        </span>
    </td>
    <td class="text-end">
        <?php if (in_array($req['status'], ['approved', 'rejected'])): ?>
            <button class="btn btn-sm neumorphic-btn" disabled data-bs-toggle="tooltip" title="Request locked - already processed">
                <i class="bi bi-lock-fill text-muted"></i>
            </button>
        <?php else: ?>
            <div class="dropdown">
                <button class="btn btn-sm neumorphic-btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-status-dropdown shadow-lg border-0 mt-2">
                    <li><h6 class="dropdown-header fw-bold text-muted smaller tracking-wider px-3 pt-2 pb-2">ACTIONS</h6></li>
                    <li>
                        <form method="POST" class="px-3 py-2">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success w-100 neumorphic-btn py-2 fw-bold" onclick="return confirm('Approve this company request and create account?')">
                                <i class="bi bi-check-lg me-1"></i> Approve
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider opacity-10"></li>
                    <li>
                        <form method="POST" class="px-3 pb-2">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <button type="submit" class="btn btn-sm text-danger text-start w-100 p-2 rounded-3 border-0 bg-transparent dropdown-item-danger" onclick="return confirm('Reject this request?')">
                                <i class="bi bi-x-lg me-1"></i> Reject
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </td>
</tr>

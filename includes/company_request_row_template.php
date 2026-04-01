<tr>
    <td>
        <div class="smaller text-muted fw-medium"><?= date('M d, Y', strtotime($req['created_at'])) ?></div>
        <div class="fw-bold text-primary"><?= escape($req['company_name']) ?></div>
    </td>
    <td>
        <div class="fw-bold small"><?= escape($req['name']) ?></div>
    </td>
    <td>
        <a href="mailto:<?= escape($req['email']) ?>" class="text-decoration-none">
            <div class="text-primary"><?= escape($req['email']) ?></div>
        </a>
    </td>
    <td>
        <div class="small"><?= escape($req['phone']) ?></div>
    </td>
    <td>
        <?php
        $status_class = match ($req['status']) {
            'pending' => 'status-pending',
            'approved' => 'status-delivered',
            'rejected' => 'status-cancelled',
            default => 'status-pending'
        };
        $status_label = ucfirst($req['status']);
        ?>
        <span class="badge-neumorphic <?= $status_class ?> small px-3 fw-bold">
            <?= $status_label ?>
        </span>
    </td>
    <td class="text-end">
        <?php if ($req['status'] === 'pending'): ?>
            <div class="dropdown dropup">
                <button class="btn btn-sm neumorphic-btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end premium-status-dropdown shadow-lg border-0 mt-2" style="position: absolute; bottom: 100%; top: auto; z-index: 1000;">
                    <li><h6 class="dropdown-header fw-bold text-muted smaller tracking-wider px-3 pt-2 pb-2">REQUEST ACTION</h6></li>
                    <li>
                        <form method="POST" class="px-3 py-2" onsubmit="return confirm('Are you sure you want to approve this company and create an agent account?');">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success w-100 neumorphic-btn py-2 fw-bold">
                                <i class="bi bi-check-circle me-2"></i> Approve & Create Account
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider opacity-10"></li>
                    <li>
                        <div class="px-3 pb-2">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="block_email_<?= $req['id'] ?>" name="block_email" value="1">
                                    <label class="form-check-label small" for="block_email_<?= $req['id'] ?>">
                                        Block email from re-registering
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="block_phone_<?= $req['id'] ?>" name="block_phone" value="1">
                                    <label class="form-check-label small" for="block_phone_<?= $req['id'] ?>">
                                        Block phone from re-registering
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-sm text-danger text-start w-100 p-2 rounded-3 border-0 bg-transparent dropdown-item-danger">
                                    <i class="bi bi-x-circle me-2"></i> Reject Request
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <button class="btn btn-sm neumorphic-btn" disabled data-bs-toggle="tooltip" title="Request already processed">
                <i class="bi bi-lock-fill text-muted"></i>
            </button>
        <?php endif; ?>
    </td>
</tr>

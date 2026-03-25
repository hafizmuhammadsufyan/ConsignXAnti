<tr>
    <td>
        <div class="fw-bold text-primary"><?= escape($ship['tracking_number']) ?></div>
        <div class="smaller text-muted fw-medium"><?= date('M d, Y', strtotime($ship['created_at'])) ?></div>
    </td>
    <td>
        <div class="fw-bold small"><?= escape($ship['customer_name']) ?></div>
        <div class="smaller text-muted fw-medium"><?= escape($ship['customer_email']) ?></div>
    </td>
    <td>
        <div class="small d-flex align-items-center">
            <span class="text-muted"><?= escape($ship['origin_city']) ?></span>
            <i class="bi bi-arrow-right mx-2 text-primary opacity-50"></i>
            <span class="fw-bold"><?= escape($ship['dest_city']) ?></span>
        </div>
    </td>
    <td>
        <?php
        $status_class = match ($ship['status']) {
            'Pending' => 'status-pending',
            'Delivered' => 'status-delivered',
            'Cancelled' => 'status-cancelled',
            'Returned' => 'status-returned',
            'Picked Up' => 'status-picked-up',
            'Out For Delivery' => 'status-out-delivery',
            default => 'status-transit'
        };
        ?>
        <span class="badge-neumorphic <?= $status_class ?> small px-3 fw-bold">
            <?= escape($ship['status']) ?>
        </span>
    </td>
    <td class="text-end">
        <div class="dropdown">
            <button class="btn btn-sm neumorphic-btn" type="button" data-bs-toggle="dropdown" <?= $ship['status'] === 'Delivered' ? 'disabled' : '' ?>>
                <i class="bi bi-pencil-square me-1"></i> Update
            </button>
                <ul class="dropdown-menu dropdown-menu-end premium-status-dropdown shadow-lg border-0 mt-2">
                    <li><h6 class="dropdown-header fw-bold text-muted smaller tracking-wider px-3 pt-2 pb-2">REFINE STATUS</h6></li>
                    <li>
                        <form method="POST" class="px-3 py-1">
                            <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="shipment_id" value="<?= $ship['id'] ?>">
                            <label class="form-label smaller fw-bold text-muted mb-1">Update Current Status</label>
                            <select name="new_status" class="form-select form-select-sm premium-status-select mb-2" required>
                                <option value="" disabled>Select Status...</option>
                                <option value="Pending" <?= $ship['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Picked Up" <?= $ship['status'] == 'Picked Up' ? 'selected' : '' ?>>Picked Up</option>
                                <option value="In Transit" <?= $ship['status'] == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
                                <option value="Out For Delivery" <?= $ship['status'] == 'Out For Delivery' ? 'selected' : '' ?>>Out For Delivery</option>
                                <option value="Delivered" <?= $ship['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="Returned" <?= $ship['status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                                <option value="Cancelled" <?= $ship['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <input type="text" name="location" class="form-control form-control-sm neumorphic-input py-2 mb-2" placeholder="Record Location">
                            <button type="submit" class="btn btn-sm btn-primary w-100 neumorphic-btn py-2 fw-bold">Update Case</button>
                        </form>
                    </li>
                </ul>
        </div>
    </td>
</tr>

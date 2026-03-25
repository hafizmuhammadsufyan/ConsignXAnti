<tr>
    <td>
        <div class="fw-bold text-primary"><?= escape($ship['tracking_number']) ?></div>
        <div class="smaller text-muted fw-medium"><?= date('M d, Y', strtotime($ship['created_at'])) ?></div>
    </td>
    <td>
        <div class="small fw-bold text-muted"><?= escape($ship['customer_name']) ?></div>
    </td>
    <td>
        <div class="small d-flex align-items-center">
            <span class="text-muted"><?= escape($ship['origin_city']) ?></span>
            <i class="bi bi-arrow-right mx-2 text-primary opacity-50"></i>
            <span class="fw-bold"><?= escape($ship['dest_city']) ?></span>
        </div>
    </td>
    <td>
        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border-0 px-3 fw-bold smaller">
            <?= escape($ship['agent_company'] ?? 'ConsignX Admin') ?>
        </span>
    </td>
    <td class="fw-bold">
        <?= format_currency($ship['price']) ?>
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
</tr>

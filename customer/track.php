<?php
// FILE: /consignxAnti/customer/track.php
// DEPRECATED: This file is now a redirect stub. All tracking goes through track_shipment.php

require_once '../includes/config.php';

$tracking_number = trim($_GET['tracking_number'] ?? $_GET['id'] ?? '');

if ($tracking_number) {
    header('Location: track_shipment.php?tracking_number=' . urlencode($tracking_number));
    exit;
} else {
    header('Location: track_shipment.php');
    exit;
}
?>
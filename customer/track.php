<?php
// FILE: /consignxAnti/customer/track.php
// This is a redirect stub - actual tracking page is track_shipment.php

require_once '../includes/config.php';

$tracking_number = trim($_GET['tracking_number'] ?? $_GET['id'] ?? '');

if ($tracking_number) {
    // Forward to the main tracking page with the tracking number
    header('Location: track_shipment.php?tracking_number=' . urlencode($tracking_number));
    exit;
} else {
    // No tracking number provided, go to main tracking page
    header('Location: track_shipment.php');
    exit;
}
?>
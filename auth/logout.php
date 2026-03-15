<?php
// FILE: /consignxAnti/auth/logout.php

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Execute the secure logout function
logout();

// Redirect back to landing page or login page
header('Location: ../auth/login.php');
exit;
?>
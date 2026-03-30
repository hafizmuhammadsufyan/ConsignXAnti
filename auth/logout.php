<?php
// FILE: /consignxAnti/auth/logout.php

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Perform logout and clear session
logout();

// Redirect to login page
header('Location: ../auth/login.php');
exit;
?>
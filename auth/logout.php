<?php
require_once '../config/db.php';
require_once '../logs/log_activity.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], $_SESSION['username'], 'Logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>
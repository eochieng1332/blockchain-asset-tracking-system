<?php
// Authentication check for protected pages
require_once dirname(__DIR__) . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Role-based access control
function checkRole($allowed_roles = []) {
    if (empty($allowed_roles)) {
        return true;
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: " . BASE_URL . "dashboard/dashboard.php?error=unauthorized");
        exit();
    }
    
    return true;
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed!");
    }
    return true;
}
?>
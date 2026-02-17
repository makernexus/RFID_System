<?php
// Session authentication check
// Include this file at the top of any protected page
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Store the requested page for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Optional: Check for session timeout (30 minutes of inactivity)
$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check if user has required role
function requireRole($requiredRole) {
    $userRole = $_SESSION['role'] ?? '';
    
    // Check if user has the required role
    if (is_array($requiredRole)) {
        if (!in_array($userRole, $requiredRole)) {
            die("Access denied. You do not have permission to view this page.");
        }
    } else {
        if ($userRole !== $requiredRole) {
            die("Access denied. You do not have permission to view this page.");
        }
    }
    
    return true;
}

// Function to check if user is manager or admin
// Function to check if user is admin (not manager)
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

?>

<?php

// Admin Logging Functions
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

/**
 * Log an admin action to the admin_log table
 * 
 * @param mysqli $connection Database connection (must have write permissions)
 * @param string $actionType Type of action (e.g., 'update_mod', 'update_classes', 'photo_upload')
 * @param string $clientID Client ID being modified
 * @param string $fieldChanged Field that was changed
 * @param string $beforeValue Value before change (null for new records or photo uploads)
 * @param string $afterValue Value after change (null for deletions)
 * @param string $notes Optional notes about the change
 * @return bool True if logged successfully, false otherwise
 */
function logAdminAction($connection, $actionType, $clientID, $fieldChanged, $beforeValue, $afterValue, $notes = '') {
    // Get admin info from session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        return false;
    }
    
    $adminUserID = $_SESSION['user_id'];
    $adminUsername = $_SESSION['username'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $insertSQL = "INSERT INTO admin_log 
                  (adminUserID, adminUsername, actionType, clientID, fieldChanged, beforeValue, afterValue, notes, ipAddress) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connection, $insertSQL);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "sssssssss", 
        $adminUserID, $adminUsername, $actionType, $clientID, 
        $fieldChanged, $beforeValue, $afterValue, $notes, $ipAddress);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Get current values for a client before making changes
 * 
 * @param mysqli $connection Database connection
 * @param string $clientID Client ID to query
 * @return array|null Array with client data or null if not found
 */
function getClientDataForLogging($connection, $clientID) {
    $selectSQL = "SELECT MOD_Eligible, displayClasses FROM clientInfo WHERE clientID = ?";
    $stmt = mysqli_prepare($connection, $selectSQL);
    if (!$stmt) {
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, "s", $clientID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $data;
}

?>

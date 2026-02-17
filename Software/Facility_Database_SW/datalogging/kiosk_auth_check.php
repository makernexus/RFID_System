<?php
// Kiosk authentication check using persistent tokens
// Include this file at the top of kiosk pages instead of auth_check.php
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

// This allows kiosks to authenticate once and maintain access across restarts
// without requiring manual login each time

include 'db_auth.php';

/**
 * Validate a kiosk token from the request
 * Checks both cookie and HTTP header for flexibility
 */
function validateKioskToken() {
    $token = null;
    
    // Check for token in cookie (primary method)
    if (isset($_COOKIE['kiosk_token'])) {
        $token = $_COOKIE['kiosk_token'];
    }
    
    // Check for token in Authorization header (alternative)
    $headers = getallheaders();
    if (isset($headers['X-Kiosk-Token'])) {
        $token = $headers['X-Kiosk-Token'];
    }
    
    // Check for token in GET parameter (for initial setup)
    if (isset($_GET['kiosk_token'])) {
        $token = $_GET['kiosk_token'];
        // Set cookie for future requests
        setcookie('kiosk_token', $token, time() + (365 * 24 * 60 * 60), '/', '', false, true);
    }
    
    if (!$token) {
        return false;
    }
    
    // Validate token against database
    $con = getAuthDbConnection();
    if (!$con) {
        return false;
    }
    
    $token = mysqli_real_escape_string($con, $token);
    $sql = "SELECT id, name, location, last_used, expires_at, is_active 
            FROM kiosk_tokens 
            WHERE token = '$token' AND is_active = 1";
    
    $result = mysqli_query($con, $sql);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        mysqli_close($con);
        return false;
    }
    
    $kioskToken = mysqli_fetch_assoc($result);
    
    // Check if token is expired
    if ($kioskToken['expires_at'] && strtotime($kioskToken['expires_at']) < time()) {
        mysqli_close($con);
        return false;
    }
    
    // Update last_used timestamp
    $updateSql = "UPDATE kiosk_tokens SET last_used = NOW() WHERE id = " . (int)$kioskToken['id'];
    mysqli_query($con, $updateSql);
    
    mysqli_close($con);
    
    // Store kiosk info in global for logging
    $GLOBALS['kiosk_info'] = $kioskToken;
    
    return true;
}

/**
 * Get the current kiosk information
 */
function getKioskInfo() {
    return $GLOBALS['kiosk_info'] ?? null;
}

/**
 * Check if user is authenticated via regular session
 */
function isUserAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in with valid session
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        // Optional: Check for session timeout (30 minutes of inactivity)
        $timeout = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            return false; // Session expired
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

// Check authentication - allow BOTH kiosk tokens AND regular user sessions
$isKiosk = validateKioskToken();
$isUser = isUserAuthenticated();

if (!$isKiosk && !$isUser) {
    // Not authenticated as either kiosk or user, show error
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Kiosk Authentication Required</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .error-box {
                background-color: #fff;
                border-left: 4px solid #f44336;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #f44336;
                margin-top: 0;
            }
            p {
                line-height: 1.6;
            }
            .setup-link {
                display: inline-block;
                margin-top: 15px;
                padding: 10px 20px;
                background-color: #2196F3;
                color: white;
                text-decoration: none;
                border-radius: 4px;
            }
            .setup-link:hover {
                background-color: #0b7dda;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>&#128274; Kiosk Authentication Required</h1>
            <p>This page requires kiosk authentication. The kiosk token is either missing, invalid, or has expired.</p>
            <p><strong>If you are setting up a new kiosk:</strong></p>
            <ol>
                <li>Contact an administrator to generate a kiosk token</li>
                <li>Use the setup URL provided by the administrator</li>
                <li>The authentication will persist across browser restarts</li>
            </ol>
            <p><strong>If this is an existing kiosk:</strong></p>
            <ul>
                <li>The token may have expired</li>
                <li>Browser data may have been cleared</li>
                <li>Contact an administrator to reactivate</li>
            </ul>
            <p><strong>If you are staff:</strong></p>
            <ul>
                <li><a href="login.php" style="color: #2196F3;">Login with your credentials</a> to access this page</li>
            </ul>
            <a href="kiosk_setup.php" class="setup-link">Administrator: Setup Kiosk</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Token validated - kiosk is authenticated
?>

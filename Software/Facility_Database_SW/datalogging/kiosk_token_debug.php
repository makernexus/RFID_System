<?php
// Debug kiosk token cookie setting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent "headers already sent" errors
ob_start();

echo "<h1>Kiosk Token Debug</h1>";

// Check if token provided
if (isset($_GET['kiosk_token'])) {
    $token = $_GET['kiosk_token'];
    echo "<p><strong>Token received:</strong> " . htmlspecialchars($token) . "</p>";
    
    // Try to set cookie
    $result = setcookie('kiosk_token', $token, [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    echo "<p><strong>setcookie() returned:</strong> " . ($result ? "TRUE" : "FALSE") . "</p>";
    echo "<p><strong>Cookie should be set for:</strong> 1 year from now</p>";
    echo "<p><strong>Refresh this page (without ?kiosk_token) to test if cookie persists</strong></p>";
} else {
    echo "<p><strong>No token in URL</strong></p>";
}

// Check if cookie exists
if (isset($_COOKIE['kiosk_token'])) {
    echo "<p style='color: green;'><strong>✓ Cookie IS set!</strong></p>";
    echo "<p><strong>Cookie value:</strong> " . htmlspecialchars($_COOKIE['kiosk_token']) . "</p>";
} else {
    echo "<p style='color: red;'><strong>✗ Cookie NOT set</strong></p>";
}

echo "<h2>All Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Headers Info:</h2>";
echo "<p>Headers sent: " . (headers_sent($file, $line) ? "YES at $file:$line" : "NO") . "</p>";

ob_end_flush();
?>

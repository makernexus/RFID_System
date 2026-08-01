<?php
// particle_login.php
// Server-side Particle.io authentication
// Credentials are read from rfidconfig.ini and never sent to the browser
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus

// Set JSON header early
header('Content-Type: application/json');

// Require authentication - must be logged in to get Particle token
require_once 'auth_check.php';

// Check Origin/Referer to prevent CSRF attacks
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Get the current server's base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$allowedOrigin = $protocol . '://' . $host;

// Check if request came from our own site
$validOrigin = false;
if (!empty($origin)) {
    $validOrigin = (strpos($origin, $allowedOrigin) === 0);
} elseif (!empty($referer)) {
    $validOrigin = (strpos($referer, $allowedOrigin) === 0);
}

if (!$validOrigin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Invalid origin']);
    exit;
}

// Prevent direct access without proper request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if credentials were passed in via POST
$rawInput = file_get_contents('php://input');
$postData = json_decode($rawInput, true);
$particleUsername = null;
$particlePassword = null;

// Use provided credentials if available
if (is_array($postData) && !empty($postData['username']) && !empty($postData['password'])) {
    $particleUsername = $postData['username'];
    $particlePassword = $postData['password'];
} else {
    // Fall back to configuration file
    $ini_array = parse_ini_file("rfidconfig.ini", true);
    
    if (!isset($ini_array["Particle"]["username"]) || !isset($ini_array["Particle"]["password"])) {
        http_response_code(500);
        echo json_encode(['error' => 'Particle credentials not configured in rfidconfig.ini and not provided in request']);
        exit;
    }
    
    $particleUsername = $ini_array["Particle"]["username"];
    $particlePassword = $ini_array["Particle"]["password"];
}

// Verify credentials are not blank
if (empty($particleUsername) || empty($particlePassword)) {
    http_response_code(500);
    echo json_encode(['error' => 'Credentials are not set. Check your server .ini file']);
    exit;
}

// Particle.io API endpoints
$particleLoginUrl = 'https://api.particle.io/oauth/token';
$particleClientId = 'particle';
$particleClientSecret = 'particle';

// Prepare authentication request
$postData = [
    'grant_type' => 'password',
    'username' => $particleUsername,
    'password' => $particlePassword,
    'client_id' => $particleClientId,
    'client_secret' => $particleClientSecret
];

// Initialize cURL
$ch = curl_init($particleLoginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle errors
if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Authentication failed', 'details' => $response]);
    exit;
}

// Parse and return the response
$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid response from Particle.io']);
    exit;
}

// Return only the access token (not the credentials)
echo json_encode([
    'access_token' => $data['access_token'],
    'expires_in' => $data['expires_in'] ?? null
]);

<?php
// get_preferred_device.php
// Returns the preferred device name from rfidconfig.ini
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

// Set JSON header early
header('Content-Type: application/json');

// Require authentication
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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read configuration file
$ini_array = parse_ini_file("rfidconfig.ini", true);

// Get preferred device name (may not exist)
$preferredDevice = $ini_array["Particle"]["preferredDevice"] ?? null;

// Return the preferred device name (or null if not set)
echo json_encode([
    'preferredDevice' => $preferredDevice
]);

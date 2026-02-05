<?php
// Admin Dashboard
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';  // Require authentication

// Only admins can access this page
if (!isAdmin()) {
    die("Access denied. Only administrators can access this page.");
}

ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - RFID System</title>
    <link href="style.css" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .dashboard-header {
            margin-bottom: 30px;
        }
        .dashboard-section {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .dashboard-section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .dashboard-links {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .dashboard-links li {
            margin: 10px 0;
        }
        .dashboard-links a {
            display: inline-block;
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
            min-width: 200px;
            text-align: center;
        }
        .dashboard-links a:hover {
            background-color: #45a049;
        }
        .dashboard-links a.secondary {
            background-color: #2196F3;
        }
        .dashboard-links a.secondary:hover {
            background-color: #0b7dda;
        }
        .dashboard-links a.warning {
            background-color: #ff9800;
        }
        .dashboard-links a.warning:hover {
            background-color: #e68900;
        }
        .info-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin-top: 20px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1976D2;
        }
    </style>
</head>
<body>
    <?php echo $authHeader; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>. Use this dashboard to manage the RFID system.</p>
        </div>
        
        <!-- Navigation Section -->
        <div class="dashboard-section">
            <h2>Navigation</h2>
            <ul class="dashboard-links">
                <li><a href="rfidhome.php">📊 RFID Reports Home</a></li>
                <li><a href="index.html" class="secondary">🏠 Public Home Page</a></li>
            </ul>
        </div>
        
        <!-- User Management Section -->
        <div class="dashboard-section">
            <h2>User Management</h2>
            <ul class="dashboard-links">
                <li><a href="user_management.php">👥 Manage Users</a></li>
            </ul>
            <p style="color: #666; margin-top: 15px;">
                Create, edit, and delete user accounts. Assign roles (Admin, Accounting, MoD) and manage user permissions.
            </p>
        </div>
        
        <!-- System Administration Section -->
        <div class="dashboard-section">
            <h2>System Administration</h2>
            <ul class="dashboard-links">
                <li><a href="setup_auth.php" class="warning">⚙️ Re-run Auth Setup</a></li>
            </ul>
            <p style="color: #666; margin-top: 15px;">
                <strong>Warning:</strong> Only run the auth setup if you need to reinitialize the authentication system.
            </p>
        </div>
        
        <!-- Quick Stats Section -->
        <div class="dashboard-section">
            <h2>Quick Information</h2>
            <div class="info-box">
                <h3>System Status</h3>
                <p><strong>Your Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
                <p><strong>Session Timeout:</strong> 30 minutes of inactivity</p>
                <p><strong>Authentication:</strong> Active and protecting pages</p>
            </div>
        </div>
        
        <!-- Documentation Section -->
        <div class="dashboard-section">
            <h2>Documentation</h2>
            <ul class="dashboard-links">
                <li><a href="AUTH_README.md" class="secondary">📖 Authentication System Documentation</a></li>
                <li><a href="QUICK_START.txt" class="secondary">🚀 Quick Start Guide</a></li>
            </ul>
        </div>
        
        <!-- Protected Pages List -->
        <div class="dashboard-section">
            <h2>Pages Protection</h2>
            <p style="margin-top: 15px; font-style: italic; color: #666;">
                To protect additional pages, add <code>include 'auth_check.php';</code> at the top of the PHP file.
            </p>
        </div>
    </div>
</body>
</html>

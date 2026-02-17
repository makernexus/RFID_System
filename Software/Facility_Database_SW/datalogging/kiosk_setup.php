<?php
// Kiosk Token Management Page
// Admin-only page to create and manage kiosk authentication tokens
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';
requireRole('admin');
include 'db_auth.php';

// Connect to database
$con = getAuthDbConnection();
if (!$con) {
    die("Database connection failed");
}

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = trim($_POST['name']);
                $location = trim($_POST['location']);
                $expiresIn = (int)$_POST['expires_in'];
                
                if (empty($name)) {
                    $message = "Kiosk name is required";
                    $messageType = "error";
                } else {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    
                    // Calculate expiration
                    $expiresAt = null;
                    if ($expiresIn > 0) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresIn days"));
                    }
                    
                    $name = mysqli_real_escape_string($con, $name);
                    $location = mysqli_real_escape_string($con, $location);
                    $username = mysqli_real_escape_string($con, $_SESSION['username']);
                    $expiresAtStr = $expiresAt ? "'" . mysqli_real_escape_string($con, $expiresAt) . "'" : "NULL";
                    
                    $insertSql = "INSERT INTO kiosk_tokens (token, name, location, expires_at, created_by)
                                  VALUES ('$token', '$name', '$location', $expiresAtStr, '$username')";
                    
                    if (mysqli_query($con, $insertSql)) {
                        $message = "Kiosk token created successfully!";
                        $messageType = "success";
                        $newToken = $token;
                        $newTokenId = mysqli_insert_id($con);
                    } else {
                        $message = "Error creating token: " . mysqli_error($con);
                        $messageType = "error";
                    }
                }
                break;
                
            case 'deactivate':
                $tokenId = (int)$_POST['token_id'];
                $updateSql = "UPDATE kiosk_tokens SET is_active = 0 WHERE id = $tokenId";
                if (mysqli_query($con, $updateSql)) {
                    $message = "Kiosk token deactivated";
                    $messageType = "success";
                } else {
                    $message = "Error deactivating token: " . mysqli_error($con);
                    $messageType = "error";
                }
                break;
                
            case 'activate':
                $tokenId = (int)$_POST['token_id'];
                $updateSql = "UPDATE kiosk_tokens SET is_active = 1 WHERE id = $tokenId";
                if (mysqli_query($con, $updateSql)) {
                    $message = "Kiosk token activated";
                    $messageType = "success";
                } else {
                    $message = "Error activating token: " . mysqli_error($con);
                    $messageType = "error";
                }
                break;
                
            case 'delete':
                $tokenId = (int)$_POST['token_id'];
                $deleteSql = "DELETE FROM kiosk_tokens WHERE id = $tokenId";
                if (mysqli_query($con, $deleteSql)) {
                    $message = "Kiosk token deleted";
                    $messageType = "success";
                } else {
                    $message = "Error deleting token: " . mysqli_error($con);
                    $messageType = "error";
                }
                break;
        }
    }
}

// Fetch all tokens
$result = mysqli_query($con, "SELECT * FROM kiosk_tokens ORDER BY created_at DESC");
$tokens = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tokens[] = $row;
    }
}

mysqli_close($con);

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiosk Token Management</title>
    <link href="style.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .section {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background-color: #da190b;
        }
        .btn-warning {
            background-color: #ff9800;
            color: white;
        }
        .btn-warning:hover {
            background-color: #e68900;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 14px;
            margin: 2px;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-expired {
            background-color: #fff3cd;
            color: #856404;
        }
        .setup-url {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
            margin-top: 15px;
        }
        .setup-url code {
            display: block;
            background-color: #263238;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
        }
        .copy-btn {
            background-color: #2196F3;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .copy-btn:hover {
            background-color: #0b7dda;
        }
        .info-box {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ff9800;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖥️ Kiosk Token Management</h1>
        <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($newToken)): ?>
            <div class="setup-url">
                <h3>✅ New Kiosk Token Created!</h3>
                <p><strong>Setup Instructions:</strong></p>
                <ol>
                    <li>Open the kiosk browser</li>
                    <li>Visit the setup URL below (copy and paste into the kiosk browser)</li>
                    <li>The token will be saved and persist across browser restarts</li>
                </ol>
                <p><strong>Setup URL:</strong></p>
                <code id="setupUrl"><?php 
                    $setupUrl = $currentUrl . dirname($_SERVER['PHP_SELF']) . "/rfidcurrentcheckinsWithMOD.php?kiosk_token=" . $newToken;
                    echo htmlspecialchars($setupUrl); 
                ?></code>
                <button class="copy-btn" onclick="copySetupUrl()">📋 Copy URL</button>
                <p style="color: #856404; margin-top: 10px;"><strong>⚠️ Important:</strong> Save this URL securely. You won't be able to retrieve it later.</p>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>ℹ️ About Kiosk Tokens</h3>
            <p>Kiosk tokens allow display screens to access protected pages without requiring manual login after each restart.</p>
            <ul>
                <li><strong>One-time setup:</strong> Visit the setup URL once on each kiosk</li>
                <li><strong>Persistent:</strong> Token stored in browser, survives restarts</li>
                <li><strong>Secure:</strong> Each kiosk gets a unique, revocable token</li>
                <li><strong>Expiring:</strong> Set expiration dates for temporary kiosks</li>
            </ul>
        </div>
        
        <!-- Create New Token -->
        <div class="section">
            <h2>Create New Kiosk Token</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="name">Kiosk Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Lobby Display, Workshop Kiosk">
                </div>
                
                <div class="form-group">
                    <label for="location">Location (Optional)</label>
                    <input type="text" id="location" name="location" placeholder="e.g., Main Entrance, Wood Shop">
                </div>
                
                <div class="form-group">
                    <label for="expires_in">Expires In</label>
                    <select id="expires_in" name="expires_in">
                        <option value="0">Never (Permanent)</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Kiosk Token</button>
            </form>
        </div>
        
        <!-- Existing Tokens -->
        <div class="section">
            <h2>Existing Kiosk Tokens</h2>
            <?php if (empty($tokens)): ?>
                <p style="color: #666;">No kiosk tokens created yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $token): ?>
                            <?php
                            $isExpired = $token['expires_at'] && strtotime($token['expires_at']) < time();
                            $status = $isExpired ? 'expired' : ($token['is_active'] ? 'active' : 'inactive');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($token['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($token['location'] ?: '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo strtoupper($status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($token['created_at'])); ?></td>
                                <td><?php echo $token['last_used'] ? date('Y-m-d H:i', strtotime($token['last_used'])) : 'Never'; ?></td>
                                <td><?php echo $token['expires_at'] ? date('Y-m-d', strtotime($token['expires_at'])) : 'Never'; ?></td>
                                <td>
                                    <?php if ($token['is_active'] && !$isExpired): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-small" onclick="return confirm('Deactivate this kiosk token?')">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-small">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Permanently delete this token? This cannot be undone.')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copySetupUrl() {
            const url = document.getElementById('setupUrl').textContent;
            navigator.clipboard.writeText(url).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
    </script>
</body>
</html>

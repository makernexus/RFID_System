<?php

// Admin Activity Log Viewer
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin']);  // Require admin role
include 'commonfunctions.php';

// Get filter parameters
$filterClientID = isset($_GET['clientID']) ? trim($_GET['clientID']) : '';
$filterAdmin = isset($_GET['admin']) ? trim($_GET['admin']) : '';
$filterDays = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Build query with filters
$whereConditions = ["logDate >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
$params = [$filterDays];
$types = "i";

if ($filterClientID !== '') {
    $whereConditions[] = "clientID = ?";
    $params[] = $filterClientID;
    $types .= "s";
}

if ($filterAdmin !== '') {
    $whereConditions[] = "adminUsername LIKE ?";
    $params[] = "%{$filterAdmin}%";
    $types .= "s";
}

$whereClause = implode(" AND ", $whereConditions);

$selectSQL = "SELECT logID, logDate, adminUsername, actionType, clientID, fieldChanged, 
                     beforeValue, afterValue, notes, ipAddress 
              FROM admin_log 
              WHERE {$whereClause}
              ORDER BY logDate DESC 
              LIMIT 500";

$stmt = mysqli_prepare($con, $selectSQL);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$logs = [];
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
}

mysqli_stmt_close($stmt);
mysqli_close($con);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Activity Log</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="style.css" rel="stylesheet">
  
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .page-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      margin: -20px -20px 30px -20px;
      border-radius: 0 0 10px 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .page-header h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
      font-weight: 600;
    }
    
    .page-header p {
      margin: 0;
      opacity: 0.9;
      font-size: 14px;
    }
    
    .filter-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    .filter-row {
      display: flex;
      gap: 15px;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    
    .filter-field {
      flex: 1;
      min-width: 150px;
    }
    
    .filter-field label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }
    
    .filter-field input,
    .filter-field select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }
    
    .filter-button {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s;
    }
    
    .filter-button:hover {
      transform: translateY(-2px);
    }
    
    .clear-button {
      background: #999;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    
    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .log-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .log-table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .log-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
    }
    
    .log-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
    }
    
    .log-table tbody tr:hover {
      background-color: #f8f9ff;
    }
    
    .log-table td {
      padding: 12px 15px;
      font-size: 14px;
      color: #333;
    }
    
    .action-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
    }
    
    .action-update_mod {
      background-color: #e3f2fd;
      color: #1976d2;
    }
    
    .action-update_classes {
      background-color: #f3e5f5;
      color: #7b1fa2;
    }
    
    .action-photo_upload {
      background-color: #e8f5e9;
      color: #388e3c;
    }
    
    .action-create_user {
      background-color: #c8e6c9;
      color: #2e7d32;
    }
    
    .action-update_user {
      background-color: #fff9c4;
      color: #f57f17;
    }
    
    .action-change_password {
      background-color: #ffccbc;
      color: #d84315;
    }
    
    .action-delete_user {
      background-color: #ffcdd2;
      color: #c62828;
    }
    
    .value-cell {
      max-width: 300px;
      font-size: 13px;
    }
    
    .value-display {
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: background-color 0.2s;
    }
    
    .value-display:hover {
      background-color: #f0f0f0;
    }
    
    .value-json {
      background-color: #f8f9fa;
      padding: 8px;
      border-radius: 4px;
      font-family: 'Courier New', monospace;
      white-space: pre-wrap;
      word-break: break-all;
      margin: 4px 0;
    }
    
    .value-changed {
      background-color: #fff9c4;
      padding: 2px 4px;
      border-radius: 2px;
      font-weight: 600;
    }
    
    .diff-container {
      display: flex;
      gap: 10px;
      margin-top: 5px;
    }
    
    .diff-column {
      flex: 1;
      background-color: #f8f9fa;
      padding: 8px;
      border-radius: 4px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
    }
    
    .diff-label {
      font-weight: 600;
      color: #666;
      margin-bottom: 4px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
    }
    
    .diff-removed {
      background-color: #ffebee;
      color: #c62828;
    }
    
    .diff-added {
      background-color: #e8f5e9;
      color: #2e7d32;
    }
    
    .diff-item {
      padding: 2px 4px;
      margin: 2px 0;
      border-radius: 2px;
    }
    
    .changes-summary {
      background-color: #e3f2fd;
      padding: 8px;
      border-radius: 4px;
      margin-top: 5px;
      font-size: 12px;
      color: #1565c0;
    }
    
    .changes-summary strong {
      color: #0d47a1;
    }
    
    .client-link {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
    }
    
    .client-link:hover {
      text-decoration: underline;
    }
    
    .no-results {
      padding: 40px;
      text-align: center;
      color: #666;
    }
    
    @media (max-width: 768px) {
      .filter-row {
        flex-direction: column;
      }
      
      .filter-field {
        width: 100%;
      }
      
      .table-container {
        overflow-x: auto;
      }
    }
  </style>
</head>

<body>
    <?php 
    ob_start();
    include 'auth_header.php';
    echo ob_get_clean();
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Admin Activity Log</h1>
            <p>Track changes made by administrators through the web interface</p>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="clientID">Client ID</label>
                        <input type="text" id="clientID" name="clientID" 
                               value="<?php echo htmlspecialchars($filterClientID); ?>" 
                               placeholder="Filter by client">
                    </div>
                    <div class="filter-field">
                        <label for="admin">Admin Username</label>
                        <input type="text" id="admin" name="admin" 
                               value="<?php echo htmlspecialchars($filterAdmin); ?>" 
                               placeholder="Filter by admin">
                    </div>
                    <div class="filter-field">
                        <label for="days">Time Range</label>
                        <select id="days" name="days">
                            <option value="7" <?php echo $filterDays == 7 ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="30" <?php echo $filterDays == 30 ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo $filterDays == 90 ? 'selected' : ''; ?>>Last 90 days</option>
                            <option value="365" <?php echo $filterDays == 365 ? 'selected' : ''; ?>>Last year</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="filter-button">Apply Filters</button>
                        <a href="rfidadminlog.php" class="clear-button">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (count($logs) > 0): ?>
            <div class="table-container">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Client ID</th>
                            <th>Field</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($logs as $log): 
                            // Try to parse JSON values
                            $beforeData = json_decode($log['beforeValue'], true);
                            $afterData = json_decode($log['afterValue'], true);
                            
                            $isJsonBefore = json_last_error() === JSON_ERROR_NONE && is_array($beforeData);
                            $isJsonAfter = json_last_error() === JSON_ERROR_NONE && is_array($afterData);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['logDate']))); ?></td>
                                <td><?php echo htmlspecialchars($log['adminUsername']); ?></td>
                                <td>
                                    <span class="action-badge action-<?php echo htmlspecialchars($log['actionType']); ?>">
                                        <?php echo htmlspecialchars($log['actionType']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['clientID'] && is_numeric($log['clientID'])): ?>
                                        <a href="rfidonemember.php?clientID=<?php echo urlencode($log['clientID']); ?>" 
                                           class="client-link">
                                            <?php echo htmlspecialchars($log['clientID']); ?>
                                        </a>
                                    <?php elseif ($log['clientID']): ?>
                                        <?php echo htmlspecialchars($log['clientID']); ?>
                                    <?php else: ?>
                                        <em>-</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['fieldChanged']); ?></td>
                                <td class="value-cell">
                                    <?php if ($isJsonBefore): ?>
                                        <em>(multiple fields)</em>
                                    <?php else: ?>
                                        <?php echo $log['beforeValue'] ? htmlspecialchars($log['beforeValue']) : '<em>-</em>'; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="value-cell">
                                    <?php 
                                    // Show changes summary if both are JSON
                                    if ($isJsonBefore && $isJsonAfter): 
                                        $changes = [];
                                        foreach ($afterData as $key => $afterValue) {
                                            // Use loose comparison to handle type differences (1 vs "1")
                                            if (!isset($beforeData[$key]) || $beforeData[$key] != $afterValue) {
                                                $beforeVal = isset($beforeData[$key]) ? $beforeData[$key] : '(new)';
                                                $changes[] = "$key: " . htmlspecialchars($beforeVal) . " → " . htmlspecialchars($afterValue);
                                            }
                                        }
                                        if (!empty($changes)):
                                    ?>
                                        <div class="changes-summary">
                                            <?php echo implode('<br>', $changes); ?>
                                        </div>
                                    <?php 
                                        else:
                                    ?>
                                        <em>(no changes detected)</em>
                                    <?php
                                        endif;
                                    elseif ($isJsonAfter): ?>
                                        <em>(multiple fields)</em>
                                    <?php else: ?>
                                        <?php echo $log['afterValue'] ? htmlspecialchars($log['afterValue']) : '<em>-</em>'; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log['notes'] ? htmlspecialchars($log['notes']) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="no-results">
                    <p style="font-size: 18px; margin: 0;">
                        No activity logs found for the selected filters
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>

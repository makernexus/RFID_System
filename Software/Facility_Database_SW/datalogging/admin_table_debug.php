<?php
// Admin Table Debug Page
// Lists all tables with row counts and oldest createDate
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';  // Require authentication

// Only administrators can access this page
if (!isAdmin()) {
    die("Access denied. Only administrators can access this page.");
}

ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();

// Get database connection
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);

// Check connection
if (mysqli_connect_errno()) {
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Get all tables in the database
$tablesQuery = "SHOW TABLES";
$tablesResult = mysqli_query($con, $tablesQuery);

$tableData = array();

while ($tableRow = mysqli_fetch_array($tablesResult)) {
    $tableName = $tableRow[0];
    
    // Get row count
    $countQuery = "SELECT COUNT(*) as row_count FROM `$tableName`";
    $countResult = mysqli_query($con, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $rowCount = $countRow['row_count'];
    
    // Try to find date column and get oldest date
    // Different tables use different date column names
    $oldestDate = "N/A";
    $dateColumnName = null;
    $possibleDateColumns = ['logDate', 'created_at', 'dateCreated', 'dateCreatedLocal', 'dateEventLocal'];
    
    // Check which date column exists for this table
    foreach ($possibleDateColumns as $columnName) {
        $columnsQuery = "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'";
        $columnsResult = mysqli_query($con, $columnsQuery);
        
        if (mysqli_num_rows($columnsResult) > 0) {
            $dateColumnName = $columnName;
            break;
        }
    }
    
    // If we found a date column, get the oldest date
    if ($dateColumnName !== null) {
        $dateQuery = "SELECT MIN(`$dateColumnName`) as oldest_date FROM `$tableName`";
        $dateResult = mysqli_query($con, $dateQuery);
        if ($dateResult) {
            $dateRow = mysqli_fetch_assoc($dateResult);
            if ($dateRow['oldest_date']) {
                $oldestDate = $dateRow['oldest_date'];
            }
        }
    }
    
    $tableData[] = array(
        'name' => $tableName,
        'row_count' => $rowCount,
        'oldest_date' => $oldestDate,
        'date_column' => $dateColumnName
    );
}

// Close connection
mysqli_close($con);

// Sort tables by name
usort($tableData, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Table Debug - RFID System</title>
    <link href="style.css" rel="stylesheet">
    <style>
        .debug-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .debug-header {
            margin-bottom: 30px;
        }
        .debug-header h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .summary-box {
            background-color: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .summary-box h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-table th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #45a049;
        }
        .debug-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        .debug-table tr:hover {
            background-color: #f5f5f5;
        }
        .debug-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .row-count {
            text-align: right;
            font-weight: bold;
            color: #2196F3;
        }
        .date-value {
            font-family: monospace;
            color: #666;
        }
        .na-value {
            color: #999;
            font-style: italic;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .back-link:hover {
            background-color: #0b7dda;
        }
        .timestamp {
            color: #666;
            font-size: 0.9em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php echo $authHeader; ?>
    
    <div class="debug-container">
        <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        
        <div class="debug-header">
            <h1>Database Table Debug Information</h1>
            <p>This page displays all tables in the database with row counts and oldest date values (from various date columns).</p>
        </div>
        
        <div class="summary-box">
            <h3>Summary</h3>
            <p><strong>Total Tables:</strong> <?php echo count($tableData); ?></p>
            <p><strong>Database:</strong> <?php echo htmlspecialchars($dbName); ?></p>
        </div>
        
        <table class="debug-table">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th style="text-align: right;">Row Count</th>
                    <th>Oldest Date</th>
                    <th>Date Column</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableData as $table): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($table['name']); ?></strong></td>
                    <td class="row-count"><?php echo number_format($table['row_count']); ?></td>
                    <td>
                        <?php if ($table['oldest_date'] === 'N/A'): ?>
                            <span class="na-value">N/A</span>
                        <?php else: ?>
                            <span class="date-value"><?php echo htmlspecialchars($table['oldest_date']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($table['date_column'] === null): ?>
                            <span class="na-value">-</span>
                        <?php else: ?>
                            <code style="background-color: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 0.9em;"><?php echo htmlspecialchars($table['date_column']); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="timestamp">
            <p><em>Generated: <?php echo date('Y-m-d H:i:s'); ?></em></p>
        </div>
    </div>
</body>
</html>

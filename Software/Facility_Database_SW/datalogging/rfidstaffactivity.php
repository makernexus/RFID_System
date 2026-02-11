<?php

// List all checkin/out by staff 
//    start/stop dates in URL
//    if no start/stop then last 7 days
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin', 'accounting']);  // Require admin or accounting role
include 'commonfunctions.php';
$maxRows = 1000;

// Get date range selection
$rangeType = isset($_GET['range']) ? $_GET['range'] : 'last_3weeks';
$customStart = isset($_GET['customStart']) ? $_GET['customStart'] : '';
$customEnd = isset($_GET['customEnd']) ? $_GET['customEnd'] : '';

// Calculate start and end dates based on selection
$startDate = '';
$endDate = date('Y-m-d');

switch ($rangeType) {
    case 'last_week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'last_3weeks':
        $startDate = date('Y-m-d', strtotime('-21 days'));
        break;
    case 'last_30':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'last_90':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'last_year':
        $startDate = date('Y-m-d', strtotime('-365 days'));
        break;
    case 'custom':
        if ($customStart && $customEnd) {
            $startDate = $customStart;
            $endDate = $customEnd;
        } else {
            // Default to last week if custom dates not provided
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-7 days'));
}

// Format dates for SQL (YYYYMMDD)
$startDateSQL = str_replace('-', '', $startDate);
$endDateSQL = str_replace('-', '', $endDate);

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

$selectSQL = "SELECT dateEventLocal, a.firstName, b.lastName, logEvent, logData 
    FROM `rawdata` a join clientInfo b
      ON a.clientID = b.clientID 
    WHERE displayClasses like '%staff%' 
      AND displayClasses <> 'exStaff'
      AND logEvent IN ('Checked In','Checked Out') 
      AND dateEventLocal between ? and ? 
    ORDER BY a.firstName, b.lastName, dateEventLocal LIMIT ?;";

$stmt = mysqli_prepare($con, $selectSQL);
mysqli_stmt_bind_param($stmt, "ssi", $startDateSQL, $endDateSQL, $maxRows);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rowsReturned = mysqli_num_rows($result);

$results = [];
while($row = mysqli_fetch_assoc($result)) {
    $results[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($con);

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Staff Activity Report</title>
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
      max-width: 1400px;
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
    
    .filter-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .filter-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: #333;
      font-size: 16px;
    }
    
    .filter-form {
      display: flex;
      gap: 15px;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    
    .form-group {
      flex: 0 0 auto;
    }
    
    .form-select {
      padding: 12px 15px;
      min-width: 200px;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      transition: border-color 0.2s;
      background-color: white;
      cursor: pointer;
    }
    
    .form-select:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .form-input {
      padding: 12px 15px;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
      transition: border-color 0.2s;
    }
    
    .form-input:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .custom-dates {
      display: none;
      gap: 10px;
      align-items: center;
    }
    
    .custom-dates.show {
      display: flex;
    }
    
    .filter-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: transform 0.2s;
      white-space: nowrap;
    }
    
    .filter-btn:hover {
      transform: translateY(-2px);
    }
    
    .stats-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      display: flex;
      gap: 30px;
      align-items: center;
    }
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #667eea;
    }
    
    .stat-label {
      font-size: 14px;
      color: #666;
      font-weight: 500;
    }
    
    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .activity-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .activity-table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .activity-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .activity-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s;
    }
    
    .activity-table tbody tr:hover {
      background-color: #f8f9ff;
    }
    
    .activity-table tbody tr:last-child {
      border-bottom: none;
    }
    
    .activity-table tbody tr.name-separator {
      border-top: 3px solid #667eea;
    }
    
    .activity-table tbody tr.name-separator td {
      padding-top: 20px;
    }
    
    .activity-table td {
      padding: 12px 15px;
      font-size: 14px;
      color: #333;
    }
    
    .date-cell {
      color: #666;
      font-size: 13px;
      white-space: nowrap;
    }
    
    .name-cell {
      font-weight: 600;
      color: #333;
    }
    
    .event-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .event-checkin {
      background-color: #4caf50;
      color: white;
    }
    
    .event-checkout {
      background-color: #2196f3;
      color: white;
    }
    
    .no-results {
      background: white;
      padding: 60px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      color: #666;
    }
    
    .warning-message {
      background-color: #fff3cd;
      color: #856404;
      padding: 12px 20px;
      border-radius: 5px;
      margin-bottom: 20px;
      border: 1px solid #ffeaa7;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }
      
      .page-header {
        margin: -10px -10px 20px -10px;
        padding: 20px;
      }
      
      .page-header h1 {
        font-size: 22px;
      }
      
      .filter-container {
        padding: 15px;
      }
      
      .filter-form {
        flex-direction: column;
      }
      
      .form-select,
      .filter-btn {
        width: 100%;
      }
      
      .custom-dates {
        flex-direction: column;
        width: 100%;
      }
      
      .form-input {
        width: 100%;
      }
      
      .stats-container {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }
      
      .table-container {
        overflow-x: auto;
      }
      
      .activity-table {
        font-size: 12px;
      }
      
      .activity-table th,
      .activity-table td {
        padding: 10px 8px;
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
            <h1>Staff Activity Report</h1>
            <p>View check-in and check-out activity for staff members</p>
        </div>
        
        <div class="filter-container">
            <label class="filter-label">Select Date Range:</label>
            <form class="filter-form" method="GET" action="rfidstaffactivity.php" id="dateRangeForm">
                <div class="form-group">
                    <select name="range" id="rangeSelect" class="form-select" onchange="toggleCustomDates()">
                        <option value="last_week" <?php echo $rangeType === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                        <option value="last_3weeks" <?php echo $rangeType === 'last_3weeks' ? 'selected' : ''; ?>>Last 3 Weeks</option>
                        <option value="last_30" <?php echo $rangeType === 'last_30' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="last_90" <?php echo $rangeType === 'last_90' ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="last_year" <?php echo $rangeType === 'last_year' ? 'selected' : ''; ?>>Last Year</option>
                        <option value="custom" <?php echo $rangeType === 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                    </select>
                </div>
                
                <div class="custom-dates <?php echo $rangeType === 'custom' ? 'show' : ''; ?>" id="customDates">
                    <input type="date" 
                           name="customStart" 
                           class="form-input" 
                           id="customStart"
                           value="<?php echo $rangeType === 'custom' ? htmlspecialchars($customStart) : ''; ?>">
                    <span>to</span>
                    <input type="date" 
                           name="customEnd" 
                           class="form-input" 
                           id="customEnd"
                           value="<?php echo $rangeType === 'custom' ? htmlspecialchars($customEnd) : ''; ?>">
                </div>
                
                <button type="submit" class="filter-btn">Generate Report</button>
            </form>
        </div>
        
        <?php if ($rowsReturned > 0): ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div>
                        <div class="stat-number"><?php echo $rowsReturned; ?></div>
                        <div class="stat-label">Total Records</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div>
                        <div class="stat-label"><strong>Date Range:</strong> <?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($rowsReturned >= $maxRows): ?>
                <div class="warning-message">
                    <strong>Warning:</strong> Results limited to <?php echo $maxRows; ?> rows. The actual number of records may be higher.
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $previousName = '';
                        foreach ($results as $row): 
                            $currentName = $row['lastName'] . ', ' . $row['firstName'];
                            $nameSeparatorClass = '';
                            
                            // Add separator class if name changes
                            if ($previousName !== '' && $previousName !== $currentName) {
                                $nameSeparatorClass = ' name-separator';
                            }
                            $previousName = $currentName;
                            
                            $eventClass = strtolower($row['logEvent']) === 'checked in' ? 'event-checkin' : 'event-checkout';
                        ?>
                            <tr class="<?php echo $nameSeparatorClass; ?>">
                                <td class="date-cell">
                                    <?php echo htmlspecialchars($row['dateEventLocal']); ?>
                                </td>
                                <td class="name-cell">
                                    <?php echo htmlspecialchars($currentName); ?>
                                </td>
                                <td>
                                    <span class="event-badge <?php echo $eventClass; ?>">
                                        <?php echo htmlspecialchars($row['logEvent']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if (is_numeric($row['logData'])) {
                                        echo number_format($row['logData'] / 60, 1);
                                    } else {
                                        echo htmlspecialchars($row['logData']);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p style="font-size: 18px; margin: 0;">
                    No staff activity found for the selected date range
                </p>
                <p style="margin-top: 10px; color: #999;">
                    Try selecting a different date range
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleCustomDates() {
            const rangeSelect = document.getElementById('rangeSelect');
            const customDates = document.getElementById('customDates');
            const customStart = document.getElementById('customStart');
            const customEnd = document.getElementById('customEnd');
            
            if (rangeSelect.value === 'custom') {
                customDates.classList.add('show');
                customStart.required = true;
                customEnd.required = true;
            } else {
                customDates.classList.remove('show');
                customStart.required = false;
                customEnd.required = false;
            }
        }
        
        // Initialize on page load
        toggleCustomDates();
    </script>
</body>

</html>

<?php

// 1 report from RFID LOGGING DATABASE
//
// Member detail checkin reports
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin']);  // Require manager, admin, or MoD role
include 'commonfunctions.php';

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }


// ------------ TABLE 1  
  
$selectSQLMembersPerMonth = "
SELECT YEAR(dateEventLocal) as yr, month(dateEventLocal) as mnth, clientID, firstName, logEvent 
FROM `rawdata` 
WHERE dateEventLocal >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
  and logEvent = 'Checked In'
group by YEAR(dateEventLocal),month(dateEventLocal), clientID
order by YEAR(dateEventLocal),month(dateEventLocal), firstName;
";

$result = mysqli_query($con, $selectSQLMembersPerMonth);

// Process the data
$results = [];
$previousMonth = "";

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $thisMonth = $row["yr"] . "-" . str_pad($row["mnth"], 2, '0', STR_PAD_LEFT);
        if ($thisMonth != $previousMonth) {
            $results[] = ['type' => 'header', 'year' => $row["yr"], 'month' => $row["mnth"]];
            $previousMonth = $thisMonth;
        }
        $results[] = ['type' => 'data', 'firstName' => $row["firstName"], 'clientID' => $row["clientID"]];
    }
}

mysqli_close($con);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Member Check-Ins Detail</title>
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
      max-width: 900px;
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
    
    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .checkin-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .checkin-table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .checkin-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
    }
    
    .checkin-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s;
    }
    
    .checkin-table tbody tr:hover {
      background-color: #f8f9ff;
    }
    
    .checkin-table tbody tr:last-child {
      border-bottom: none;
    }
    
    .checkin-table td {
      padding: 12px 15px;
      font-size: 14px;
      color: #333;
    }
    
    .month-header {
      background-color: #f8f9ff !important;
      font-weight: 600;
      color: #667eea !important;
      font-size: 15px;
    }
    
    .month-header:hover {
      background-color: #f8f9ff !important;
    }
    
    .client-id-link {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    
    .client-id-link:hover {
      color: #764ba2;
      text-decoration: underline;
    }
    
    .no-results {
      background: white;
      padding: 40px;
      text-align: center;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      color: #666;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }
      
      .page-header {
        padding: 20px;
      }
      
      .page-header h1 {
        font-size: 22px;
      }
      
      .checkin-table {
        font-size: 12px;
      }
      
      .checkin-table th,
      .checkin-table td {
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
            <h1>Member Check-Ins Detail</h1>
            <p>Monthly breakdown of member check-ins for the last 30 days</p>
        </div>
        
        <?php if (count($results) > 0): ?>
            <div class="table-container">
                <table class="checkin-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Month</th>
                            <th>First Name</th>
                            <th>Client ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <?php if ($row['type'] === 'header'): ?>
                                <tr class="month-header">
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                                    <td>
                                        <a href="rfidonemember.php?clientID=<?php echo urlencode($row['clientID']); ?>" 
                                           class="client-id-link">
                                            <?php echo htmlspecialchars($row['clientID']); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p style="font-size: 18px; margin: 0;">
                    No check-in records found
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
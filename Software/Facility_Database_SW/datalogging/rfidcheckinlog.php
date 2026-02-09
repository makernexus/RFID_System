<?php

// List everyone who successfully checked in to the makerspace
// (last 200)
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin', 'MoD']);  // Require admin or MoD role
include 'commonfunctions.php';

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);
  
$selectSQL = "SELECT * FROM rawdata where logEvent = 'Checked In' ORDER BY recNum DESC LIMIT 200;";

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

$result = mysqli_query($con, $selectSQL);
$results = [];

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}

mysqli_close($con);

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Check-In Log</title>
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
      text-transform: uppercase;
      letter-spacing: 0.5px;
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
    
    .photo-cell {
      width: 60px;
    }
    
    .client-photo {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #f0f0f0;
    }
    
    .name-cell {
      font-weight: 600;
      color: #333;
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
    
    .date-cell {
      color: #666;
      font-size: 13px;
      white-space: nowrap;
    }
    
    .event-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      background-color: #4caf50;
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
      
      .stats-container {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }
      
      .table-container {
        overflow-x: auto;
      }
      
      .checkin-table {
        font-size: 12px;
      }
      
      .checkin-table th,
      .checkin-table td {
        padding: 10px 8px;
      }
      
      .photo-cell {
        display: none;
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
            <h1>Recent Check-Ins</h1>
            <p>Last 200 successful check-ins to the makerspace. Click Client ID to view detailed member report.</p>
        </div>
        
        <?php if (count($results) > 0): ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div>
                        <div class="stat-number"><?php echo count($results); ?></div>
                        <div class="stat-label">Check-Ins Shown</div>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="checkin-table">
                    <thead>
                        <tr>
                            <th class="photo-cell"></th>
                            <th>Date & Time</th>
                            <th>Event</th>
                            <th>Client ID</th>
                            <th>First Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td class="photo-cell">
                                    <img src="photo/<?php echo htmlspecialchars($row['clientID']); ?>.jpg" 
                                         alt="Photo" 
                                         class="client-photo"
                                         onerror="this.src='WeNeedAPhoto.png'">
                                </td>
                                <td class="date-cell">
                                    <?php echo htmlspecialchars($row['dateEventLocal']); ?>
                                </td>
                                <td>
                                    <span class="event-badge">
                                        <?php echo htmlspecialchars($row['logEvent']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="rfidonemember.php?clientID=<?php echo urlencode($row['clientID']); ?>" 
                                       class="client-id-link">
                                        <?php echo htmlspecialchars($row['clientID']); ?>
                                    </a>
                                </td>
                                <td class="name-cell">
                                    <?php echo htmlspecialchars($row['firstName']); ?>
                                </td>
                            </tr>
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
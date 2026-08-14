<?php

// Activity Dropoff Report
// Identifies members whose recent visit frequency has dropped 50% or more
// compared to their historical monthly average
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin']);  // Require manager or admin role
include 'commonfunctions.php';

// Cache for 24 hours; use ?regenerate=1 to force a fresh report
$localCacheFileName = "rfidActivityDropoff.cache";
$cacheTime = 86400;  // 24 hours in seconds
$forceRegen = isset($_GET['regenerate']) && $_GET['regenerate'] == '1';

if (!$forceRegen) {
    $cachedHtml = checkCachedFile($localCacheFileName, $cacheTime);
    if ($cachedHtml != "") {
        echo $cachedHtml;
        return;
    }
}

// Start output buffering — everything from here to the bottom will be captured
ob_start();

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

$selectSQL = 
"SELECT 
    h.clientID, h.lastName, h.firstName,
    ROUND(h.avg_monthly_visits, 1) AS historical_monthly_avg,
    COALESCE(r.recent_visits, 0) AS last_30_days_visits,
    ROUND((COALESCE(r.recent_visits, 0) - h.avg_monthly_visits) / h.avg_monthly_visits * 100, 1) AS percent_drop
FROM (
    -- Calculate average monthly visits over the prior 5 months
    SELECT 
        rawdata.clientID,
        clientInfo.lastName,
        clientInfo.firstName,
        COUNT(*) / 5.0 AS avg_monthly_visits
    FROM rawdata
    JOIN clientInfo ON rawdata.clientID = clientInfo.clientID
    WHERE dateEventLocal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
      AND dateEventLocal < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
      AND logEvent = 'Checked In'
      AND clientInfo.dateLastSeen >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    GROUP BY rawdata.clientID
) h
LEFT JOIN (
    -- Calculate visits in the last 30 days
    SELECT 
        rawdata.clientID,
        COUNT(*) AS recent_visits
    FROM rawdata
    WHERE dateEventLocal >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
      AND logEvent = 'Checked In'
    GROUP BY rawdata.clientID
) r ON h.clientID = r.clientID
-- Filter for members who used to come at least twice a month, but dropped by 50%+
WHERE h.avg_monthly_visits >= 2.0 
  AND (COALESCE(r.recent_visits, 0) <= h.avg_monthly_visits * 0.5)
ORDER BY percent_drop ASC;";

$result = mysqli_query($con, $selectSQL);

// Check for query errors (CTEs not supported in MySQL < 8.0)
if (!$result) {
    echo "Query error: " . mysqli_error($con);
    exit();
}

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
  <title>Activity Dropoff Report</title>
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
      background: linear-gradient(135deg, #e53e3e 0%, #c05621 100%);
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
      flex-wrap: wrap;
    }
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #e53e3e;
    }
    
    .stat-number.warn {
      color: #c05621;
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
    
    .dropoff-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .dropoff-table thead {
      background: linear-gradient(135deg, #e53e3e 0%, #c05621 100%);
      color: white;
    }
    
    .dropoff-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .dropoff-table th.num {
      text-align: right;
    }
    
    .dropoff-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s;
    }
    
    .dropoff-table tbody tr:hover {
      background-color: #fff5f5;
    }
    
    .dropoff-table tbody tr:last-child {
      border-bottom: none;
    }
    
    .dropoff-table td {
      padding: 12px 15px;
      font-size: 14px;
      color: #333;
    }
    
    .dropoff-table td.num {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }
    
    .dropoff-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .dropoff-severe {
      background-color: #fff5f5;
      color: #c53030;
    }
    
    .dropoff-moderate {
      background-color: #fffaf0;
      color: #c05621;
    }
    
    .dropoff-significant {
      background-color: #fffff0;
      color: #975a16;
    }
    
    .client-id-link {
      color: #e53e3e;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    
    .client-id-link:hover {
      color: #c05621;
      text-decoration: underline;
    }
    
    .no-results {
      background: white;
      padding: 60px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      color: #666;
    }
    
    .no-results .icon {
      font-size: 48px;
      margin-bottom: 15px;
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
      
      .dropoff-table {
        font-size: 12px;
      }
      
      .dropoff-table th,
      .dropoff-table td {
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
            <h1>Activity Dropoff Report</h1>
            <p>Members whose recent visit frequency has dropped 50% or more compared to their historical monthly average. Only includes members who previously averaged at least 2 visits per month and have been seen in the last 3 months.</p>
        </div>
        
        <?php 
        $dropCount = count($results);
        if ($dropCount > 0): 
        ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div>
                        <div class="stat-number"><?php echo $dropCount; ?></div>
                        <div class="stat-label">Members at Risk</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div>
                        <?php 
                        $severeCount = 0;
                        $moderateCount = 0;
                        foreach ($results as $r) {
                            $d = abs((float)$r['percent_drop']);
                            if ($d >= 100) $severeCount++;
                            elseif ($d >= 75) $moderateCount++;
                        }
                        ?>
                        <div class="stat-number warn"><?php echo $severeCount; ?></div>
                        <div class="stat-label">100% Drop<br>(Zero recent visits)</div>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="dropoff-table">
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th class="num">Historical<br>Monthly Avg</th>
                            <th class="num">Last 30 Days</th>
                            <th class="num">Percent Drop</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): 
                            $pctDrop = (float)$row['percent_drop'];
                            $absDrop = abs($pctDrop);
                            if ($absDrop >= 100) {
                                $badgeClass = 'dropoff-severe';
                            } elseif ($absDrop >= 75) {
                                $badgeClass = 'dropoff-moderate';
                            } else {
                                $badgeClass = 'dropoff-significant';
                            }
                        ?>
                            <tr>
                                <td>
                                    <a href="rfidonemember.php?clientID=<?php echo urlencode($row['clientID']); ?>" 
                                       class="client-id-link">
                                        <?php echo htmlspecialchars($row['clientID']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                                <td class="num"><?php echo htmlspecialchars($row['historical_monthly_avg']); ?></td>
                                <td class="num"><?php echo htmlspecialchars($row['last_30_days_visits']); ?></td>
                                <td class="num">
                                    <span class="dropoff-badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($row['percent_drop']); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="icon">&#9989;</div>
                <p style="font-size: 18px; margin: 0;">
                    No members found with significant activity dropoff
                </p>
                <p style="color: #999; margin-top: 10px;">
                    All active members are maintaining their visit frequency.
                </p>
            </div>
        <?php endif; ?>
        
        <?php
        // Show when this report was generated
        $generatedTime = date('Y-m-d H:i:s');
        ?>
        <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
            <p style="color: #999; font-size: 13px; margin: 0 0 10px 0;">
                Report generated: <?php echo $generatedTime; ?> &mdash; cached for 24 hours
            </p>
            <a href="rfidActivityDropoff.php?regenerate=1" 
               style="display: inline-block; padding: 10px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; transition: opacity 0.2s;"
               onmouseover="this.style.opacity='0.85'"
               onmouseout="this.style.opacity='1'">
                &#8635; Regenerate Report
            </a>
        </div>
    </div>
</body>

</html>
<?php
// Capture everything that was output, write to cache, then send to browser
$html = ob_get_clean();
updateCachedFile($localCacheFileName, $html);
echo $html;
?>
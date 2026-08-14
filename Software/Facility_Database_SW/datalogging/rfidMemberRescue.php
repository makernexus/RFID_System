<?php

// Member Rescue Report
// Identifies new members (joined within last 90 days) who haven't visited
// in 3-6 weeks and are at risk of not returning
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin']);  // Require manager or admin role
include 'commonfunctions.php';

// Cache for 24 hours; use ?regenerate=1 to force a fresh report
$localCacheFileName = "rfidMemberRescue.cache";
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
    ci.clientID,
    ci.firstName,
    ci.lastName,
    ci.dateCreated AS membership_start_date,
    MAX(r.dateEventLocal) AS last_visit_date,
    DATEDIFF(CURDATE(), MAX(r.dateEventLocal)) AS days_since_last_visit,
    COUNT(r.dateEventLocal) AS total_visits_to_date
FROM clientInfo ci
LEFT JOIN rawdata r ON ci.clientID = r.clientID
    AND r.logEvent = 'Checked In'
-- Look only at accounts created in the last 3 months
WHERE ci.dateCreated >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
  AND r.dateEventLocal >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY ci.clientID, ci.firstName, ci.lastName, ci.dateCreated
-- Target members who haven't visited in 3 to 6 weeks
HAVING (days_since_last_visit >= 21 AND days_since_last_visit <= 45)
   OR (last_visit_date IS NULL AND DATEDIFF(CURDATE(), ci.dateCreated) >= 21)
ORDER BY days_since_last_visit DESC, membership_start_date ASC;";

$result = mysqli_query($con, $selectSQL);

// Check for query errors
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
  <title>Member Rescue Report</title>
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
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .page-header {
      background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
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
      color: #dd6b20;
    }
    
    .stat-number.warn {
      color: #c53030;
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
    
    .rescue-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .rescue-table thead {
      background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
      color: white;
    }
    
    .rescue-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .rescue-table th.num {
      text-align: right;
    }
    
    .rescue-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.2s;
    }
    
    .rescue-table tbody tr:hover {
      background-color: #fffaf0;
    }
    
    .rescue-table tbody tr:last-child {
      border-bottom: none;
    }
    
    .rescue-table td {
      padding: 12px 15px;
      font-size: 14px;
      color: #333;
    }
    
    .rescue-table td.num {
      text-align: right;
      font-variant-numeric: tabular-nums;
    }
    
    .urgency-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .urgency-critical {
      background-color: #fff5f5;
      color: #c53030;
    }
    
    .urgency-high {
      background-color: #fffaf0;
      color: #c05621;
    }
    
    .urgency-moderate {
      background-color: #fffff0;
      color: #975a16;
    }
    
    .client-id-link {
      color: #dd6b20;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    
    .client-id-link:hover {
      color: #c05621;
      text-decoration: underline;
    }
    
    .zero-visits {
      color: #c53030;
      font-weight: 600;
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
      
      .rescue-table {
        font-size: 12px;
      }
      
      .rescue-table th,
      .rescue-table td {
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
            <h1>Member Rescue Report</h1>
            <p>New members (joined in last 90 days) who haven't visited in 3–6 weeks. These members are at risk of not returning and may benefit from outreach.</p>
        </div>
        
        <?php 
        $memberCount = count($results);
        if ($memberCount > 0): 
            // Count members with zero visits
            $zeroVisitCount = 0;
            foreach ($results as $r) {
                if ((int)$r['total_visits_to_date'] === 0) $zeroVisitCount++;
            }
        ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div>
                        <div class="stat-number"><?php echo $memberCount; ?></div>
                        <div class="stat-label">Members to Rescue</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div>
                        <div class="stat-number warn"><?php echo $zeroVisitCount; ?></div>
                        <div class="stat-label">Never Visited</div>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="rescue-table">
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Joined</th>
                            <th>Last Visit</th>
                            <th class="num">Days Since<br>Last Visit</th>
                            <th class="num">Total<br>Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): 
                            $daysSince = (int)$row['days_since_last_visit'];
                            $totalVisits = (int)$row['total_visits_to_date'];
                            $lastVisit = $row['last_visit_date'];
                            
                            if ($lastVisit === null) {
                                $urgencyClass = 'urgency-critical';
                            } elseif ($daysSince >= 35) {
                                $urgencyClass = 'urgency-critical';
                            } elseif ($daysSince >= 28) {
                                $urgencyClass = 'urgency-high';
                            } else {
                                $urgencyClass = 'urgency-moderate';
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
                                <td><?php echo htmlspecialchars($row['membership_start_date']); ?></td>
                                <td>
                                    <?php if ($lastVisit === null): ?>
                                        <span class="zero-visits">Never</span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($lastVisit); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="num">
                                    <span class="urgency-badge <?php echo $urgencyClass; ?>">
                                        <?php echo $daysSince; ?> days
                                    </span>
                                </td>
                                <td class="num"><?php echo $totalVisits; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="icon">&#9989;</div>
                <p style="font-size: 18px; margin: 0;">
                    No new members found at risk
                </p>
                <p style="color: #999; margin-top: 10px;">
                    Recent new members are visiting regularly. Great job!
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
            <a href="rfidMemberRescue.php?regenerate=1" 
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
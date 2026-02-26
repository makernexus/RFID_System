<?php

// List everyone who successfully checked in to the makerspace
// (last 200)
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin', 'MoD']);  // Require manager, admin, or MoD role
include 'commonfunctions.php';

$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

// Get search parameters
$searchLastName = isset($_GET["search"]) ? trim($_GET["search"]) : '';
$clientID = isset($_GET["clientID"]) ? trim($_GET["clientID"]) : '';

$searchResults = [];
$results = [];
$hasClientID = false;
$selectedClient = null;

if ($searchLastName !== '' && $clientID === '') {
    // Search for clients by last name
    $con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);
    
    if (mysqli_connect_errno()) {
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
      exit();
    }
    
    $escapedSearch = mysqli_real_escape_string($con, $searchLastName);
    $searchSQL = "SELECT clientID, firstName, lastName, displayClasses, MOD_Eligible, dateLastSeen 
                  FROM clientInfo 
                  WHERE lastName LIKE '%" . $escapedSearch . "%' 
                  ORDER BY lastName, firstName 
                  LIMIT 50";
    
    $searchResult = mysqli_query($con, $searchSQL);
    
    if ($searchResult) {
        while($row = mysqli_fetch_assoc($searchResult)) {
            $searchResults[] = $row;
        }
    }
    
    mysqli_close($con);
} elseif ($clientID !== '') {
    // Get activity for specific client
    $hasClientID = true;
    
    $con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);
    
    if (mysqli_connect_errno()) {
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
      exit();
    }
    
    // Get client info
    $escapedClientID = mysqli_real_escape_string($con, $clientID);
    $clientSQL = "SELECT clientID, firstName, lastName FROM clientInfo WHERE clientID = '" . $escapedClientID . "' LIMIT 1";
    $clientResult = mysqli_query($con, $clientSQL);
    
    if ($clientResult && mysqli_num_rows($clientResult) > 0) {
        $selectedClient = mysqli_fetch_assoc($clientResult);
    }
    
    // Get activity data
    $selectSQL = "SELECT dateEventLocal, firstName, logEvent, logData FROM `rawdata` WHERE clientID = '" . $escapedClientID . "' AND logEvent IN ('checkin denied','checked in','checked out')  ORDER BY dateEventLocal DESC LIMIT 100;";
    
    $result = mysqli_query($con, $selectSQL);
    $results = [];

    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
        }
    }

    mysqli_close($con);
}

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Member Activity Report</title>
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
    
    .search-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .search-label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      color: #333;
      font-size: 16px;
    }
    
    .search-form {
      display: flex;
      gap: 10px;
      align-items: flex-end;
    }
    
    .form-group {
      flex: 0 0 auto;
    }
    
    .search-input {
      padding: 12px 15px;
      width: 250px;
      border: 2px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      transition: border-color 0.2s;
    }
    
    .search-input:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .search-btn {
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
    
    .search-btn:hover {
      transform: translateY(-2px);
    }
    
    .search-results-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .search-result-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .search-result-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .search-result-photo {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #f0f0f0;
      flex-shrink: 0;
    }
    
    .search-result-info {
      flex-grow: 1;
    }
    
    .search-result-name {
      font-size: 16px;
      font-weight: 600;
      color: #333;
      margin: 0 0 5px 0;
    }
    
    .search-result-detail {
      font-size: 13px;
      color: #666;
      margin: 2px 0;
    }
    
    .view-report-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      transition: transform 0.2s;
      white-space: nowrap;
    }
    
    .view-report-btn:hover {
      transform: scale(1.05);
    }
    
    .results-header {
      background: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    .results-header h2 {
      margin: 0;
      color: #333;
      font-size: 20px;
    }
    
    .client-info-card {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .client-photo-large {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #f0f0f0;
    }
    
    .client-details h2 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 24px;
    }
    
    .client-details p {
      margin: 0;
      color: #666;
      font-size: 14px;
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
    
    .event-denied {
      background-color: #f44336;
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
      
      .search-container {
        padding: 15px;
      }
      
      .search-form {
        flex-direction: column;
        gap: 15px;
      }
      
      .search-input {
        width: 100%;
      }
      
      .search-btn {
        width: 100%;
      }
      
      .search-results-grid {
        grid-template-columns: 1fr;
      }
      
      .client-info-card {
        flex-direction: column;
        text-align: center;
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
            <h1>Member Activity Report</h1>
            <p>Search for a member by last name and view their check-in and check-out history</p>
        </div>
        
        <div class="search-container">
            <label class="search-label">Search by Last Name:</label>
            <form class="search-form" method="GET" action="rfidonemember.php">
                <div class="form-group">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Enter last name (partial match)" 
                           value="<?php echo htmlspecialchars($searchLastName); ?>"
                           autofocus>
                </div>
                <button type="submit" class="search-btn">Search</button>
                <?php if ($searchLastName !== '' || $clientID !== ''): ?>
                    <a href="rfidonemember.php" class="search-btn" style="background: #e0e0e0; color: #333; text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if ($searchLastName !== '' && $clientID === ''): ?>
            <?php if (count($searchResults) > 0): ?>
                <div class="results-header">
                    <h2><?php echo count($searchResults); ?> Member<?php echo count($searchResults) != 1 ? 's' : ''; ?> Found</h2>
                </div>
                
                <div class="search-results-grid">
                    <?php foreach ($searchResults as $client): ?>
                        <div class="search-result-card">
                            <img src="photo/<?php echo htmlspecialchars($client['clientID']); ?>.jpg" 
                                 alt="Photo" 
                                 class="search-result-photo"
                                 onerror="this.src='WeNeedAPhoto.png'">
                            <div class="search-result-info">
                                <div class="search-result-name">
                                    <?php echo htmlspecialchars($client['lastName'] . ', ' . $client['firstName']); ?>
                                </div>
                                <div class="search-result-detail">
                                    <strong>ID:</strong> <?php echo htmlspecialchars($client['clientID']); ?>
                                </div>
                                <?php if (!empty($client['dateLastSeen'])): ?>
                                    <div class="search-result-detail">
                                        <strong>Last Seen:</strong> <?php echo htmlspecialchars($client['dateLastSeen']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="rfidonemember.php?clientID=<?php echo urlencode($client['clientID']); ?>" 
                               class="view-report-btn">
                                View Report
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p style="font-size: 18px; margin: 0;">
                        No members found matching "<?php echo htmlspecialchars($searchLastName); ?>"
                    </p>
                    <p style="margin-top: 10px; color: #999;">
                        Try a different search term
                    </p>
                </div>
            <?php endif; ?>
        <?php elseif ($hasClientID): ?>
            <?php if (count($results) > 0): ?>
                <?php
                $firstName = $selectedClient ? $selectedClient['firstName'] : $results[0]['firstName'];
                $lastName = $selectedClient ? $selectedClient['lastName'] : '';
                ?>
                
                <div class="client-info-card">
                    <img src="photo/<?php echo htmlspecialchars($clientID); ?>.jpg" 
                         alt="Member Photo" 
                         class="client-photo-large"
                         onerror="this.src='WeNeedAPhoto.png'">
                    <div class="client-details">
                        <h2><?php echo htmlspecialchars($firstName . ($lastName ? ' ' . $lastName : '')); ?></h2>
                        <p><strong>Client ID:</strong> <?php echo htmlspecialchars($clientID); ?></p>
                        <p><strong>Records Found:</strong> <?php echo count($results); ?> (showing last 100 events)</p>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Event</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <?php
                                $eventClass = 'event-checkin';
                                if (strtolower($row['logEvent']) == 'checked out') {
                                    $eventClass = 'event-checkout';
                                } elseif (strtolower($row['logEvent']) == 'checkin denied') {
                                    $eventClass = 'event-denied';
                                }
                                ?>
                                <tr>
                                    <td class="date-cell">
                                        <?php echo htmlspecialchars($row['dateEventLocal']); ?>
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
                        No activity found for Client ID: <?php echo htmlspecialchars($clientID); ?>
                    </p>
                    <p style="margin-top: 10px; color: #999;">
                        Try a different Client ID or check if the ID is correct
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <p style="font-size: 18px; margin: 0;">
                    Search for a member by last name
                </p>
                <p style="margin-top: 10px; color: #999;">
                    Enter a partial or full last name in the search box above
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
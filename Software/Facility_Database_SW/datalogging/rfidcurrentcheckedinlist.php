<?php

// List everyone who is currently checked in, in alphabetical order.
//
// Sources:
//   - rawdata  : RFID members whose most recent event today is "Checked In"
//   - ovl_visits : online visitors with no checkout recorded yet
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin', 'MoD', 'reception']);  // Current CheckIns is available to all roles
include 'commonfunctions.php';

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

$people = [];

// ---------------------------------------------------------------
// Members currently checked in (from rawdata + clientInfo)
//
// A member is "currently checked in" when their most recent
// "Checked In" / "Checked Out" event today is a "Checked In".
// ---------------------------------------------------------------
$selectMembersSQL = "
SELECT rd.clientID, ci.firstName, ci.lastName, ci.displayClasses, rd.dateEventLocal
FROM rawdata rd
JOIN clientInfo ci ON rd.clientID = ci.clientID
JOIN (
    SELECT clientID, MAX(recNum) AS maxRecNum
    FROM rawdata
    WHERE logEvent IN ('Checked In', 'Checked Out')
      AND dateEventLocal >= CURDATE()
    GROUP BY clientID
) latest ON rd.recNum = latest.maxRecNum AND rd.clientID = latest.clientID
WHERE rd.logEvent = 'Checked In'
ORDER BY ci.lastName ASC, ci.firstName ASC;
";

$resultMembers = mysqli_query($con, $selectMembersSQL);
if ($resultMembers === false) {
    echo "Error querying members: " . mysqli_error($con);
    mysqli_close($con);
    exit();
}

while ($row = mysqli_fetch_assoc($resultMembers)) {
    $people[] = [
        'lastName'       => $row['lastName'],
        'firstName'      => $row['firstName'],
        'source'         => 'Member',
        'clientID'       => $row['clientID'],
        'displayClasses' => $row['displayClasses'],
        'checkedIn'      => $row['dateEventLocal'],
    ];
}

// ---------------------------------------------------------------
// Online visitors currently checked in (from ovl_visits)
//
// A visitor is "currently checked in" unless elapsedHours is the
// sentinel 999, which marks a completed checkout.
// ---------------------------------------------------------------
$selectVisitorsSQL = "
SELECT nameFirst, nameLast, dateCheckinLocal
FROM ovl_visits
WHERE elapsedHours <> 999
   AND dateCheckinLocal >= CURDATE()
ORDER BY nameLast ASC, nameFirst ASC;
";

$resultVisitors = mysqli_query($con, $selectVisitorsSQL);
if ($resultVisitors === false) {
    echo "Error querying visitors: " . mysqli_error($con);
    mysqli_close($con);
    exit();
}

while ($row = mysqli_fetch_assoc($resultVisitors)) {
    $people[] = [
        'lastName'       => $row['nameLast'],
        'firstName'      => $row['nameFirst'],
        'source'         => 'Visitor',
        'clientID'       => null,
        'displayClasses' => '',
        'checkedIn'      => $row['dateCheckinLocal'],
    ];
}

mysqli_close($con);

// ---------------------------------------------------------------
// Sort the combined list alphabetically by last name, then first name.
// ---------------------------------------------------------------
usort($people, function ($a, $b) {
    $cmp = strcasecmp($a['lastName'], $b['lastName']);
    if ($cmp === 0) {
        $cmp = strcasecmp($a['firstName'], $b['firstName']);
    }
    return $cmp;
});

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Currently Checked In</title>
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

    .source-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      background-color: #4caf50;
      color: white;
    }

    .source-badge.visitor {
      background-color: #ff9800;
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
            <h1>Currently Checked In</h1>
            <p>Everyone checked in right now, from RFID members and online visitors, in alphabetical order.</p>
        </div>

        <?php if (count($people) > 0): ?>
            <div class="stats-container">
                <div class="stat-item">
                    <div>
                        <div class="stat-number"><?php echo count($people); ?></div>
                        <div class="stat-label">People Checked In</div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="checkin-table">
                    <thead>
                        <tr>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Display Classes</th>
                            <th>Source</th>
                            <th>Checked In</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($people as $person): ?>
                            <tr>
                                <td class="name-cell">
                                    <?php echo htmlspecialchars($person['lastName']); ?>
                                </td>
                                <td class="name-cell">
                                    <?php echo htmlspecialchars($person['firstName']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($person['displayClasses']); ?>
                                </td>
                                <td>
                                    <span class="source-badge <?php echo ($person['source'] === 'Visitor') ? 'visitor' : ''; ?>">
                                        <?php echo htmlspecialchars($person['source']); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?php echo htmlspecialchars($person['checkedIn']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p style="font-size: 18px; margin: 0;">
                    No one is currently checked in.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>

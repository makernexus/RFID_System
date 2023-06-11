<?php

// Information on the current Manager on Duty
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp

include 'commonfunctions.php';

// -----------------------------------------
// STEP - Checks for IP locking, etc
// -----------------------------------------

allowWebAccess();  // if IP not allowed, then die

// -----------------------------------------
// STEP - Get database credentials and establish connection
// -----------------------------------------

$ini_array = parse_ini_file("rfidconfig.ini", true);
$photoServer = $ini_array["CRM"]["photoServer"];        
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// used in SQL commands needing 00:00 today
$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles")); 

// -----------------------------------------
// STEP - Get current MOD
// -----------------------------------------

$selectSQL = 
    "SELECT r.dateEventLocal, c.clientID, c.firstName, c.pictureURL
    FROM rawdata r join clientInfo c
      ON r.clientID = c.clientID
    WHERE r.logEvent = 'MOD'
    AND r.eventName = 'RFIDLogCheckInOut'
    AND r.dateEventLocal > " . date_format($today, "Ymd") . "
    ORDER BY r.recNum DESC
    LIMIT 1";

$result = mysqli_query($con, $selectSQL);
echo mysqli_error($con);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if (strcmp($row['clientID'], "0") !== 0) {
        // we have an MOD on duty
        $returnMessage['firstName'] = $row["firstName"];
        $returnMessage['photoURL'] = $row['pictureURL'];
        $returnMessage['clientID'] = $row["clientID"];
    } else {
        // there is no MOD on duty
        $returnMessage['firstName'] = "CLOSED";
        $returnMessage['photoURL'] = 'http://rfid.makernexuswiki.com/weareclosed.jpg';
        $returnMessage['clientID'] = "0";
    }
} else {
     // there is no MOD on duty
     $returnMessage['firstName'] = "CLOSED";
     $returnMessage['photoURL'] = 'http://rfid.makernexuswiki.com/weareclosed.jpg';
     $returnMessage['clientID'] = "0";
}

// -----------------------------------------
// STEP - Return the JSON
// -----------------------------------------

echo json_encode($returnMessage);

mysqli_close($con);

return;


?>
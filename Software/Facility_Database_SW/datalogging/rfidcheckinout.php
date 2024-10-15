<?php

// SUBMIT CHECKIN/OUT DATA TO RFID LOGGING DATABASE
// 
// Call with CURL:
/*
 curl -H 'Content-Type: application/x-www-form-urlencoded' 
 -d 'event=RFIDLogging&data={"dateEventLocal":"2024-10-02 16:29:13",
 "deviceFunction":"Check In","clientID":99999911,"firstName":"Test",
 "lastName":"Testing","logEvent":"checkin allowed","logData":"Sunnyvale",
 "MODAction":"No"}
 &published_at=2024-10-04T23:29:18.543Z&coreid=1111113ce9c5a9e5a4f43d' 
 -X POST  <<URL>>/rfidcheckinout.php
*/ 
// If ClientID is currently Checked Out or missing, then add a checkin record
// If ClientID is currently Checked In, then add a checkout record
//
// Assume if a ClientID has not been seen today, then they are checked out
//
// RETURN <ActionTaken> tags containing "Checked In" or "Checked Out"
//
// Mar 2022 updated to take MODRequested JSON field and act on it if value is "Yes".
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp


function debugThis($data) {
    // set to 1 if you want to see a bunch of debug steps
    if (0) {
        echo '<b>' . $data;
    }
}

include 'commonfunctions.php';

// -----------------------------------------
// STEP - Checks for IP locking, etc
// -----------------------------------------

allowWebAccess();  // if IP not allowed, then die

// -----------------------------------------
// STEP - Get database credentials and establish connection
// -----------------------------------------

$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["writeUser"];
$dbPassword = $ini_array["SQL_DB"]["writePassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

// set up SQL connection
$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// used in SQL commands needing 00:00 today
$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$sqlDateToday = date_format($today, "Y-m-d");

// get tomorrow's date for SQL
$tomorrow = new DateTime();
$tomorrow->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$tomorrow->add(new DateInterval('P1D'));
$sqlDateTomorrow = date_format($tomorrow, "Y-m-d");


// -----------------------------------------
// STEP - Get parameters that were sent in
// -----------------------------------------

// standard data from webhook
$datePublishedAt =  cleanInput($_POST["published_at"]);
$coreID =  cleanInput($_POST["coreid"] );
$eventName = cleanInput($_POST["event"]);

// data from the device in JSON format
$postedData =  $_POST["data"];
$myJSON = json_decode($postedData,true);
echo "<p>" . printJSONError(json_last_error());
//var_dump($myJSON); 

// data from the device's JSON
$dateEventLocal = cleanInput($myJSON["dateEventLocal"] );
$deviceFunction =  cleanInput($myJSON["deviceFunction"] );
$firstName =  cleanInput($myJSON["firstName"] );
$lastName = cleanInput($myJSON["lastName"]);
$clientID =  cleanInput($myJSON["clientID"] );
$logEvent =  cleanInput($myJSON["logEvent"]);
$logData =  cleanInput($myJSON["logData"] );

$MODActionRequested = false;
if (strpos(" " . $myJSON["MODRequested"] ,"Yes") == 1) {
    $MODActionRequested = true;
} 

// -----------------------------------------
// STEP - If this was called incorrectly, then fail
// -----------------------------------------

if (strpos(" " . $logEvent,"checkin allowed") != 1) {

    die ("This url is only for checkin allowed events.");  // NOTE EARLY EXIT

}

// -----------------------------------------
// STEP - Log this call to the php script
// -----------------------------------------

$insertEventLogSQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
    $clientID, $firstName, $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
    $deviceFunction, $logEvent, $logData );

if (mysqli_query($con, $insertEventLogSQL)) {
    debugThis("New event log record created successfully");
} else {
    echo "<p>Error: " . $insertEventLogSQL . "<br>" . mysqli_error($con);
}

// -----------------------------------------
// STEP - Determine current status of the clientID
//   a1. are they checked in now?
//   a2. if so, are they MOD now?
//   b. if MODActions, are they MOD eligible?
// -----------------------------------------

// a1. Determine if this person is checked in or out
// by getting most recent status for this clientID 
$isClientCheckedIn = false; 
$lastCheckedInTime = "";
$currentStatusSQL = createCheckInOutStatusSQL($clientID, $sqlDateToday, $sqlDateTomorrow);

$resultCheckin = mysqli_query($con, $currentStatusSQL);
echo mysqli_error($con);

if (mysqli_num_rows($resultCheckin) == 1) {

    $returnedRow = mysqli_fetch_assoc($resultCheckin);
    
    if (strcmp($returnedRow["logEvent"],"Checked In") == 0) {
        // they were found checked in
        $lastCheckedInTime = $returnedRow["dateEventLocal"];
        $isClientCheckedIn = true;
        debugThis("client is checked in");
    }
}

// a2. if they are checked in, are they currently MOD?
$isClientMOD = false;
if ($isClientCheckedIn) {
    // is this person currently MOD?
    $selectMODSQL = createIsMODSQL ($clientID, $sqlDateToday, $sqlDateTomorrow); 

    $result = mysqli_query($con, $selectMODSQL);
    echo mysqli_error($con);

    if (mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);
        // is our clientID the current MOD?
        if (strcmp($clientID, $row['clientID']) == 0 ) {
            $isClientMOD = true;
            debugThis("client is MOD");
        }

    } 
}

// b. if MODAction is asked for, then is person MOD eligible?
$MODEligible = false;
if ($MODActionRequested) {

    $selectMODEligibleSQL = createMODEligibleSQL ($clientID);

    $resultMODEligible = mysqli_query($con, $selectMODEligibleSQL);

    if (mysqli_num_rows($resultMODEligible) == 1) {
        $returnedRow = mysqli_fetch_assoc($resultMODEligible);
        if (strpos(" " . $returnedRow['MOD_Eligible'],"1") == 1) {
            $MODEligible = true;
            debugThis("client is MOD Eligible");
        } 
    }

   
}

// -----------------------------------------
// STEP - Determine what actions we should take
//   a. should they be checked in or out?
//   b. should we try to make them MOD?
// -----------------------------------------

if ($isClientMOD && !$isClientCheckedIn) {
    // this is an internal inconsistency in the database. Anyone who is
    // MOD should also be checked in. We log this inconsistency, in case it happens.
    echo "database inconsistency: client is MOD but checked out";
    $insertEventLogSQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
    $clientID, $firstName, $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
    "rfidcheckinout.php", "Database Inconsistency", "client is MOD but not checked in" );

    if (mysqli_query($con, $insertEventLogSQL)) {
        debugThis("New event log record created successfully");
    } else {
        echo "<p>Error: " . $insertEventLogSQL . "<br>" . mysqli_error($con);
    }
}

// We have to decide what to do for a/ check in or out; b/ MOD action
// The two variables have these values 
//
//   value  checkinout Action  /     MOD action
//    -1      check out        /      go off duty
//     0      no action        /      no action
//     1      check in         /      go on duty
$todoCheckInOutAction = 0;
$todoMODAction = 0;

if (!$MODActionRequested) {
    // no MOD action requested, so normal check in/out
    debugThis("No Mod Action requested");
    if($isClientCheckedIn) {
        // check them out
        $todoCheckInOutAction = -1;
        if ($isClientMOD) {
            debugThis ("MOD will be forced Off Duty");
            // if they are MOD then also go off duty
            $todoMODAction = -1;
        }
    } else {
        // check them in
        $todoCheckInOutAction = 1;
    }
} else {
    // MOD Action Requested
    if ($isClientMOD) {
        // to be MOD they should be checked in so this invokation is 
        // for a check OUT and they go off duty as MOD (in this case
        // the shop should be closing)
        $todoCheckInOutAction = -1;
        $todoMODAction = -1;
    } else {
        // they want to go on duty as MOD
        $todoMODAction = 1;
        if (!$isClientCheckedIn) {
            // they are not checked in, so we need to do that too
            $todoCheckInOutAction = 1;
        }
    }
}

// -----------------------------------------
// STEP - Do the needed check in/out work
// -----------------------------------------

$returnMessageCheckInOutValue = "no change"; 

if ($todoCheckInOutAction == -1) {
    // Check them OUT
    debugThis("checking out");
    // How long have they been in the makerspace?
    $minutesInSpace = round((strtotime($dateEventLocal) - strtotime($lastCheckedInTime) )/60);

    // construct the SQL
    $checkInOutSQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
        $clientID, $firstName, $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
        "rfidcheckin.php", "Checked Out", $minutesInSpace );

    $returnMessageCheckInOutValue = "Checked Out";

} else {

    if ($todoCheckInOutAction == 1) {
        // Check them IN
        debugThis("checking in");
        // construct the SQL
        $checkInOutSQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
            $clientID, $firstName, $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
            "rfidcheckin.php", "Checked In", "" );

        $returnMessageCheckInOutValue = "Checked In";
    }

}

// execute the check in/out SQL
if (mysqli_query($con, $checkInOutSQL)) {
    //echo "<p>New record created successfully";
} else {
    echo "<p>Error: " . $checkInOutSQL . "<br>" . mysqli_error($con);
}

// -----------------------------------------
// STEP - Do the needed MOD on / off duty work
// -----------------------------------------

$returnMessageMODValue = "No Change";

if ($todoMODAction == -1) {
    // Going OFF duty
    debugThis("MOD going Off Duty");
    // use clientID 0 and firstName null
    $insertMODOffDutySQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
        "0", "", $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
        $deviceFunction, "MOD", $logData );

    // log device event
    if (mysqli_query($con, $insertMODOffDutySQL)) {
        $returnMessageMODValue = "Off Duty";
    } else {
        echo "<p>Error: " . $insertMODOffDutySQL . "<br>" . mysqli_error($con);
    }

} else {

    if ($todoMODAction == 1) {
        // Going ON duty
        debugThis("MOD going On Duty");
        if ($MODEligible) {

            // They will become MOD
            $insertMODOnDutySQL = createRawDataInsertSQL ($dateEventLocal, $coreID, 
                $clientID, $firstName, $datePublishedAt, $eventName, $_SERVER['REMOTE_ADDR'],
                $deviceFunction, "MOD", $logData );

            // log device event
            if (mysqli_query($con, $insertMODOnDutySQL)) {
                $returnMessageMODValue = "On Duty";
            } else {
                echo "<p>Error: " . $insertMODOnDutySQL . "<br>" . mysqli_error($con);
            }

        } else {

            $returnMessageMODValue = "No Change";

        }
    }
}

// -----------------------------------------
// STEP - Respond to the client
// -----------------------------------------
// The RFID device will parse the return and show this to the user.
$returnMessageCheckInOut = "<ActionTaken>" . $returnMessageCheckInOutValue . "</ActionTaken>"; 
$returnMessageMOD = "<MODAction>" . $returnMessageMODValue . "</MODAction>";
$returnMessage = $returnMessageCheckInOut . $returnMessageMOD;
echo $returnMessage;  

// -----------------------------------------
// STEP - Update the client info table
//   a. might be new entry we haven't seen before
//   b. might be a name change that we need to update
// -----------------------------------------

$clientInfoSQL = createClientInfoInsertSQL ($clientID, $lastName, $firstName, $dateEventLocal);

if (mysqli_query($con, $clientInfoSQL)) {
    //echo "<p>update/insert ran successfully";
} else {
    echo "<p>Error: " . "<br>" . mysqli_error($con);
}

// -----------------------------------------
// STEP - And now we are done
// -----------------------------------------
mysqli_close($con);

return;

//------------------------------------------------------------------------
//   FUNCTIONS BELOW
//------------------------------------------------------------------------

// Logging SQL
function createRawDataInsertSQL ($dateEventLocal, $coreID, $clientID, $firstName, $datePublishedAt, $eventName, $ipAddress, $deviceFunction, $logEvent, $logData ) {
    $insertEventLogSQL = 
        "INSERT INTO `rawdata`
            (`dateEventLocal`, `coreID`, `deviceFunction`, `clientID`, firstName, `eventName`, `logEvent`, `logData`, `datePublishedAt`,ipAddress) 
        VALUES ('<<dateEventLocal>>', '<<coreID>>', '<<deviceFunction>>', '<<clientID>>', '<<firstName>>', '<<eventName>>', '<<logEvent>>', '<<logData>>','<<datePublishedAt>>','<<ipAddress>>')";

        // put common values into sql
        $insertEventLogSQL = str_replace("<<dateEventLocal>>", $dateEventLocal, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<coreID>>", $coreID, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<clientID>>", $clientID, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<firstName>>", $firstName, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<datePublishedAt>>", $datePublishedAt, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<eventName>>", $eventName, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<ipAddress>>", $_SERVER['REMOTE_ADDR'], $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<deviceFunction>>", $deviceFunction, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<logEvent>>", $logEvent, $insertEventLogSQL);
        $insertEventLogSQL = str_replace("<<logData>>", $logData, $insertEventLogSQL);

    return $insertEventLogSQL;
}

// Checkinout status SQL
function createCheckInOutStatusSQL ($clientID, $sqlDateToday, $sqlDateTomorrow) {
    $currentStatusSQL = 
    "SELECT * FROM rawdata 
     WHERE clientID = '<<clientID>>' 
       AND logEvent in ('Checked In','Checked Out')
       AND dateEventLocal BETWEEN CONVERT('<<todaydate>>', DATE) and CONVERT('<<tomorrowdate>>', DATE)
    ORDER BY recNum DESC 
    LIMIT 1";
    $currentStatusSQL = str_replace("<<clientID>>", $clientID, $currentStatusSQL);
    $currentStatusSQL = str_replace("<<todaydate>>", $sqlDateToday, $currentStatusSQL);
    $currentStatusSQL = str_replace("<<tomorrowdate>>", $sqlDateTomorrow, $currentStatusSQL);
    return $currentStatusSQL;
} 

// IsClientMOD status SQL
function createIsMODSQL ($clientID,$sqlDateToday, $sqlDateTomorrow) {
    $selectMODSQL = "SELECT dateEventLocal, clientID, firstName
    FROM rawdata
    WHERE logEvent = 'MOD'
        AND eventName = 'RFIDLogCheckInOut'
        AND dateEventLocal BETWEEN CONVERT('<<todaydate>>', DATE) and CONVERT('<<tomorrowdate>>', DATE)
   ORDER BY recNum DESC
    LIMIT 1";
    $selectMODSQL = str_replace("<<todaydate>>", $sqlDateToday, $selectMODSQL);
    $selectMODSQL = str_replace("<<tomorrowdate>>", $sqlDateTomorrow, $selectMODSQL);
    return $selectMODSQL;
}

// IsClient MOD Eligible SQL
function createMODEligibleSQL ($clientID) {
    $selectMODEligibleSQL = 
        "SELECT * FROM clientInfo
         WHERE clientID = '<<clientID>>'";
    $selectMODEligibleSQL = str_replace("<<clientID>>", $clientID, $selectMODEligibleSQL);
    return $selectMODEligibleSQL;
}

// update Client Info SQL
function createClientInfoInsertSQL ($clientID, $lastName, $firstName, $dateEventLocal) {
    $clientInfoSQL = "CALL sp_insert_update_clientInfo(<<CLIENTID>>,'<<FIRSTNAME>>',
        '<<LASTNAME>>','<<DATELASTSEEN>>',<<ISCHECKEDIN>>);";
    $clientInfoSQL = str_replace("<<CLIENTID>>", $clientID, $clientInfoSQL);
    $clientInfoSQL = str_replace("<<LASTNAME>>", $lastName, $clientInfoSQL);
    $clientInfoSQL = str_replace("<<FIRSTNAME>>", $firstName, $clientInfoSQL);
    $clientInfoSQL = str_replace("<<DATELASTSEEN>>", $dateEventLocal, $clientInfoSQL);
    $clientInfoSQL = str_replace("<<ISCHECKEDIN>>", "0", $clientInfoSQL);
    return $clientInfoSQL;
}


// Pass in a string headed for the db and clean it. This isn't
// perfect, just meant to hold off some badness
function cleanInput ($data) {
	
	$baditems = array("select ","update ","delete ","`","insert ","alter ", "drop ");
	$data = str_ireplace($baditems, "[] ",$data);
	return $data;
}


// Print any JSON error
function printJSONError ($errorCode) {

	switch ($errorCode) {
        case JSON_ERROR_NONE:
            //echo ' - No JSON errors';
        break;
        case JSON_ERROR_DEPTH:
            echo ' JSON - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo 'JSON - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' JSON - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' JSON - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' JSON - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            echo ' JSON - Unknown error';
        break;
    };
    
}



?>
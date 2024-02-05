<?php

// Purpose: Online Visitor Log check in out code 
// Author: Jim Schrempp
// Copywrite: 2024 Maker Nexus
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
// This code is called from a web form or a QR code. It checks in or checks out a visitor.
// The code is called with a visitID from a QR code or from a web form. If the visitID is -1, then the
// code is being called from a web form. If the visitID is 0, then the code is being called from a bare URL
// and the code will serve up the HTML form. If the visitID is a number, then the code is being called
// from a QR code and the code will check in or check out the visitor.
//
// Date: 2024-02-04
//

include 'OVLcommonfunctions.php';

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d H:i:s");  // used in SQL statements

$OVLdebug = false; // set to true to see debug messages 
debugToUser( "OVLdebug is active. " . $nowSQL .  "<br>");

logfile(">>>----- OVLcheckinout.php called");

// write php errors to the log file
ini_set('log_errors', 1);
ini_set('error_log', 'OVLlog.txt');


allowWebAccess();  // if IP not allowed, then die

// get the HTML skeleton
$html = file_get_contents("OVLcheckinout.html");
if (!$html){
  die("unable to open file");
}

// Get the database connection info from the ini file
$ini_array = parse_ini_file("OVLconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["writeUser"];
$dbPassword = $ini_array["SQL_DB"]["writePassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    debugToUser(  "Failed to connect to MySQL: " . mysqli_connect_error());
    logfile("Failed to connect to MySQL: " . mysqli_connect_error());
}


// previousVisitNum 
//    -1: post data came from the form, human input
//     0: request came from a URL with no previous visit
//   num: data came from a QR code with the previous visit number in it
$previousVisitNum = 0;
if (isset($_GET["vid"])) {
    // this would come from a QR code
    $previousVisitNum = cleanInput($_GET["vid"]);
} elseif (isset($_POST["previousVisitNum"])) {
    // this would come from the form
    $previousVisitNum = cleanInput($_POST["previousVisitNum"]);
} 
#if vid is not a number, then exit
if (!is_numeric($previousVisitNum)) {
    echoMessage( "visitID: " . $previousVisitNum . " is not a number. Exiting.");
    logfile("visitID: " . $previousVisitNum . " is not a number. Exiting.");
    exit;
} else  {
    //echo "visitID: " . $previousVisitNum . " has been received. Thank you.";
    logfile("visitID: " . $previousVisitNum . " has been received.");
}


$nameFirstArray = array();
$nameLastArray = array();
$howDidYouHear = "";
$hasSignedWaiver = 0;
$numPeople = 1;
$email = "";
$phone = "";
$visitReason = "";

if ($previousVisitNum == -1) {
    // this is a post from the form, get the data

    // Get the posted data
    $nameFirstArray =  $_POST["nameFirst"];
    $nameLastArray =  $_POST["nameLast"];
    $howDidYouHear = cleanInput($_POST["howDidYouHear"]);
    $hasSignedWaiver = cleanInput($_POST["hasSignedWaiver"]);
    $numPeople = cleanInput($_POST["numPeople"]);

    $email = "";
    if (isset($_POST["email"])) {
        $email = cleanInput($_POST["email"]);
    } 

    $hasSignedWaiver = 0;
    if (isset($_POST["hasSignedWaiver"])) {
        $hasSignedWaiver = $_POST['hasSignedWaiver'];
    } 

    $howDidYouHear = "";
    if (isset($_POST["howDidYouHear"])) {
        $howDidYouHear = cleanInput($_POST["howDidYouHear"]);
    }

    // Turn visit reason array into a pipe delimited string
    $visitReason = "";
    if (isset($_POST["visitReason"])) {
        $visitReasonArray = $_POST["visitReason"];
        if (is_array($visitReasonArray)) {
            // make one string of the reasons
            foreach ($visitReasonArray as $value) {
                $visitReason .= $value . "| ";
            }
        }
        // remove trailing pipe and space
        $visitReason = rtrim($visitReason, "| ");

        $visitReason = cleanInput($visitReason); // prevent attacks
    }
}

// based on $previousVisitNum:
//   -1: post data came from the form, human input
//    0: request came from a URL with no previous visit
//  num: data came from a QR code with the previous visit number in it
//
switch ($previousVisitNum) {
    
    case -1:
        // This came from the form, 
        // insert a new visit into the database for each person in the form

        # the first person must be populated
        if (trim($nameFirstArray[0]) == "" or trim($nameLastArray[0]) == "") {
            echoMessage("First and last name are required. No action taken.");
            logfile("No name entered. No action taken.");
            exit;
        }

        # add to database for each person
        $numAdded = 0;
        for ($i = 0; $i < $numPeople; $i++) {
            // only add if first and last name are populated
            if (trim($nameFirstArray[$i]) != "" and trim($nameLastArray[$i]) != "") {
                $numAdded++;
                if ($numAdded > 1) {
                    $email = "";
                    $phone = "";
                }
                insertNewVisitInDatabase($con, $nowSQL, $nameFirstArray[$i], $nameLastArray[$i], $email, $phone,
                    $visitReason, 0, $howDidYouHear);
            }
        }
        break;

    case 0:
        // request from a bare URL, serve up the HTML form
        $html = file_get_contents("OVLcheckinout.html");
        if (!$html){
            //logfile("unable to open file");
            die("unable to open file");
        }
        // send the HTML
        echo $html;
        break;

    default:
        // we have a previousVisitNum so this is either a checkout, 
        // or a new checkin from a repeat visitor with QR code

        // is the visitID in the database since the start of today without a checkout?
        $currentCheckInData = getCurrentCheckin($con, $previousVisitNum);
        $currentCheckInRecNum = $currentCheckInData["currentCheckInRecNum"];
        
        if ($currentCheckInRecNum == -1) {

            echoMessage("No previous record found for visitID: " . $previousVisitNum . ".<br> No action taken.<br>");
            echoMessage( "Please use the web form to check in.");
            logfile("No previous record found for visitID: " . $previousVisitNum . ". No action taken.");
            die;

        } elseif ($currentCheckInRecNum == 0) {

            // this is a new checkin today for an existing visitor
            insertNewVisitInDatabase($con, $nowSQL, $currentCheckInData["nameFirstFromDB"], $currentCheckInData["nameLastFromDB"],
                     "", "", "", $previousVisitNum, "");
            echoMessage( "Checked In with previousVisitNum: " . $previousVisitNum . ".");

        } else {

            // we have a currentCheckInRecNum so this is a checkout
            checkoutVisitInDatabase($con, $nowSQL, $currentCheckInRecNum);
            echoMessage( "Checked Out");

        }

        // close the database connection
        mysqli_close($con);

        // end the php
        die;
}
// END OF MAIN CODE


//------------------------------------------------------------------------
// Insert a new visit into the database
function insertNewVisitInDatabase($con, $nowSQL, $nameFirst, $nameLast, $email, $phone, $visitReason, $previousVisitNum, $howDidYouHear) {

    $labelNeedsPrinting = 1;
    if ($previousVisitNum != 0) {
        $labelNeedsPrinting = 0;  // don't print a label badge for a person using a QR code
    }

    // use this format to avoid SQL injection attacks
    $sql = "INSERT INTO ovl_visits (nameFirst, nameLast, email, phone, visitReason, previousRecNum, "
        . " dateCreatedLocal, dateCheckinLocal, labelNeedsPrinting, howDidYouHear) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "sssssissis", $nameFirst, $nameLast, $email, $phone, $visitReason, $previousVisitNum, 
            $nowSQL, $nowSQL, $labelNeedsPrinting, $howDidYouHear);

    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        logfile("Error on exec: " . mysqli_stmt_error($stmt));
        debugToUser(  "Error on exec: " . mysqli_stmt_error($stmt) . "<br>");
    } else {
        // update 
        debugToUser(  "New record created successfully" . "<br>");
        logfile("New record created successfully");
    }

    mysqli_stmt_close($stmt);
}

//------------------------------------------------------------------------
// check the visitor out
function checkoutVisitInDatabase($con, $nowSQL, $visitID) {

    $sql = "SELECT dateCheckinLocal FROM ovl_visits " 
        . " WHERE recNum = " . $visitID;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        debugToUser(  "Error: " . $sql . "<br>" . mysqli_error($con));
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        exit;
    } else {
        $row = mysqli_fetch_assoc($result);
        $dateCheckinLocal = $row["dateCheckinLocal"];
    }

    // calculate elapsed time
    $dateCheckinLocal = new DateTime($dateCheckinLocal);
    $dateCheckinLocal->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $dateCheckoutLocal = new DateTime($nowSQL);
    $dateCheckoutLocal->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $interval = $dateCheckinLocal->diff($dateCheckoutLocal);
    $elapsedTime = $interval->format('%h');

    // update the existing visit to check the visitor out
    $sql = "UPDATE ovl_visits SET "
        . " dateUpdated = '" . $nowSQL . "',"
        . " dateUpdatedLocal = '" . $nowSQL . "',"
        . " dateCheckoutLocal = '" . $nowSQL . "',"
        . " elapsedHours = " . $elapsedTime . ","
        . " labelNeedsPrinting = 0"
        . " WHERE recNum = " . $visitID;

    debugToUser(  "sql: " . $sql);

    $result = mysqli_query($con, $sql);
    if (!$result) {
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        debugToUser(  "Error: " . $sql . "<br>" . mysqli_error($con));
    } else {
        // update 
        debugToUser(  "Record updated successfully");
        logfile("Record updated successfully");
    }
}

//------------------------------------------------------------------------
// See if the visitID is currently checked in.
// Returns several fields.
// Based on currentCheckInRecNum:
//    -1: no record found in database. 
//     0: record found in checked OUT state
//   num: record found in checked IN state
function getCurrentCheckin ($con, $visitID){

    $today = new DateTime(); 
    $today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $todaySQL = $today->format("Y-m-d"); // date only, no time

    $currentCheckInRecNum = -1;  // default to no record found

    // get the most recent occurrence for this visitor
    $sql = "SELECT max(recNum) as maxRecNum FROM ovl_visits " 
        . " WHERE (recNum = " . $visitID . " OR previousRecNum = " . $visitID . ")";

    debugToUser("sql: " . $sql . "<br>");

    $result = mysqli_query($con, $sql);
    if (!$result) {
        debugToUser(  "Error: " . $sql . "<br>" . mysqli_error($con));
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        die;
    } else {
        if (mysqli_num_rows($result) == 0) {
            debugToUser(  "Parameter error 1: Old QR code? No previous record found for visitID: " . $visitID . " <br>");
            $maxRecNum = -1;
        } else {
            $row = mysqli_fetch_assoc($result);
            $maxRecNum = $row["maxRecNum"];

            if ($maxRecNum == "") {
                debugToUser(  "Parameter error 2: Old QR code? No previous record found for visitID: " . $visitID . " <br>");
                $maxRecNum = -1;
            }

        }
    }       
    
    // if we have a previous visit, get its data
    if ($maxRecNum > 0) {

        // we have a previous visit, get its data
        $sql = "SELECT recNum, nameLast, nameFirst, email, phone, dateCheckinLocal, dateCheckoutLocal FROM ovl_visits"
                . " WHERE recNum = " . $maxRecNum;

        debugToUser("sql: " . $sql . "<br>");

        $result = mysqli_query($con, $sql);
        if (!$result) {
            debugToUser(  "Error: " . $sql . "<br>" . mysqli_error($con) );
            logfile("Error: " . $sql . "<br>" . mysqli_error($con));
            die;
        } 
        # if results are empty, then we have a problem since we just got this recNum from the database
        if (mysqli_num_rows($result) == 0) {
            logfile("Internal Error: No record found for recNum: " . $maxRecNum . ". No action taken.");
            echo "Internal Error: No record found for recNum: " . $maxRecNum . ". No action taken.";
            die;
        }

        $row = mysqli_fetch_assoc($result);

        $checkinDate = date_create($row["dateCheckinLocal"]);
        $checkinDate->setTimeZone(new DateTimeZone("America/Los_Angeles"));

        // was this record from before today?
        if ($checkinDate->format('Y-m-d') < $today->format('Y-m-d')) {

            // dateCheckinLocal is from yesterday or before
            debugToUser(  "No record found from today" . "<br>");
            $currentCheckInRecNum = 0; // should add a new record

        } else {

            // dateCheckinLocal is from today. Is there a checkout date?
            if ($row["dateCheckoutLocal"] == "0000-00-00 00:00:00") {
                // there is an open checkin for today
                $row = mysqli_fetch_assoc($result);
                $currentCheckInRecNum = $maxRecNum;  // should update this record 
            } else {
                debugToUser( "No open checkin from today" . "<br>");
                $currentCheckInRecNum = 0; // should add a new record
            }

        }
    } 

    $returnThis = array(
                "currentCheckInRecNum" => $currentCheckInRecNum, 
                "nameLastFromDB" =>$row["nameLast"], 
                "nameFirstFromDB" => $row["nameFirst"],
                "email" => $row["email"], 
                "phone" => $row["phone"]
                ) ;
    return $returnThis;
}

//------------------------------------------------------------------------
// Log message to a rolling log file
//
function logfile($logEntry) {
    // rolling log file set up
    $logFile = 'OVLlog.txt';
    $maxSize = 50000; // Maximum size of the log file in bytes
    $backupFile = 'OVLlog_backup_' . date('Y-m-d') . '.txt'; 

    // Check if the log file is larger than the maximum size
    if (filesize($logFile) > $maxSize) {
        // Rename the log file to the backup file
        rename($logFile, $backupFile);
    }

    // add a carriage return to the log entry
    $logEntry = $logEntry . "\n\r";
    // add a date/time stamp to the log entry
    $logEntry = date('Y-m-d H:i:s') . " " . $logEntry;

    // Write to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

//------------------------------------------------------------------------
// Pass in a string headed for the db and clean it. This isn't
// perfect, just meant to hold off some badness
function cleanInput ($data) {
	
	$baditems = array("select ","update ","delete ","`","insert ","alter ", "drop ", "'","`","&",";");
	$data = str_ireplace($baditems, "[] ",$data);
	return $data;
}

//-------------------------------------
// Echo a string to the user for debugging
// only if $OVLdebug is true
// otherwise, do nothing
function debugToUser ($data) {
    global $OVLdebug;
    if ($OVLdebug){
        echo "<br>" . $data . "<br>";
    }
}

// -------------------------------------
// Send message back to user
function echoMessage($msg) {
    echo "<H1>" . $msg . "</H1>";
    logfile($msg);
    exit;
}

?>
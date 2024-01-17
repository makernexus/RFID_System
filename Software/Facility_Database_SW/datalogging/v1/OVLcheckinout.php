<?php

// Purpose: Online Visitor Log check in out code 
// Author: Jim Schrempp
// Copywrite: 2024 Maker Nexus
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
//
// Date: 2024-10-10
//

include 'OVLcommonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

// get the HTML skeleton
$html = file_get_contents("OVLcheckinout.html");
if (!$html){
  die("unable to open file");
}

// Get the data
$ini_array = parse_ini_file("OVLconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["writeUser"];
$dbPassword = $ini_array["SQL_DB"]["writePassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    logfile("Failed to connect to MySQL: " . mysqli_connect_error());
}

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d H:i:s");

// posted data
$nameFirst =  cleanInput($_POST["nameFirst"]);
$nameLast =  cleanInput($_POST["nameLast"] );
$howDidYouHear = cleanInput($_POST["howDidYouHear"]);
$hasSignedWaiver = cleanInput($_POST["hasSignedWaiver"]);

$visitReason = "";
if (isset($_POST["visitReason"])) {
    $visitReason = cleanInput($_POST["visitReason"]);
    echo "visitReason: " . $visitReason . " has been received.";
}

$email = "";
if (isset($_POST["email"])) {
    $email = cleanInput($_POST["email"]);
} 

$phone = "";
if (isset($_POST["phone"])) {
    $phone = cleanInput($_POST["phone"]);
}

// previousVisitNum 
//    -1: post data came from the form, human input
//     0: request came from a URL with no previous visit
//   num: data came from a QR code with the previous visit number in it
$previousVisitNum = 0;
if (isset($_GET["vid"])) {
    // this would come from a QR code
    $previousVisitNum = cleanInput($_GET["vid"]);
} 
if (isset($_POST["previousVisitNum"])) {
    // this would come from the form
    $previousVisitNum = cleanInput($_POST["previousVisitNum"]);
} 
#if vid is not a number, then exit
if (!is_numeric($previousVisitNum)) {
    echo "visitID: " . $previousVisitNum . " is not a number. Exiting.";
    logfile("visitID: " . $previousVisitNum . " is not a number. Exiting.");
    exit;
} else  {
    echo "visitID: " . $previousVisitNum . " has been received. Thank you.";
    logfile("visitID: " . $previousVisitNum . " has been received.");
}

switch ($previousVisitNum) {
    case -1:
        // if visitid =-1, then this came from the form, add a record to the database
        echo "insert new";
        // insert the new visit into the database
        $previousVisitNum = 0;
        insertNewVisitInDatabase($con, $nowSQL, $nameFirst, $nameLast, $email, $phone, $visitReason, $previousVisitNum);
        break;

    case 0:
        // request from a bare URL, serve up the HTML form
        $html = file_get_contents("OVLcheckinout.html");
        if (!$html){
            logfile("unable to open file");
            die("unable to open file");
        }
        // send the HTML
        echo $html;
        break;

    default:
        // we have a previousVisitNum so this is either a checkout, or a new checkin from a repeat visitor
        echo "previousVisitNum: " . $previousVisitNum . " has been received.";
        // is the visitID in the database from since the start of the day without a checkout?
        $currentCheckInRecNum = getCurrentCheckin($con, $previousVisitNum);
        // if no records in result, then this is a new checkin
        if ($currentCheckInRecNum == 0) {
            // this is a new checkin
            // insert the new visit into the database
            insertNewVisitInDatabase($con, $nowSQL, $nameFirst, $nameLast, $email, $phone, $visitReason, $previousVisitNum);
            echo "insert new with previousVisitNum: " . $currentCheckInRecNum . ".";
        } else {
            // this is a checkout
            updateVisitInDatabase($con, $nowSQL, $currentCheckInRecNum);
            echo "checkout";
        }

        // close the database connection
        mysqli_close($con);

        // end the php
        exit;
}

function insertNewVisitInDatabase($con, $nowSQL, $nameFirst, $nameLast, $email, $phone, $visitReason, $previousVisitNum) {


    $sql = "INSERT INTO ovl_visits SET"
        . " nameFirst = '" . $nameFirst . "',"
        . " nameLast = '" . $nameLast . "'," 
        . " email = '" . $email . "', "
        . " phone = '" . $phone . "'," 
        . " visitReason = '" . $visitReason . "',"
        . " previousRecNum = " . $previousVisitNum . ","
        . " dateCreatedLocal = '" . $nowSQL . "',"
        . " dateCheckinLocal = '" . $nowSQL  . "'";

    echo "sql: " . $sql;

    $result = mysqli_query($con, $sql);
    if (!$result) {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
    } else {
        // update 
        echo "New record created successfully";
        logfile("New record created successfully");
    }
}

function updateVisitInDatabase($con, $nowSQL, $visitID) {

    $sql = "SELECT dateCheckinLocal FROM ovl_visits " 
        . " WHERE recNum = " . $visitID;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
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


    $sql = "UPDATE ovl_visits SET "
        . " dateUpdated = '" . $nowSQL . "',"
        . " dateUpdatedLocal = '" . $nowSQL . "',"
        . " dateCheckoutLocal = '" . $nowSQL . "',"
        . " elapsedHours = " . $elapsedTime . ","
        . " labelNeedsPrinting = 0"
        . " WHERE recNum = " . $visitID;

    echo "sql: " . $sql;

    $result = mysqli_query($con, $sql);
    if (!$result) {
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
    } else {
        // update 
        echo "Record updated successfully";
        logfile("Record updated successfully");
    }
}

function getCurrentCheckin ($con, $visitID){
    $today = new DateTime();
    $today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $todaySQL = $today->format("Y-m-d"); // date only, no time

    $sql = "SELECT max(recNum) as recNum FROM ovl_visits WHERE "
            . "(recNum = " . $visitID . " OR previousRecNum = " . $visitID . ")"
            . " AND dateCreated > '" . $todaySQL . "'"
            . " AND dateCheckoutLocal = '0000-00-00 00:00:00'";

    echo "sql: " . $sql;

    $result = mysqli_query($con, $sql);
    if (!$result) {
        echo "Error: " . $sql . "<br>" . mysqli_error($con);
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        exit;
    } 
    # if results are empty, then there is no open checkin from today
    if (mysqli_num_rows($result) == 0) {
        $currentCheckInRecNum = 0;
    } else {
        $row = mysqli_fetch_assoc($result);
        $currentCheckInRecNum = $row["recNum"];
    }
    return $currentCheckInRecNum;
}

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

    // Write to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

//------------------------------------------------------------------------
// Pass in a string headed for the db and clean it. This isn't
// perfect, just meant to hold off some badness
function cleanInput ($data) {
	
	$baditems = array("select ","update ","delete ","`","insert ","alter ", "drop ");
	$data = str_ireplace($baditems, "[] ",$data);
	return $data;
}

?>
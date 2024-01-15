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

include 'commonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

// get the HTML skeleton
$html = file_get_contents("OVLcheckinout.html");
if (!$html){
  die("unable to open file");
}

// Get the data
$ini_array = parse_ini_file("OVLconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    logfile("Failed to connect to MySQL: " . mysqli_connect_error());
}

// parse http variables into php variables

$previousVisitNum = 0;
if (isset($_GET["vid"])) {
    $previousVisitNum = cleanInput($_GET["vid"]);
} 
if (isset($_POST["previousVisitNum"])) {
    $previousVisitNum = cleanInput($_POST["previousVisitNum"]);
} 

// posted data
$nameFirst =  cleanInput($_POST["nameFirst"]);
$nameLast =  cleanInput($_POST["nameLast"] );
$howDidYouHear = cleanInput($_POST["howDidYouHear"]);
$hasSignedWaiver = cleanInput($_POST["hasSignedWaiver"]);

$visitReason = "";
if (isset($_POST["visitReason"])) {
    $visitReason = cleanInput($_POST["visitReason"]);
}

$email = "";
if (isset($_POST["email"])) {
    $email = cleanInput($_POST["email"]);
} 

$phone = "";
if (isset($_POST["phone"])) {
    $phone = cleanInput($_POST["phone"]);
}

$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));


# if vid is not set, then exit
if (!isset($previousVisitNum)) {
    echo "No visitID received. Exiting.";
    logfile("No visitID received. Exiting.");
    exit;
} else {
    #if vid is not a number, then exit
    if (!is_numeric($previousVisitNum)) {
        echo "visitID: " . $previousVisitNum . " is not a number. Exiting.";
        logfile("visitID: " . $previousVisitNum . " is not a number. Exiting.");
        exit;
    } else  {
        echo "visitID: " . $previousVisitNum . " has been received. Thank you.";
        logfile("visitID: " . $previousVisitNum . " has been received.");
    }
}

switch ($previousVisitNum) {
    case -1:
        // if visitid =-1, then this came from the form, add a record to the database
        echo "insert new";
        // insert the new visit into the database
        insertNewVisitInDatabase($con, $nameFirst, $nameLast, $email, $phone, $visitReason);

        $result = mysqli_query($con, $sql);
        if (!$result) {
            echo "Error: " . $sql . "<br>" . mysqli_error($con);
            logfile("Error: " . $sql . "<br>" . mysqli_error($con));
            exit;
        } else {
            // update 
            echo "New record created successfully";  // xxx
            logfile("New record created successfully");
        }
        break;

    case 0:
        // Serve up the HTML form
        $html = file_get_contents("OVLcheckinout.html");
        if (!$html){
            logfile("unable to open file");
            die("unable to open file");
        }
        // send the HTML
        echo $html;

        break;

    default:
        // we have a visitID so this is either a checkout, or a new checkin from a repeat visitor
        echo "previousVisitNum: " . $previousVisitNum . " has been received.";
        // is the visitID in the database from since the start of the day?
        $sql = "SELECT * FROM visitorLog WHERE visitID = " . $visitID 
            . " AND dateCreated > '" . $today->format("Y-m-d") . "'";
        $result = mysqli_query($con, $sql);
        if (!$result) {
            echo "Error: " . $sql . "<br>" . mysqli_error($con);
            logfile("Error: " . $sql . "<br>" . mysqli_error($con));
            exit;
        } 
        // if no records in result, then this is a new checkin
        if (mysqli_num_rows($result) == 0) {
            // this is a new checkin
            // insert the new visit into the database
            insertNewVisitInDatabase($con, $nameFirst, $nameLast, $email, $phone, $visitReason);
            echo "insert new with previousVisitNum: " . $previousVisitNum . ".";
        } else {
            // this is a checkout
            updateVisitInDatabase($con, $result["recNum"]);
            echo "checkout";
        }

        // close the database connection
        mysqli_close($con);

        // end the php
        exit;
}

function insertNewVisitInDatabase($con, $nameFirst, $nameLast, $email, $phone, $visitReason) {
    $today = new DateTime();

    $sql = "INSERT INTO visitorLog SET nameFirst = '" . $nameFirst . "', nameLast = '" . $nameLast . "'," 
        . " email = '" . $email . "', phone = '" . $phone . "'," 
        . " visitReason = '" . $visitReason . "'"
        . ", dateCheckedIn = '" . $today->format("Y-m-d H:i:s") . "'";

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

function updateVisitInDatabase($con, $visitID) {

    $today = new DateTime();
    $sql = "UPDATE visitorLog SET dateOut = '" . $today->format("Y-m-d H:i:s") 
        . " dateCheckout = '" . $today->format("Y-m-d H:i:s")
        . "' WHERE visitID = " . $visitID;
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
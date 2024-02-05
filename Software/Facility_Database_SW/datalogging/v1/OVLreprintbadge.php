<?php

// Purpose: Flip the field to queue a badge reprint
// Author: Jim Schrempp
// Copywrite: 2024 Maker Nexus
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
//
// Date: 2024-02-05
//

include 'OVLcommonfunctions.php';

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d H:i:s");

// write php errors to the log file
ini_set('log_errors', 1);
ini_set('error_log', 'OVLlog.txt');


$OVLdebug = false; // set to true to see debug messages 
debugToUser( "OVLdebug is active. " . $nowSQL .  "<br>");

allowWebAccess();  // if IP not allowed, then die

// Get the database credentials from a file outside the web root
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

// visitId is the primary key of the ovl_visits table
// this is the record number to requeue for printing 
$visitID = 0;
if (isset($_GET["vid"])) {
    // this would come from a QR code
    $visitID = cleanInput($_GET["vid"]);
} 

#if vid is not a number, then exit
if (!is_numeric($visitID)) {
    echo "visitID: " . $visitID . " is not a number. Exiting.";
    logfile("visitID: " . $visitID . " is not a number. Exiting.");
    exit;
} else  {
    //echo "visitID: " . $visitID . " has been received. Thank you.";
    logfile("visitID: " . $visitID . " has been received.");
}

switch ($visitID) {
    case 0:

        // do nothing
        echo "Error: visit number 0";
        logfile("Error: visit number 0");
        break;

    default:

        $affectedRows = updateBadgePrintInDatabase($con, $nowSQL, $visitID);
        if ($affectedRows == 0) {
            echo "Error: visit number " . $visitID . " not found in last 24 hours.";
            logfile("Error: visit number " . $visitID . " not found in last 24 hours.");
        } else {
            echo "Done. " . $affectedRows . " rows updated.";
        }
        
    } // end switch

// close the database connection
mysqli_close($con);

// end the php
die;

// -------------------------------------
// Functions

// Set the badgeNeedsPrinting flag in the database to 1
function updateBadgePrintInDatabase($con, $nowSQL, $recNum) {

    // update the existing visit to check the visitor out
    $sql = "UPDATE ovl_visits SET "
        . " dateUpdated = '" . $nowSQL . "',"
        . " dateUpdatedLocal = '" . $nowSQL . "',"
        . " labelNeedsPrinting = 1"
        . " WHERE recNum = " . $recNum 
        . "   and dateCreatedLocal > " . "'" . $nowSQL . "' - INTERVAL 1 DAY";

    debugToUser(  "sql: " . $sql);

    $affectedRows = 0;
    $result = mysqli_query($con, $sql);
    if (!$result) {
        logfile("Error: " . $sql . "<br>" . mysqli_error($con));
        debugToUser(  "Error: " . $sql . "<br>" . mysqli_error($con));
    } else {
        $affectedRows = mysqli_affected_rows($con); 
        debugToUser(  "SQL completed successfully");
        logfile("SQL completed successfully");
    }

    return $affectedRows;
}

// Log to a file
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
	
	$baditems = array("select ","update ","delete ","`","insert ","alter ", "drop ");
	$data = str_ireplace($baditems, "[] ",$data);
	return $data;
}

//-------------------------------------
// Echo a string to the user for debugging
function debugToUser ($data) {
    global $OVLdebug;
    if ($OVLdebug){
        echo "<br>" . $data . "<br>";
    }
}

?>
<?php

// List all checkin/out by staff 
//    start/stop dates in URL
//    if no start/stop then 14 day lookback
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin', 'accounting']);  // Require admin or accounting role
include 'commonfunctions.php';
$maxRows = 1000;

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

// get start date
$startDate = 0;

if (isset($_REQUEST['startDate']))
{
    // param was set in the query string

    if(!empty($_REQUEST['startDate']))
    {
        // passed in start date
        $startDate = $_REQUEST['startDate'];
    }
} 

if ($startDate == 0){
    // so look back 14 days
    $startDate = date("Ymd",strtotime("-14 day"));
}

// get end date

$endDate = 0;

if (isset($_REQUEST['endDate']))
{
    // param was set in the query string

    if(!empty($_REQUEST['endDate']))
    {
        // passed in end date
        $endDate = $_REQUEST['endDate'];
    }
} 

if ($endDate == 0){
    // so end today
    $endDate = date('Ymd');
}


// get the HTML skeleton
$myfile = fopen("rfidstaffactivity.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidstaffactivity.txt"));
fclose($myfile);

// Generate auth header
ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();
$html = str_replace("<<AUTH_HEADER>>", $authHeader, $html);

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

$selectSQL = "SELECT dateEventLocal, a.firstName, b.lastName, logEvent 
    FROM `rawdata` a join clientInfo b
      ON a.clientID = b.clientID 
    WHERE displayClasses like '%staff%' 
      AND displayClasses <> 'exStaff'
      AND logEvent IN ('Checked In','Checked Out') 
      AND dateEventLocal between '<<STARTDATE>>' and '<<ENDDATE>>' 
    ORDER BY a.firstName, b.lastName, dateEventLocal LIMIT <<MAXROWS>>;";
$selectSQL = str_replace("<<STARTDATE>>",$startDate,$selectSQL);
$selectSQL = str_replace("<<ENDDATE>>",$endDate,$selectSQL);
$selectSQL = str_replace("<<MAXROWS>>",$maxRows,$selectSQL);

//echo $selectSQL;


// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  return;
}

$result = mysqli_query($con, $selectSQL);
$rowsReturned = mysqli_num_rows($result);

// Construct the page

$html = str_replace("<<STARTDATE>>", $startDate, $html);
$html = str_replace("<<ENDDATE>>", $endDate, $html);

$tableRows = "";

if (mysqli_num_rows($result) > 0) {

	$previousClientID = "---";
    while($row = mysqli_fetch_assoc($result)) {

    	$thisTableRow = makeTR( array (
                 $row["dateEventLocal"],
                 $row["firstName"],
                 $row["lastName"],
                 $row["logEvent"]
                 )
       		);

    	$tableRows = $tableRows . $thisTableRow;
    }

    $html = str_replace("<<TABLEHEADER>>",
    	makeTR(
    		array(
    			"Date Event Local",
    			"First Name",
                "Last Name",
                "Log Event"
    			)
    		),
    	$html);
    $html = str_replace("<<TABLEROWS>>", $tableRows,$html);

    if ($rowsReturned > $maxRows - 1) {
        $html = str_replace("<<TOTALROWS>>", "<p style='color:red'>WARNING: more than " . $maxRows ." rows, results truncated.</p>", $html);
    } else {
        $html = str_replace("<<TOTALROWS>>", $rowsReturned, $html);
    }

} else {
    $html = str_replace("<<TABLEROWS>>", "",$html);
    $html = str_replace("<<TOTALROWS>>", "0 found", $html);
}

echo $html;

mysqli_close($con);

return;



?>

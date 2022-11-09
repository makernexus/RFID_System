<?php

// List all checkin/out by staff 
//    start/stop dates in URL
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'commonfunctions.php';
$maxRows = 1000;

// get the URL parameters
$startDate = $_GET["startDate"];
if ($startDate == 0) {
    echo "startDate= parameter not found.";
    return;
}
$endDate = $_GET["endDate"];
if ($endDate == 0) {
    echo "endDate= parameter not found.";
    return;
}

// get the HTML skeleton
$myfile = fopen("rfidstaffactivity.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidstaffactivity.txt"));
fclose($myfile);

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
    $html = str_replace("<<STARTDATE>>", $startDate, $html);
    $html = str_replace("<<ENDDATE>>", $endDate, $html);
    if ($rowsReturned > $maxRows - 1) {
        $html = str_replace("<<TOTALROWS>>", "<p style='color:red'>WARNING: more than " . $maxRows ." rows, results truncated.</p>", $html);
    } else {
        $html = str_replace("<<TOTALROWS>>", $rowsReturned, $html);
    }
	echo $html;

} else {
    echo "0 results";
}

mysqli_close($con);

return;



?>

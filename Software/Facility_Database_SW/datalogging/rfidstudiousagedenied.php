<?php

// members using various studios (electronics, textiles, woodshop, etc) 
//    start/stop dates in URL
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp, Bob Glicksman

include 'auth_check.php';  // Require authentication
requireRole(['admin']);  // Require admin role
include 'commonfunctions.php';
$maxRows = 10000;
$assumedHoursForNoCheckout = 5;

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

// These are the eventData we will query for
$studios = array("Textile denied", "Wood denied");

// get the HTML skeleton
$myfile = fopen("rfidstudiousagedenied.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidstudiousagedenied.txt"));
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

$selectSQL =  // Records for studio checkins
"    SELECT DISTINCT logEvent, b.firstName, b.lastName, a.dateEventLocal
    FROM `rawdata` a join clientInfo b on a.clientID = b.clientID
    WHERE dateEventLocal between '<<STARTDATE>>' and '<<ENDDATE>>' 
    and logEvent  = '<<STUDIO>>'
    ORDER BY dateEventLocal DESC
    LIMIT 100";
$selectSQL = str_replace("<<STARTDATE>>",$startDate,$selectSQL);
$selectSQL = str_replace("<<ENDDATE>>",$endDate,$selectSQL);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  return;
}

// Build tables into page

foreach ($studios as $studio) {
    $thisTable = buildTable($studio,$selectSQL,$con);
    $token = "<<TABLE" . strtoupper($studio) . ">>";
    $html = str_replace($token, $thisTable, $html);
}

// page information blocks
$html = str_replace("<<STARTDATE>>", $startDate, $html);
$html = str_replace("<<ENDDATE>>", $endDate, $html);

echo $html;

mysqli_close($con);

return;

// ---------------------------------------

function buildTable($studio, $selectSQL, $con) {
    $selectSQL = str_replace("<<STUDIO>>",$studio,$selectSQL);
    $result = mysqli_query($con, $selectSQL);
    echo mysqli_error($con);

    // Construct the table
    $thisTable = buildDivFromRecordset($result);
    return $thisTable;
}

function buildDivFromRecordset($recordSet){

    $tableTemplate = "<table class='rawlogtable'>
                    <<TABLEHEADER>>
                    <<TABLEROWS>>
                    </table>";

    if (mysqli_num_rows($recordSet) > 0) {

        while($row = mysqli_fetch_assoc($recordSet)) {

            $thisYear = substr($row["eventYearWeek"], 0, 4);  //yyyymm
            $thisWeek = substr($row["eventYearWeek"], -2);
     
            $thisTableRow = makeTR( array (
                        $row["dateEventLocal"],
                        $row["lastName"],
                        $row["firstName"],
                        )
                    );
    
            $tableRows = $tableRows . $thisTableRow;
        }
    
        // members in shop each hour of the day
        $tableTemplate = str_replace("<<TABLEHEADER>>", makeTR(array("Date","Last Name","First Name")), $tableTemplate);
        $tableTemplate = str_replace("<<TABLEROWS>>", $tableRows, $tableTemplate);
    } else {
        $tableTemplate = "No Rows Found";
    }
    return $tableTemplate;
}






?>

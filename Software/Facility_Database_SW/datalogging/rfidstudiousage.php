<?php

// members using various studios (electronics, textiles, woodshop, etc) 
//    start/stop dates in URL
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp, Bob Glicksman
//
// Dec 2023: 
//   added three more studis: 3D, hotshop, coldshop

include 'auth_check.php';  // Require authentication
requireRole(['manager', 'admin']);  // Require manager, admin, or MoD role
include 'commonfunctions.php';
$maxRows = 10000;
$assumedHoursForNoCheckout = 5;

// get the URL parameters
$startDate = $_GET["startDate"];
if ($startDate == 0) {
    // echo "startDate= parameter not found.";
    // return;
    $startDate = date("Ymd", strtotime("-1 year"));
}
$endDate = $_GET["endDate"];
if ($endDate == 0) {
    //echo "endDate= parameter not found.";
    //return;
    $endDate = date("Ymd");
}

// These are the eventData we will query for
$studios = array("Electronics", "Textile allowed", "Woodshop allowed", "3D allowed", 
                "hotshop allowed", "coldshop allowed" );

// get the HTML skeleton
$myfile = fopen("rfidstudiousage.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidstudiousage.txt"));
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
"select logEvent, count(*) as numTaps, eventYearWeek, MIN(year) as minYear, MIN(dayYear) minDayYear
from (
    SELECT DISTINCT logEvent, clientID, yearweek(dateEventLocal) as eventYearWeek, 
        year(dateEventLocal) as year, dayofyear(dateEventLocal) as dayYear 
    FROM `rawdata` 
    WHERE dateEventLocal between '<<STARTDATE>>' and '<<ENDDATE>>' 
    and logEvent  = '<<STUDIO>>'
	) a
group by logEvent, eventYearWeek ";
$selectSQL = str_replace("<<STARTDATE>>",$startDate,$selectSQL);
$selectSQL = str_replace("<<ENDDATE>>",$endDate,$selectSQL);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  return;
}

// Build tables into page

foreach ($studios as $studio) {
    $thisStudioSQL = str_replace("<<STUDIO>>",$studio,$selectSQL);
    $result = mysqli_query($con, $thisStudioSQL);
    echo mysqli_error($con);
    $html = addStudio($studio, $result, $html);
}

// page information blocks
$html = str_replace("<<STARTDATE>>", $startDate, $html);
$html = str_replace("<<ENDDATE>>", $endDate, $html);

$html = str_replace("<<RANGE START>>", dateToISO($startDate), $html);
$html = str_replace("<<RANGE END>>", dateToISO($endDate), $html);

echo $html;

mysqli_close($con);

return;

// ---------------------------------------

function addStudio($studio, $recordset, $html) {
    
    // Construct the table
    $html = addTableFromRecordset($studio, $recordset, $html);

    mysqli_data_seek($recordset, 0);
    $html = addGraphDataFromRecordSet($studio, $recordset, $html);

    return $html;
}

function addTableFromRecordset($studio, $recordSet, $html){

    $tableTemplate = "<table class='rawlogtable'>
                    <<TABLEHEADER>>
                    <<TABLEROWS>>
                    </table>";

    if (mysqli_num_rows($recordSet) > 0) {

        $row = mysqli_fetch_assoc($recordSet);
        do {

            $thisYear = substr($row["eventYearWeek"], 0, 4);  //yyyymm
            $thisWeek = substr($row["eventYearWeek"], -2);
    
            $thisTableRow = makeTR( array (
                        $thisYear . " " . $thisWeek,
                        date( "M j, Y", strtotime($thisYear."W".$thisWeek."1") ), 
                        $row["numTaps"]
                        )
                    );
    
                $tableRows = $tableRows . $thisTableRow;

            } while($row = mysqli_fetch_assoc($recordSet));
            
        // members in shop each hour of the day
        $tableTemplate = str_replace("<<TABLEHEADER>>", makeTR(array("YearWeek","Week Starting", "Member Days")), $tableTemplate);
        $tableTemplate = str_replace("<<TABLEROWS>>", $tableRows, $tableTemplate);
    
    } else {
        $tableTemplate = "No Rows Found";
    }

    $token = "<<TABLE" . strtoupper($studio) . ">>";
    $html = str_replace($token, $tableTemplate, $html);

    return $html;
}

function addGraphDataFromRecordSet($studio, $recordSet, $html) {

    if (mysqli_num_rows($recordSet) > 0) {
        $dataX = "";
        $dataY = "";

        $row = mysqli_fetch_assoc($recordSet);
        do {

            $thisYear = substr($row["eventYearWeek"], 0, 4);  //yyyymm
            $thisWeek = substr($row["eventYearWeek"], -2);

            // $dateValue = $row["minYear"] . "-" . $row["minMonth"] . "-" . $row["minDay"] . " 00:00:00" ;
            $dateValue = date( "Y-m-d", strtotime($thisYear."W".$thisWeek."1") );

            $dataX = $dataX . "" . $dateValue . "|";
            $dataY = $dataY . $row["numTaps"] . "|";

        } while($row = mysqli_fetch_assoc($recordSet));

        $dataX = trim($dataX);
        $token = "<<GRAPH" . strtoupper($studio) . "DATAX>>";
        $html = str_replace($token, $dataX, $html);

        $dataY = trim($dataY);
        $token = "<<GRAPH" . strtoupper($studio) . "DATAY>>";
        $html = str_replace($token, $dataY, $html);

        

    } else {
        // what to do if no records?
    }

    return $html;
}

function dateToISO ($dateString) {

    return substr($dateString,0,4) . "-" . substr($dateString,4,2) . "-" . substr($dateString,6,2);

}

function getDateFromDayOfYear($year, $dayOfYear) {
    $date = DateTime::createFromFormat('z Y', strval($year) . ' ' . strval($dayOfYear));
    return $date;
  }





?>

<?php

// number of members present in shop hour by hour over weekdays 
//    start/stop dates in URL
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin', 'MoD']);  // Require admin or MoD role
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

// get the HTML skeleton
$myfile = fopen("rfidshopusagebyhour.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidshopusagebyhour.txt"));
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

$selectSQLA =  // checkin records
"CREATE TEMPORARY TABLE checkIns SELECT dateEventLocal, a.clientID, a.firstName FROM rawdata a join clientInfo b on a.clientID = b.clientID 
where displayClasses not like '%staff%' and dateEventLocal between '<<STARTDATE>>' and '<<ENDDATE>>' and logEvent = 'Checked in';";

$selectSQLB =  // checkout records
"CREATE TEMPORARY TABLE checkOuts SELECT dateEventLocal, a.clientID, a.firstName FROM rawdata a join clientInfo b on a.clientID = b.clientID 
where displayClasses not like '%staff%' and dateEventLocal between '<<STARTDATE>>' and '<<ENDDATE>>' and logEvent = 'Checked Out';";

$selectSQLC = // joining the two, remembering that some people do not check out
"select weekday(a.datein) as checkinWeekday, a.clientID, a.firstName, hour(a.datein) as checkinHour, hour(b.dateout) as checkoutHour
from( 
(select min(dateEventLocal) as datein, DATE_FORMAT(dateEventLocal, '%Y%j') as DOYin, clientID, firstName from checkIns 
  group by clientID, DATE_FORMAT(dateEventLocal, '%Y%j') ) as a 
LEFT Join
(select max(dateEventLocal) as dateout, DATE_FORMAT(dateEventLocal, '%Y%j') as DOYout, clientID from checkOuts 
group by clientID, DATE_FORMAT(dateEventLocal, '%Y%j') ) as b 

on a.clientID = b.clientID and a.DOYin = b.DOYout
)
order by DOYin, checkinHour ;";

$selectSQLA = str_replace("<<STARTDATE>>",$startDate,$selectSQLA);
$selectSQLA = str_replace("<<ENDDATE>>",$endDate,$selectSQLA);

$selectSQLB = str_replace("<<STARTDATE>>",$startDate,$selectSQLB);
$selectSQLB = str_replace("<<ENDDATE>>",$endDate,$selectSQLB);

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  return;
}

// query the db
$resultA = mysqli_query($con, $selectSQLA);
echo mysqli_error($con);
$resultB = mysqli_query($con, $selectSQLB);
echo mysqli_error($con);
$resultC = mysqli_query($con, $selectSQLC);
echo mysqli_error($con);
$rowsReturned = mysqli_num_rows($resultC);

// Construct the page

// init arrays
for ($i=0; $i<24; $i++){
    $visitLength[$i]=0;
}
for ($i=0;$i<24;$i++){
    for ($j=0;$j<7;$j++){
        $membersInShop[$j][$i] = 0;
    }
}

if (mysqli_num_rows($resultC) > 0) {

    while($row = mysqli_fetch_assoc($resultC)) {

        $hourIn = $row["checkinHour"];  // 0-23
        $hourOut = $row["checkoutHour"];  // 0-23
        $weekday = $row["checkinWeekday"]; // 0-6
        
        if ($hourOut > 0) {
            // We have a check out
            $thisVisitHours = $hourOut - $hourIn + 1;

            // Add visit hours to the total vistor hours each weekday
            $totalHoursPerDay[$weekday] += $thisVisitHours;
            $totalMembersPerDay[$weekday]++;

            // distribution of visit hours
            $visitLength[$thisVisitHours]++;

        } else {
            // No checkout record so assume they spent a few hours for the analysis below  
            $numNoCheckOut++; 
            $hourOut += $assumedHoursForNoCheckout;
            if ($hourOut > 21) {
                $hourOut = 21;
            };
        };

        // add to the array of members in the shop 
        // each hour of each weekday
        for ($i = $hourIn; $i <= $hourOut; $i++) {
            $membersInShop[$weekday][$i]++;
            if ($membersInShop[$weekday][$i] > $maximumMembersInShop) {
                $maximumMembersInShop = $membersInShop[$weekday][$i];
            }
        };

    	$thisTableRow2 = makeTR( array (
                 $row["checkinWeekday"],
                 $row["checkinHour"],
                 $row["checkoutHour"],
                 $row["clientID"],
                 $row["firstName"]
                 )
       		);

    	$tableRows2 = $tableRows2 . $thisTableRow2;
    }

    // members in shop each hour of the day
    $html = str_replace("<<TABLEHEADER1>>", makeTR(array("Hour","Mon","Tue", "Wed","Thu","Fri","Sat","Sun")), $html);
    $html = str_replace("<<TABLEHEADER0>>", makeTR(array("Hour","Mon","Tue", "Wed","Thu","Fri","Sat","Sun")), $html);
    for ($i=0; $i<24; $i++){
        unset($thisHour);
        $thisHour[0] = $i;
        $thisHourRaw[0] = $i;
        for ($j=1; $j<8; $j++) {
            if ($membersInShop[$j-1][$i] == 0) {
                $thisHour[$j] = 0;
                $thisHourRaw[$j] = 0;
            } else {
                $thisHour[$j] = round($membersInShop[$j-1][$i]/$maximumMembersInShop *9)+1;
                $thisHourRaw[$j] = $membersInShop[$j-1][$i];
            }
        }
        $thisTableRow1 = makeHeatMapTR($thisHour, 10, TRUE);
        $tableRows1 = $tableRows1 . $thisTableRow1;

        $thisTableRow0 = makeTR($thisHourRaw);
        $tableRows0 = $tableRows0 . $thisTableRow0;
    }
    $html = str_replace("<<TABLEROWS1>>", $tableRows1,$html);
    $html = str_replace("<<TABLEROWS0>>", $tableRows0,$html);

    // average number of hours spent in the shop
    $html = str_replace("<<AVERAGETABLEHEADER>>", makeTR(array("M","T", "W","TH","F","S","S")), $html);
        for ($j=0; $j<7; $j++) {
            if ($totalMembersPerDay[$j] > 0){
                $thisDayAverage[$j] = round($totalHoursPerDay[$j]/$totalMembersPerDay[$j],1);
            } else {
                $thisDayAverage[$j] = "na";
            }
        $thisTableRow1 = makeTR($thisDayAverage);
        }
    $html = str_replace("<<AVERAGETABLEROWS>>", $thisTableRow1,$html);

    // Visit Length Distribution
    for ($i=0; $i<24; $i++) {
        $headerHoursArray[$i] = $i;
    }
    $html = str_replace("<<VISITHOURSTABLEHEADER>>", makeTR($headerHoursArray), $html);
    $html = str_replace("<<VISITHOURSTABLEROWS>>", makeTR($visitLength), $html);

    // raw data for debugging
    if ($rowsReturned > 100) {
        $html = str_replace("<<TABLEHEADER2>>", "More than 100 rows, display cancelled.", $html);
        $html = str_replace("<<TABLEROWS2>>", "",$html);
    } else {
        $html = str_replace("<<TABLEHEADER2>>",
            makeTR(
                array(
                    "day",
                    "hourIn", "hourOut",
                    "ID","name"
                    )
                ),
            $html);
        $html = str_replace("<<TABLEROWS2>>", $tableRows2,$html);
    }

    // page information blocks
    $html = str_replace("<<STARTDATE>>", $startDate, $html);
    $html = str_replace("<<ENDDATE>>", $endDate, $html);
    $html = str_replace("<<ASSUMEDUSE>>", $assumedHoursForNoCheckout, $html);
    $html = str_replace("<<NOCHECKOUT>>", round($numNoCheckOut/$rowsReturned,2)*100, $html);

    if ($rowsReturned > $maxRows - 1) {
        $html = str_replace("<<TOTALROWS>>", "<p style='color:red'>WARNING: more than " . $maxRows ." rows, results truncated.</p>", $html);
    } else {
        $html = str_replace("<<TOTALROWS>>", "$rowsReturned", $html);
    }

} else {
    $html = "0 results";
}

echo $html;

mysqli_close($con);

return;



?>

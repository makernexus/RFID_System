<?php

// Purpose: Display the current visitors
// Author: Jim Schrempp
// Copywrite: 2024 Maker Nexus
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
//
// Date: 2024-10-16
//

include 'OVLcommonfunctions.php';

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d H:i:s");

$OVLdebug = false; // set to true to see debug messages
debugToUser( "OVLdebug is active. " . $nowSQL .  "<br>");

allowWebAccess();  // if IP not allowed, then die

// get the HTML skeleton
$html = file_get_contents("OVLcurrentvisitors.html");
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
    //logfile("Failed to connect to MySQL: " . mysqli_connect_error());
}

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d"); // just the date

$sql = "SELECT recNum, nameFirst, nameLast FROM ovl_visits " 
        . " WHERE dateCheckinLocal > '" . $nowSQL . "'"
        . " AND dateCheckoutLocal = '0000-00-00 00:00:00'";

$result = mysqli_query($con, $sql);
if (!$result) {

    echo "Error: " . $sql . "<br>" . mysqli_error($con);
    //logfile("Error: " . $sql . "<br>" . mysqli_error($con));
    exit;

} else {

    // create the divs

    $outputDivs = "";
    if (mysqli_num_rows($result) == 0) {
        $outputDivs = "<div class='visitor'>No visitors at this time</div>";
    } else {
        // loop over all rows
        while ($row = mysqli_fetch_assoc($result)) {
            //echo "row: " . $row["nameFirst"] . " " . $row["nameLast"] . "<br>";
            $outputDivs = $outputDivs . makeDiv($row["recNum"],$row["nameFirst"], $row["nameLast"]);
        }
    }

    // replace the divs in the html
    $html = str_replace("<<DIVSHERE>>", $outputDivs, $html);
    echo $html;
    
}

// close the database connection
mysqli_close($con);

// end the php
exit;


function makeDiv($visitID, $nameFirst, $nameLast) {
    
    $div = "<div class='visitor'>"
        . $nameFirst . " "
        . $nameLast . " "
        . makeCheckoutLink($visitID) . "&nbsp;&nbsp;&nbsp;"
        . makeNewBadgeLink($visitID)
        . "</div>";
    return $div;
}

function makeCheckoutLink($visitID) {
    $link = "<a href='OVLcheckinout.php?vid=" . $visitID . "' target='_blank'>Check Out</a>";
    return $link;
}

function makeNewBadgeLink($visitID) {
    $link = "<a href='OVLreprintbadge.php?vid=" . $visitID . "' target='_blank'>Reprint Badge</a>";
    return $link;
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

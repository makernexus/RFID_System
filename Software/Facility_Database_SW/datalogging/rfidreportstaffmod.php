<?php

// Show list of anyone who is either Staff designated or MOD Eligible
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp

include 'auth_check.php';  // Require authentication
requireRole(['admin', 'MoD']);  // Require admin or MoD role
include 'commonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles")); 

// get the HTML skeleton
$htmlFileName = "rfidreportstaffmodhtml.txt";
$myfile = fopen($htmlFileName, "r") or die("Unable to open file!");
$html = fread($myfile,filesize($htmlFileName));
fclose($myfile);

// Generate auth header
ob_start();
include 'auth_header.php';
$authHeader = ob_get_clean();
$html = str_replace("<<AUTH_HEADER>>", $authHeader, $html);

// Get the config info
$ini_array = parse_ini_file("rfidconfig.ini", true);
$photoServer = $ini_array["CRM"]["photoServer"];        // if true, running in sandbox
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

$selectSQL = 
    "SELECT firstName, lastName, clientID, displayClasses, MOD_Eligible
     FROM clientInfo
     WHERE (displayClasses like '%staff%' AND displayClasses not like '%exStaff%')
        OR MOD_Eligible = 1
     ORDER BY lastName ASC";
    
$result = mysqli_query($con, $selectSQL);
echo mysqli_error($con);

// Construct the page

$resultTable = "<table>";

if (mysqli_num_rows($result) > 0) {
	
    while($row = mysqli_fetch_assoc($result)) {

        $thisRow = makeRow($row["firstName"], $row["lastName"], $row["displayClasses"], $row["MOD_Eligible"]  ) . "\r\n";
            
        $resultTable = $resultTable . $thisRow;
    }
}
$resultTable = $resultTable . "</table>";

$html = str_replace("<<RESULTTABLE>>",$resultTable, $html);
echo $html;

mysqli_close($con);

return;

// ------------------------------------------------------------


function makeRow($firstName, $lastName, $classes, $MODeligible) {
    $MOD = "";
    if ($MODeligible == 1) {
        $MOD = "MOD";
    }
    $STAFF = "";
    if (strpos(" ".$classes,"staff") != 0) {
        $STAFF = "Staff";
    }

    return "<tr><td>" . $lastName . ", " . $firstName . "</td><td>" . $STAFF . "</td><td>" . $MOD . "</td></tr>" ;
}

function makeTable($firstName, $lastName, $clientID, $dateLastSeen, $photoServer, $MODeligible){
  return "<table class='clientTable'><tr><td class='clientImageTD'>" . makeImageURL($clientID, $photoServer) . 
  "</td></tr><tr><td class='clientNameTD'>" . $lastName . ", " . $firstName . 
  "</td></tr><tr><td class='clientEquipTD'><p class='equiplist'>" . $dateLastSeen . "</p></td></tr></table>";
}	
function makeImageURL($data, $photoServer) {
	return "<img class='IDPhoto' alt='no photo' src='" . $photoServer . $data . ".jpg' onerror=\"this.src='WeNeedAPhoto.png'\" >";
}

?>
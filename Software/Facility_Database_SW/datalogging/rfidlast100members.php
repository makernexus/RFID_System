<?php

// Show photos of most recent 100 members
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp

include 'commonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles")); 

// get the HTML skeleton
$myfile = fopen("rfidlast100membershtml.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidlast100membershtml.txt"));
fclose($myfile);

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
    "SELECT firstName, lastName, clientID, dateLastSeen, displayClasses, MOD_Eligible
     FROM clientInfo
     ORDER BY dateLastSeen DESC
     LIMIT 200";
    
$result = mysqli_query($con, $selectSQL);
echo mysqli_error($con);

// Construct the page

if (mysqli_num_rows($result) > 0) {
	
    while($row = mysqli_fetch_assoc($result)) {

        $thisDiv = makeDiv($row["firstName"], $row["lastName"], $row["clientID"], $row["dateLastSeen"], $photoServer, $row["displayClasses"], $row["MOD_Eligible"]  ) . "\r\n";
            
        $photodivs = $photodivs . $thisDiv;
    }
}

$html = str_replace("<<PHOTODIVS>>",$photodivs, $html);
echo $html;

mysqli_close($con);

return;

// ------------------------------------------------------------


function makeDiv($firstName, $lastName, $clientID, $dateLastSeen, $photoServer, $classes, $MODeligible) {
    $MODclass = "";
    if ($MODeligible == 1) {
        $MODclass = "MOD";
    }
    return "<div class='photodiv " . $classes . " " . $MODclass . "' >" . makeTable($firstName, $lastName, $clientID, $dateLastSeen, $photoServer, $displayClasses, $MODeligible) . "</div>";
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
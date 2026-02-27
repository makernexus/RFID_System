<?php

// Show photos of everyone who is checked in today.
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp
// 20250202 result is now cached for 15 seconds

include 'kiosk_auth_check.php';  // NEW: Token-based authentication

include 'commonfunctions.php';
//allowWebAccess();  // if IP not allowed, then die

$today = new DateTime();
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));

$localCacheFileName = "rfidcurrentcheckinsWithMOD.cache";
$cacheTime = 15;  // seconds

$html = checkCachedFile($localCacheFileName, $cacheTime);
if ($html != "") {
    echo $html;
    return;
}

// cache file is not valid, so we need to build the page

// get the HTML skeleton
$myfile = fopen("rfidcurrentcheckinshtmlWithMOD.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidcurrentcheckinshtmlWithMOD.txt"));
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

// Get current MgrOnDuty status
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/rfidcurrentMOD.php';
//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//execute post
$result = curl_exec($ch);

//close connection
curl_close($ch);

$MODResult = json_decode($result, true);
if ($MODResult == null) {
    echo("could not parse MOD result JSON");
    exit();
}

$selectSQL =
    "CALL sp_checkedInDisplay('" . date_format($today, "Ymd") . "');" ;

$result = mysqli_query($con, $selectSQL);
echo mysqli_error($con);

// Construct the page

$html =  str_replace("<<REFRESHTIME>>","Updated: " . date_format($today, "Y-m-d H:i:s"),$html);

// Put out MOD panel
$html = str_replace("<<MODFIRSTNAME>>",$MODResult["firstName"], $html);
$html = str_replace("<<MODPHOTO>>",$MODResult["photoURL"], $html);



if (mysqli_num_rows($result) > 0) {

    // output data of each row
    $currentClientID = "";
    $currentFirstName = "";
    $currentDisplayClasses = "";
    $currentEquipment = "";
    $firstIteration = true;
    while($row = mysqli_fetch_assoc($result)) {

        if ($row["clientID"] != $currentClientID) {
            // new client

            if ($firstIteration) {
                //
                $firstIteration = false;
            } else if ($currentClientID != $MODResult["clientID"]) {
                // create div for previous clientID
                $thisDiv = makeDiv($currentDisplayClasses, $currentFirstName, $currentClientID, $currentEquipment, $photoServer  ) . "\r\n";

                // add div to output accumulation
                $photodivs = $photodivs . $thisDiv;
            }

            // set up for the next client
            $currentFirstName = $row["firstName"];
            $currentEquipment = $row["photoDisplay"];
            $currentClientID = $row["clientID"];
            $currentDisplayClasses = $row["displayClasses"];

        } else {

            // same client, add the equipment name
            $currentEquipment = $currentEquipment . " " . $row["photoDisplay"];
        }

    }
    // last element from loop
    if ($currentClientID != $MODResult["clientID"]) {
        $thisDiv = makeDiv($currentDisplayClasses, $currentFirstName , $currentClientID, $currentEquipment, $photoServer ) . "\r\n";
        $photodivs = $photodivs . $thisDiv;
    }
}

$html = str_replace("<<PHOTODIVS>>",$photodivs, $html);
echo $html;

mysqli_close($con);

updateCachedFile($localCacheFileName, $html);

return;

// ------------------------------------------------------------

function makeImageURL($data, $photoServer) {
    return "<img class='IDPhoto' alt='no photo' src='" . $photoServer . $data . ".jpg' onerror=\"this.src='WeNeedAPhoto.png'\" >";
}
function makeDiv($classes, $name, $clientID, $equip, $photoServer) {
    return "<div class='photodiv " . $classes . "'><div class='photodiv-inner'>" . makeTable($name, $clientID, $equip, $photoServer) . "</div></div>";
}
function makeTable($name, $clientID, $equip, $photoServer){
    return "<table class='clientTable'><tr><td class='clientImageTD'>" . makeImageURL($clientID, $photoServer) .
    "</td></tr><tr><td class='clientNameTD'>" . makeNameCheckoutAction($clientID, $name) .
    "</td></tr><tr><td class='clientEquipTD'>" . makeEquipList($equip) . "</td></tr></table>";
}
function makeNameCheckoutAction($clientID, $name) {
    return "<p class='photoname' onclick=\"checkout('" . $clientID . "','" . $name . "')\">" . $name . "</p>";
}
function makeEquipList($equip){
    return "<p class='equiplist'>" . $equip . "</p>";
}

function makeMODDiv($name, $photoURL) {
    return "<div class='photodiv MOD' >" . makeMODTable($name, $photoURL) . "</div>";
}
function makeMODTable($name, $photoURL){
    return "<table class='clientTable'>" .
    //"<tr><td><p class='MODtitle'>Maker On Duty</p></td></tr>" .
    "<tr><td class='clientImageTD'><img class='IDPhoto' alt='no photo' src='" . $photoURL . "' onerror=\"this.src='WeNeedAPhoto.png'\"></td>" .
    "</tr>" .
    "<tr><td class='clientNameTD'><p class='photoname'>" . $name . "</p></td></tr>" .
    "<tr><td class='clientEquipTD'>Maker On Duty</td></tr>" .
    "</table>";
  }



?>

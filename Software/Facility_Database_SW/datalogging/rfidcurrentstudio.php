<?php

// Show photos of everyone who has tapped into a specific studio today.
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2023 Maker Nexus
// By Jim Schrempp

include 'commonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

$studio = $_GET["studio"];
if (strlen($studio) == 0) {
    echo("<p>studio not specified");
    echo("<p>valid studio names: wood, textile, electronics");
    exit();
}
$studioTag = "";
if (strcmp($studio,"wood") == 0) {
    $studioTag = "wood";
    $studioName = "Woodshop";
}
if (strlen($studioTag) == 0) {
    echo("<p>Unknown studio: |" . $studio . "|" );
    echo("<p>valid studio names: wood, textile, electronics");
    exit();
}

$today = new DateTime();
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));

// get the HTML skeleton
$myfile = fopen("rfidcurrentstudiohtml.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidcurrentstudiohtml.txt"));
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

            } else if (($currentClientID != $MODResult["clientID"])
                      && (strpos($row["photoDisplay"], $studioTag) == TRUE))  {
                echo ("<p>" . $row["photoDisplay"]);
                // create div for previous clientID
                $thisDiv = makeDiv($currentDisplayClasses, $currentFirstName, $currentClientID, $currentEquipment, $photoServer  ) . "\r\n";

                // add div to output accumulation
                $photodivs = $photodivs . $thisDiv;
            }

            // set up for the next client
            $currentFirstName = $row["firstName"];
            $currentClientID = $row["clientID"];
            $currentEquipment = $row["photoDisplay"];
            $currentDisplayClasses = $row["displayClasses"];

        } else {

            // same client, add the equipment name
            $currentEquipment = $currentEquipment . " " . $row["photoDisplay"];
        }

    }
    // last element from loop
    if (($currentClientID != $MODResult["clientID"])
        && (strpos($row["photoDisplay"], $studioTag) == TRUE)) {

        $thisDiv = makeDiv($currentDisplayClasses, $currentFirstName , $currentClientID, $currentEquipment, $photoServer ) . "\r\n";
        $photodivs = $photodivs . $thisDiv;
    }
}

if (strlen($photodivs) > 0 ){
    $html = str_replace("<<PHOTODIVS>>",$photodivs, $html);
} else {
    $html = str_replace("<<PHOTODIVS>>","<H1>No Members Have Tapped In Successfully to " . $studioName . "</H1>", $html);
}
$html = str_replace("<<STUDIONAME>>",$studioName, $html);
echo $html;

mysqli_close($con);

return;

// ------------------------------------------------------------

function makeImageURL($data, $photoServer) {
	return "<img class='IDPhoto' alt='no photo' src='" . $photoServer . $data . ".jpg' onerror=\"this.src='WeNeedAPhoto.png'\" >";
}
function makeDiv($classes, $name, $clientID, $equip, $photoServer) {
    return "<div class='photodiv " . $classes . "' style='height:280px;' >" . makeTable($name, $clientID, $equip, $photoServer) . "</div>";
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

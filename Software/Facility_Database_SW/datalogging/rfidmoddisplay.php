<?php

// Show photos of everyone who is checked in today.
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp

include 'commonfunctions.php';

allowWebAccess();  // if IP not allowed, then die

$today = new DateTime(); 
$today->setTimeZone(new DateTimeZone("America/Los_Angeles")); 

// get the HTML skeleton
$myfile = fopen("rfidmoddisplay.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfidmoddisplay.txt"));
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

// Construct the page

$html =  str_replace("<<REFRESHTIME>>","Updated: " . date_format($today, "Y-m-d H:i:s"),$html);

// Put out MOD panel 

$html = str_replace("<<FIRSTNAME>>",$MODResult["firstName"], $html);
$html = str_replace("<<PHOTO>>",$MODResult["photoURL"], $html);
echo $html;

return;

// ------------------------------------------------------------


?>
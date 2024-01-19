<?php

// When called, reply with zero to five visitors needing labels
// and reset their flag to zero in the database
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2024 Maker Nexus
// By Jim Schrempp

include 'OVLcommonfunctions.php';

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$nowSQL = $today->format("Y-m-d");

$OVLdebug = false; // set to true to see debug messages
debugToUser( "OVLdebug is active. " . $today->format("Y-m-d H:i:s") .  "<br>");

allowWebAccess();  // if IP not allowed, then die

// Get the data
$ini_array = parse_ini_file("OVLconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["writeUser"];
$dbPassword = $ini_array["SQL_DB"]["writePassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

// Check connection
if (mysqli_connect_errno()) {
    debugToUser( "Failed to connect to MySQL: " . mysqli_connect_error());
    //logfile("Failed to connect to MySQL: " . mysqli_connect_error());
}

$visitorArray =  array();  // create empty array here, in case of SQL error

$sql = "SELECT recNum, nameFirst, nameLast FROM ovl_visits " 
        . " WHERE labelNeedsPrinting = 1"
        . " AND dateCheckinLocal > '" . $nowSQL . "'"
        . " LIMIT 5";

debugtouser("sql" . $sql . "<br>");

$result = mysqli_query($con, $sql);
if (!$result) {

    debugtouser( "Error: " . $sql . "<br>" . mysqli_error($con));
    //logfile("Error: " . $sql . "<br>" . mysqli_error($con));
    exit;

} else {
    
    $visitorCount = -1;
    $recNumList = "";
    if (mysqli_num_rows($result) > 0) {
        // loop over all rows
        while ($row = mysqli_fetch_assoc($result)) {
            $visitorCount += 1;
            $visitorArray[$visitorCount]["recNum"] = $row["recNum"];
            $visitorArray[$visitorCount]["nameFirst"] = $row["nameFirst"];
            $visitorArray[$visitorCount]["nameLast"] = $row["nameLast"];
            $visitorArray[$visitorCount]["URL"] = "https://rfidsandbox.makernexuswiki.com/v1/OVLcheckinout.php?vid=" . $row["recNum"];
            $recNumList = $recNumList . $row["recNum"] . ",";
        }
        $recNumList = substr($recNumList, 0, -1); // remove trailing comma

        debugToUser( "recNumList: " . $recNumList . "<br>" . strlen($recNumList) . "<br>");

        if (strlen($recNumList) > 0) {

            # update the datatbase to show that the labels have been printed
            $sql = "UPDATE ovl_visits SET labelNeedsPrinting = 0 WHERE recNum in (<<RECNUMLIST>>)";

            $sql = str_replace("<<RECNUMLIST>>", $recNumList, $sql);

            debugtouser("sql" . $sql . "<br>");

            $result = mysqli_query($con, $sql);
            if (!$result) {
                debugtouser( "Error: " . $sql . "<br>" . mysqli_error($con));
                //logfile("Error: " . $sql . "<br>" . mysqli_error($con));
                exit;
            }
        }
    }
}

#convert to json
$arrayForJSON = array();
$arrayForJSON["dateCreated"] =  date("Y-m-d H:i:s");
$arrayForJSON["data"] = array(
        "visitors" => $visitorArray);
$json = json_encode($arrayForJSON);

#send the json
echo $json;
            
// close the database connection
mysqli_close($con);

// end the php
exit;


//-------------------------------------
// Echo a string to the user for debugging
function debugToUser ($data) {
    global $OVLdebug;
    if ($OVLdebug){
        echo "<br>" . $data . "<br>";
    }
}

?>
<?php

// all RFID actions that were denied 
//    start/stop dates in URL
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2023 Maker Nexus
// By Jim Schrempp, Bob Glicksman

include 'commonfunctions.php';
$maxRows = 10000;

$today = new DateTime();  
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
$today->sub(new DateInterval('P60D'));  // end date for select will be midnight tonight
$SixtyDaysAgoSQL = $today->format("Y-m-d");

// get the HTML skeleton
$myfile = fopen("rfiddeniedanyreason.txt", "r") or die("Unable to open file!");
$html = fread($myfile,filesize("rfiddeniedanyreason.txt"));
fclose($myfile);

// Get the data
$ini_array = parse_ini_file("rfidconfig.ini", true);
$dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
$dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
$dbName = $ini_array["SQL_DB"]["dataBaseName"];

$con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);

$selectSQL =  // Records for all denials
"    SELECT DISTINCT logEvent, b.firstName, b.lastName, a.dateEventLocal, a.logData
    FROM `rawdata` a join clientInfo b on a.clientID = b.clientID
    WHERE logEvent  like '%denied%'
    AND dateEventLocal > '" . $SixtyDaysAgoSQL . "'" . " 
    ORDER BY dateEventLocal DESC
    LIMIT 100";

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  return;
}

// Build tables into page

$result = mysqli_query($con, $selectSQL);
$thisTable = buildDivFromRecordset($result);
$token = "<<TABLE DENIED>>";
$html = str_replace($token, $thisTable, $html);

// page information blocks


echo $html;

mysqli_close($con);

return;

// ---------------------------------------

function buildDivFromRecordset($recordSet){

    $tableTemplate = "<table class='rawlogtable'>
                    <<TABLEHEADER>>
                    <<TABLEROWS>>
                    </table>";

    if (mysqli_num_rows($recordSet) > 0) {

        while($row = mysqli_fetch_assoc($recordSet)) {
     
            $thisTableRow = makeTR( array (
                        $row["dateEventLocal"],
                        $row["lastName"],
                        $row["firstName"],
                        $row["logEvent"],
                        $row["logData"]
                        )
                    );
    
            $tableRows = $tableRows . $thisTableRow;
        }
    
        $tableTemplate = str_replace("<<TABLEHEADER>>", makeTR(array("Date","Last Name","First Name")), $tableTemplate);
        $tableTemplate = str_replace("<<TABLEROWS>>", $tableRows, $tableTemplate);
    } else {
        $tableTemplate = "No Rows Found";
    }
    return $tableTemplate;
}






?>

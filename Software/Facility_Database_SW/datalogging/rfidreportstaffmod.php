<?php

// Show list of anyone who is either Staff designated or MOD Eligible
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp

// Handle AJAX update request FIRST, before any includes
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    // Suppress any PHP errors/warnings for clean JSON response
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Clean any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Need to check authentication for AJAX request too
    session_start();
    
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Only admins can edit']);
        exit();
    }
    
    require_once 'admin_log_functions.php';
    
    $clientID = $_POST['clientID'];
    $modEligible = intval($_POST['modEligible']);
    $displayClasses = $_POST['displayClasses'];
    
    // Connect to the main database with write user
    $ini_array = @parse_ini_file("rfidconfig.ini", true);
    if (!$ini_array) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Config file error']);
        exit();
    }
    
    $dbUser = $ini_array["SQL_DB"]["writeUser"];
    $dbPassword = $ini_array["SQL_DB"]["writePassword"];
    $dbName = $ini_array["SQL_DB"]["dataBaseName"];
    
    $con = @mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);
    
    if (!$con) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Get current values before update for logging
    $beforeData = getClientDataForLogging($con, $clientID);
    
    $updateSQL = "UPDATE clientInfo SET MOD_Eligible = ?, displayClasses = ? WHERE clientID = ?";
    $stmt = @mysqli_prepare($con, $updateSQL);
    
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        mysqli_close($con);
        exit();
    }
    
    @mysqli_stmt_bind_param($stmt, "iss", $modEligible, $displayClasses, $clientID);
    
    if (@mysqli_stmt_execute($stmt)) {
        // Log the changes
        if ($beforeData) {
            // Log MOD_Eligible change if different
            if ($beforeData['MOD_Eligible'] != $modEligible) {
                logAdminAction($con, 'update_mod', $clientID, 'MOD_Eligible', 
                    $beforeData['MOD_Eligible'], $modEligible, 'Updated via Staff/MOD report');
            }
            
            // Log displayClasses change if different
            if ($beforeData['displayClasses'] != $displayClasses) {
                logAdminAction($con, 'update_classes', $clientID, 'displayClasses', 
                    $beforeData['displayClasses'], $displayClasses, 'Updated via Staff/MOD report');
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
    @mysqli_stmt_close($stmt);
    @mysqli_close($con);
    exit();
}

// Normal page load - require authentication
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

// Handle search
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchResults = '';
$clearSearchLink = '';

if ($searchQuery !== '') {
    // User is searching
    $html = str_replace("<<SEARCHVALUE>>", htmlspecialchars($searchQuery), $html);
    $clearSearchLink = '<a href="rfidreportstaffmod.php" style="margin-left: 10px;">Clear Search</a>';
    $html = str_replace("<<CLEARSEARCH>>", $clearSearchLink, $html);
    
    // Perform search
    $ini_array = parse_ini_file("rfidconfig.ini", true);
    $dbUser = $ini_array["SQL_DB"]["readOnlyUser"];
    $dbPassword = $ini_array["SQL_DB"]["readOnlyPassword"];
    $dbName = $ini_array["SQL_DB"]["dataBaseName"];
    
    $con = mysqli_connect("localhost",$dbUser,$dbPassword,$dbName);
    
    if (mysqli_connect_errno()) {
        $searchResults = '<div class="search-results"><p>Database connection error</p></div>';
    } else {
        $searchSQL = "SELECT firstName, lastName, clientID, displayClasses, MOD_Eligible, dateLastSeen 
                      FROM clientInfo 
                      WHERE (firstName LIKE ? OR lastName LIKE ?) 
                      ORDER BY lastName ASC";
        
        $stmt = mysqli_prepare($con, $searchSQL);
        $searchParam = "%" . $searchQuery . "%";
        mysqli_stmt_bind_param($stmt, "ss", $searchParam, $searchParam);
        mysqli_stmt_execute($stmt);
        $searchResult = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($searchResult) > 0) {
            $editColumnHeader = "";
            if (isAdmin()) {
                $editColumnHeader = "<th></th>";
            }
            
            $searchResults = '<div class="search-results"><h3>Search Results (' . mysqli_num_rows($searchResult) . ' found)</h3>';
            $searchResults .= '<table><thead><tr><th>Client ID</th><th>Name</th><th>Classes</th><th>MOD</th><th>Date Last Seen</th>' . $editColumnHeader . '</tr></thead><tbody>';
            
            while($row = mysqli_fetch_assoc($searchResult)) {
                $searchResults .= makeRow($row["firstName"], $row["lastName"], $row["clientID"], $row["displayClasses"], $row["MOD_Eligible"], $row["dateLastSeen"]) . "\r\n";
            }
            
            $searchResults .= '</tbody></table></div>';
        } else {
            $searchResults = '<div class="search-results"><p>No results found for "' . htmlspecialchars($searchQuery) . '"</p></div>';
        }
        
        mysqli_stmt_close($stmt);
        mysqli_close($con);
    }
} else {
    $html = str_replace("<<SEARCHVALUE>>", "", $html);
    $html = str_replace("<<CLEARSEARCH>>", "", $html);
}

$html = str_replace("<<SEARCHRESULTS>>", $searchResults, $html);

// Get the config info for main table
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
    "SELECT firstName, lastName, clientID, displayClasses, MOD_Eligible, dateLastSeen
     FROM clientInfo
     WHERE displayClasses IS NOT NULL AND displayClasses <> ''
     ORDER BY lastName ASC";
    
$result = mysqli_query($con, $selectSQL);
echo mysqli_error($con);

// Construct the page

$editColumnHeader = "";
if (isAdmin()) {
    $editColumnHeader = "<th></th>"; // Empty header for edit button column
}

$resultTable = "<table>";
$resultTable .= "<thead><tr><th>Client ID</th><th>Name</th><th>Classes</th><th>MOD</th><th>Date Last Seen</th>" . $editColumnHeader . "</tr></thead>";
$resultTable .= "<tbody>";

if (mysqli_num_rows($result) > 0) {
	
    while($row = mysqli_fetch_assoc($result)) {

        $thisRow = makeRow($row["firstName"], $row["lastName"], $row["clientID"], $row["displayClasses"], $row["MOD_Eligible"], $row["dateLastSeen"]  ) . "\r\n";
            
        $resultTable = $resultTable . $thisRow;
    }
}
$resultTable = $resultTable . "</tbody></table>";

$html = str_replace("<<RESULTTABLE>>",$resultTable, $html);
echo $html;

mysqli_close($con);

return;

// ------------------------------------------------------------


function makeRow($firstName, $lastName, $clientID, $classes, $MODeligible, $dateLastSeen) {
    $MOD = "";
    if ($MODeligible == 1) {
        $MOD = "MOD";
    }
    
    // Display the full displayClasses value
    $displayClassesValue = htmlspecialchars($classes);
    
    // Format date last seen
    $formattedDate = !empty($dateLastSeen) ? htmlspecialchars($dateLastSeen) : '';
    
    // Only show edit button if user is admin
    $editButton = "";
    if (isAdmin()) {
        $escapedFirstName = htmlspecialchars($firstName, ENT_QUOTES);
        $escapedLastName = htmlspecialchars($lastName, ENT_QUOTES);
        $escapedClasses = htmlspecialchars($classes, ENT_QUOTES);
        $editButton = "<td><button class='edit-btn' onclick=\"openEditModal('$clientID', '$escapedFirstName', '$escapedLastName', '$escapedClasses', $MODeligible)\">Edit</button></td>";
    }

    return "<tr><td>" . htmlspecialchars($clientID) . "</td><td>" . $lastName . ", " . $firstName . "</td><td>" . $displayClassesValue . "</td><td>" . $MOD . "</td><td>" . $formattedDate . "</td>" . $editButton . "</tr>" ;
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
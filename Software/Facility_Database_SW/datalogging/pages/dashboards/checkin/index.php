<!DOCTYPE html>
<?php
    // Show photos of everyone who is checked in today.
    //
    // Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
    // By Jim Schrempp & Giulio Gratta
    // 20250202 result is now cached for 15 seconds

    include '../../php_includes/commonfunctions.php';

    allowWebAccess();  // if IP not allowed, then die

    $today = new DateTime();
    $today->setTimeZone(new DateTimeZone("America/Los_Angeles"));
    $page="member_dashboard";
    $header_title = "Maker Nexus";

    $cache_file = "checkin.cache";
    include '../../php_includes/cache_header.php'; 
    // if valid cache is found, cache file is returned and none of the code below is run

    include '../../php_includes/open_db_connection.php';

    include '../../php_includes/simple_html_dom.php';
?>

<?php
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

    function makeDiv($classes, $name, $clientID, $equip, $photoServer) {
        return "<div class='photodiv " . $classes . "'><div class='photodiv-inner'>" . makeTable($name, $clientID, $equip, $photoServer) . "</div></div>";
    }
    function makeTable($name, $clientID, $equip, $photoServer){
        return "<table class='clientTable'><tr><td class='clientImageTD'>" . makeImageURL($clientID, $photoServer) .
        "</td></tr><tr><td class='clientNameTD'>" . makeNameCheckoutAction($clientID, $name) .
        "</td></tr><tr><td class='clientEquipTD'>" . makeEquipList($equip) . "</td></tr></table>";
    }
    function makeImageURL($data, $photoServer) {
        return "<img class='IDPhoto' alt='no photo' src='" . $photoServer . $data . ".jpg' onerror=\"this.src='../../static/images/no_photo.png'\" >";
    }
    function makeNameCheckoutAction($clientID, $name) {
        return "<p class='photoname' onclick=\"checkout('" . $clientID . "','" . $name . "')\">" . $name . "</p>";
    }
    function makeEquipList($equip){
        return "<p class='equiplist'>" . $equip . "</p>";
    }
?>

<html>
    <head>
        <title>Check In Dashboard | Maker Nexus</title>
        <?php include '../../page_components/head_meta.php'; ?>
    </head>
    <body>
        <?php include '../../page_components/header.php'; ?>
        <div class="MN-Content main-dashboard">
            <div class="MODDiv">
                <div class="MODDivInner">
                    <div class="MODTitleDiv">
                        <p class="MODTitle">Maker On Duty</p>
                    </div>
                    <div class='MODImageDiv'>
                        <img class='MODImage' alt='MOD Photo' src='<?=$MODResult["photoURL"]; ?>' onerror="this.src='../../static/images/no_photo.png'">
                    </div>
                    <div class='MODNameDiv'>
                        <p class="MODName">
                            <?php print $MODResult["firstName"]; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="MembersDiv">
                <?php print $photodivs; ?>
            </div>
            <?php include '../../page_components/visitors.php'; ?>
        </div>
        <?php include '../../page_components/footer.php'; ?>
    </body>
</html>

<?php
    include '../../php_includes/close_db_connection.php';
    include '../../php_includes/cache_footer.php';
?>

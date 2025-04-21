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
?>

<div class="MODDiv">
    <div class="MODDivInner">
        <div class="MODTitleDiv">
            <p class="MODTitle">Maker On Duty</p>
        </div>
        <div class='MODImageDiv'>
            <img class='MODImage' alt='MOD Photo' src='<?=$MODResult["photoURL"]; ?>' onerror="this.src='../static/images/no_photo.png'">
        </div>
        <div class='MODNameDiv'>
            <p class="MODName">
                <?php print $MODResult["firstName"]; ?>
            </p>
        </div>
    </div>
</div>

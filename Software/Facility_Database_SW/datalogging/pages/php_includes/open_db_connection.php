<?php
    // Get the config info
    $ini_array = parse_ini_file("../../../rfidconfig.ini", true);
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
?>

<?php

// use this to check that a kiosk authorization token is valid.
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2019 Maker Nexus
// By Jim Schrempp
// 20250202 result is now cached for 15 seconds

// Include kiosk auth FIRST - must come before any output to set cookies
include 'kiosk_auth_check.php';  // Token-based authentication

include 'commonfunctions.php';

//allowWebAccess();  // if IP not allowed, then die

$today = new DateTime();
$today->setTimeZone(new DateTimeZone("America/Los_Angeles"));


?>
<html>
<head>
  <meta charset="utf-8">
  <title>Kiosk Permission Test</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Kiosk Permission Test</h1>
    <p>If you can see this page, then your kiosk token is valid and you have permission to access kiosk pages.</p>
    <p>Current date and time: <?php echo $today->format('Y-m-d H:i:s'); ?></p>
</body>
</html> 
<?php
// Logout page
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header("Location: login.php");
exit();
?>

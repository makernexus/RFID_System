<?php
// One-time setup script to initialize authentication system
// Run this script once to create the users table and default admin account
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'db_auth.php';

echo "<html><head><title>Auth Setup</title></head><body>";
echo "<h1>Setting up authentication system...</h1>";

try {
    initializeUsersTable();
    echo "<p style='color: green;'><strong>Success!</strong> Authentication system initialized.</p>";
    echo "<p>A default admin account has been created:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>Important:</strong> Please log in and change the default password immediately!</p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    echo "<hr>";
    echo "<p style='color: orange;'><strong>Security Note:</strong> After running this setup, you should delete or restrict access to this setup_auth.php file.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>

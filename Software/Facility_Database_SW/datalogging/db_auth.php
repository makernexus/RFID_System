<?php
// Database authentication functions
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

// Get database connection for authentication
function getAuthDbConnection() {
    $ini_array = parse_ini_file("rfidconfig.ini", true);
    $dbUser = $ini_array["SQL_DB"]["writeUser"];
    $dbPassword = $ini_array["SQL_DB"]["writePassword"];
    $dbName = $ini_array["SQL_DB"]["dataBaseName"];
    
    $con = mysqli_connect("localhost", $dbUser, $dbPassword, $dbName);
    
    if (mysqli_connect_errno()) {
        die("Failed to connect to MySQL: " . mysqli_connect_error());
    }
    
    return $con;
}

// Initialize the users table (call this once to set up the database)
function initializeUsersTable() {
    $con = getAuthDbConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS auth_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'accounting', 'reception', 'MoD') NOT NULL DEFAULT 'MoD',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        INDEX (username),
        INDEX (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    #if (mysqli_query($con, $sql)) {
        // Check if we need to create a default admin user
        $checkSql = "SELECT COUNT(*) as count FROM auth_users WHERE role='admin'";
        $result = mysqli_query($con, $checkSql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] == 0) {
            // Create default admin user: username=admin, password=admin123
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $insertSql = "INSERT INTO auth_users (username, password_hash, full_name, role) 
                          VALUES ('admin', '$defaultPassword', 'Default Admin', 'admin')";
            mysqli_query($con, $insertSql);
        }
   # }
    
    mysqli_close($con);
}

// Verify user credentials
function verifyUser($username, $password) {
    $con = getAuthDbConnection();
    
    $username = mysqli_real_escape_string($con, $username);
    
    $sql = "SELECT id, username, password_hash, full_name, role, is_active 
            FROM auth_users 
            WHERE username = '$username' AND is_active = 1";
    
    $result = mysqli_query($con, $sql);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password_hash'])) {
            // Update last login time
            $updateSql = "UPDATE auth_users SET last_login = NOW() WHERE id = " . $user['id'];
            mysqli_query($con, $updateSql);
            
            mysqli_close($con);
            return $user;
        }
    }
    
    mysqli_close($con);
    return false;
}

// Get all users (admin only)
function getAllUsers() {
    $con = getAuthDbConnection();
    
    $sql = "SELECT id, username, full_name, role, is_active, created_at, last_login 
            FROM auth_users 
            ORDER BY username";
    
    $result = mysqli_query($con, $sql);
    $users = array();
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    
    mysqli_close($con);
    return $users;
}

// Create a new user (admin only)
function createUser($username, $password, $fullName, $role) {
    $con = getAuthDbConnection();
    
    $username = mysqli_real_escape_string($con, $username);
    $fullName = mysqli_real_escape_string($con, $fullName);
    $role = mysqli_real_escape_string($con, $role);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO auth_users (username, password_hash, full_name, role) 
            VALUES ('$username', '$passwordHash', '$fullName', '$role')";
    
    $result = mysqli_query($con, $sql);
    mysqli_close($con);
    
    return $result;
}

// Update user (admin only)
function updateUser($userId, $username, $fullName, $role, $isActive) {
    $con = getAuthDbConnection();
    
    $username = mysqli_real_escape_string($con, $username);
    $fullName = mysqli_real_escape_string($con, $fullName);
    $role = mysqli_real_escape_string($con, $role);
    $isActive = $isActive ? 1 : 0;
    $userId = intval($userId);
    
    $sql = "UPDATE auth_users 
            SET username = '$username', full_name = '$fullName', role = '$role', is_active = $isActive
            WHERE id = $userId";
    
    $result = mysqli_query($con, $sql);
    mysqli_close($con);
    
    return $result;
}

// Change user password
function changePassword($userId, $newPassword) {
    $con = getAuthDbConnection();
    
    $userId = intval($userId);
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $sql = "UPDATE auth_users SET password_hash = '$passwordHash' WHERE id = $userId";
    
    $result = mysqli_query($con, $sql);
    
    if (!$result) {
        error_log("Password change error: " . mysqli_error($con));
    }
    
    mysqli_close($con);
    
    return $result;
}

// Get user by ID
function getUserById($userId) {
    $con = getAuthDbConnection();
    
    $userId = intval($userId);
    $sql = "SELECT * FROM auth_users WHERE id = $userId";
    $result = mysqli_query($con, $sql);
    
    $user = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    }
    
    mysqli_close($con);
    return $user;
}

// Delete user (admin only)
function deleteUser($userId) {
    $con = getAuthDbConnection();
    
    $userId = intval($userId);
    
    $sql = "DELETE FROM auth_users WHERE id = $userId";
    
    $result = mysqli_query($con, $sql);
    mysqli_close($con);
    
    return $result;
}

?>

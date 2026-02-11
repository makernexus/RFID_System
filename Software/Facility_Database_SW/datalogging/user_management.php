<?php
// User Management Page (Manager/Admin only)
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2026 Maker Nexus

include 'auth_check.php';
include 'db_auth.php';
require_once 'admin_log_functions.php';

// Only managers and admins can access this page
if (!isAdmin()) {
    die("Access denied. Only administrators can access this page.");
}

$message = '';
$messageType = '';

// Check for session messages (from redirects)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'MoD';
        
        if (!empty($username) && !empty($password) && !empty($fullName)) {
            if (createUser($username, $password, $fullName, $role)) {
                // Log the user creation
                $con = getAuthDbConnection();
                logAdminAction($con, 'create_user', $username, 'user', 
                    null, json_encode(['username' => $username, 'full_name' => $fullName, 'role' => $role]), 
                    'User created via user management');
                mysqli_close($con);
                
                $_SESSION['message'] = "User created successfully!";
                $_SESSION['messageType'] = 'success';
                header("Location: user_management.php");
                exit();
            } else {
                $message = "Error creating user. Username may already exist.";
                $messageType = 'error';
            }
        } else {
            $message = "All fields are required.";
            $messageType = 'error';
        }
    } elseif ($action === 'update') {
        $userId = $_POST['user_id'] ?? 0;
        $username = $_POST['username'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'MoD';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Get current user data before update for logging
        $con = getAuthDbConnection();
        $oldData = getUserById($userId);
        
        if (updateUser($userId, $username, $fullName, $role, $isActive)) {
            // Log the changes
            $newData = ['username' => $username, 'full_name' => $fullName, 'role' => $role, 'is_active' => $isActive];
            logAdminAction($con, 'update_user', $username, 'user', 
                json_encode(['username' => $oldData['username'], 'full_name' => $oldData['full_name'], 'role' => $oldData['role'], 'is_active' => $oldData['is_active']]), 
                json_encode($newData), 
                'User updated via user management');
            
            mysqli_close($con);
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['messageType'] = 'success';
            header("Location: user_management.php");
            exit();
        } else {
            mysqli_close($con);
            $message = "Error updating user.";
            $messageType = 'error';
        }
    } elseif ($action === 'change_password') {
        $userId = $_POST['user_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        if (!empty($newPassword) && $userId > 0) {
            $con = getAuthDbConnection();
            $userData = getUserById($userId);
            
            $result = changePassword($userId, $newPassword);
            if ($result) {
                // Log password change (don't log actual passwords)
                logAdminAction($con, 'change_password', $userData['username'], 'password', 
                    null, null, 
                    'Password changed for user: ' . $userData['username']);
                
                mysqli_close($con);
                $_SESSION['message'] = "Password changed successfully!";
                $_SESSION['messageType'] = 'success';
                header("Location: user_management.php");
                exit();
            } else {
                mysqli_close($con);
                $message = "Error changing password. Please check server logs for details.";
                $messageType = 'error';
            }
        } else {
            $message = "Password cannot be empty and user ID must be valid.";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $userId = $_POST['user_id'] ?? 0;
        
        // Prevent deleting yourself
        if ($userId != $_SESSION['user_id']) {
            $con = getAuthDbConnection();
            $userData = getUserById($userId);
            
            if (deleteUser($userId)) {
                // Log user deletion
                logAdminAction($con, 'delete_user', $userData['username'], 'user', 
                    json_encode(['username' => $userData['username'], 'full_name' => $userData['full_name'], 'role' => $userData['role']]), 
                    null, 
                    'User deleted via user management');
                
                mysqli_close($con);
                $_SESSION['message'] = "User deleted successfully!";
                $_SESSION['messageType'] = 'success';
                header("Location: user_management.php");
                exit();
            } else {
                mysqli_close($con);
                $message = "Error deleting user.";
                $messageType = 'error';
            }
        } else {
            $message = "You cannot delete your own account.";
            $messageType = 'error';
        }
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management - RFID Reports</title>
    <link href="style.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .user-info {
            font-size: 14px;
            color: #666;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section {
            background-color: #f9f9f9;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #555;
        }
        .form-group input, .form-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-danger:hover {
            background-color: #da190b;
        }
        .btn-secondary {
            background-color: #2196F3;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #0b7dda;
        }
        .btn-logout {
            background-color: #666;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
        }
        .btn-logout:hover {
            background-color: #555;
        }
        .btn-home {
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-right: 10px;
        }
        .btn-home:hover {
            background-color: #0b7dda;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.admin {
            background-color: #f44336;
            color: white;
        }
        .badge.manager {
            background-color: #ff9800;
            color: white;
        }
        .badge.reception {
            background-color: #9C27B0;
            color: white;
        }
        .badge.mod {
            background-color: #4CAF50;
            color: white;
        }
        .badge.active {
            background-color: #4CAF50;
            color: white;
        }
        .badge.inactive {
            background-color: #999;
            color: white;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 100px auto;
            padding: 30px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        .modal-close:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User Management</h1>
            <div style="text-align: right;">
                <div style="margin-bottom: 10px;">
                    <a href="rfidhome.php" class="btn-home">Reports Home</a>
                    <a href="admin_dashboard.php" class="btn-home">Admin Dashboard</a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
                <div class="user-info">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Create User Section -->
        <div class="section">
            <h2>Create New User</h2>
            <form method="POST" action="" id="createUserForm" autocomplete="off">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="create_username" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="password" id="create_password" name="password" required style="width: 180px;" autocomplete="new-password">
                        <button type="button" onclick="togglePasswordVisibility('create_password', 'create_toggle')" id="create_toggle" class="btn btn-secondary" style="padding: 8px 12px; min-width: auto;">Show</button>
                        <button type="button" onclick="generatePassword('create_password')" class="btn btn-secondary" style="padding: 8px 12px; min-width: auto;">Generate</button>
                    </div>
                    <small style="color: #666; display: block; margin-top: 5px;">Password will be visible to admin after generation</small>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" id="create_fullname" name="full_name" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role">
                        <option value="MoD">MoD (Normal User)</option>
                        <option value="reception">Reception</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
        
        <!-- Users List Section -->
        <div class="section">
            <h2>Existing Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <span class="badge <?php echo strtolower($user['role']); ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td class="actions">
                                <button class="btn btn-secondary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">Edit</button>
                                <button class="btn btn-secondary" onclick="changeUserPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Change Password</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" id="edit_role">
                        <option value="MoD">MoD (Normal User)</option>
                        <option value="reception">Reception</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Active:</label>
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                </div>
                <button type="submit" class="btn btn-primary">Update User</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Password</h2>
                <span class="modal-close" onclick="closePasswordModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="password_user_id">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="password_username" readonly style="background-color: #f0f0f0;">
                </div>
                <div class="form-group">
                    <label>New Password:</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="password" id="change_password" name="new_password" required style="width: 180px;">
                        <button type="button" onclick="togglePasswordVisibility('change_password', 'change_toggle')" id="change_toggle" class="btn btn-secondary" style="padding: 8px 12px; min-width: auto;">Show</button>
                        <button type="button" onclick="generatePassword('change_password')" class="btn btn-secondary" style="padding: 8px 12px; min-width: auto;">Generate</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function changeUserPassword(userId, username) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_username').value = username;
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Generate a random password
        function generatePassword(fieldId) {
            const length = 8;
            const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const numbers = "0123456789";
            const specials = "!@#$%^&*";
            let password = "";
            
            // Ensure at least one uppercase, one lowercase, one number, and one special
            password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
            password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += specials[Math.floor(Math.random() * specials.length)];
            
            // Fill the rest with letters and numbers only (4 more characters)
            const alphanumeric = letters + numbers;
            for (let i = password.length; i < length; i++) {
                password += alphanumeric[Math.floor(Math.random() * alphanumeric.length)];
            }
            
            // Shuffle the password
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            const field = document.getElementById(fieldId);
            field.value = password;
            field.type = 'text'; // Show the generated password
            
            // Update the toggle button
            const toggleBtn = fieldId === 'create_password' ? 
                document.getElementById('create_toggle') : 
                document.getElementById('change_toggle');
            if (toggleBtn) {
                toggleBtn.textContent = 'Hide';
            }
        }
        
        // Clear the create user form on page load
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('createUserForm').reset();
            document.getElementById('create_password').type = 'password';
            document.getElementById('create_toggle').textContent = 'Show';
        });
        
        // Toggle password visibility
        function togglePasswordVisibility(fieldId, buttonId) {
            const field = document.getElementById(fieldId);
            const button = document.getElementById(buttonId);
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = 'Hide';
            } else {
                field.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>
</body>
</html>

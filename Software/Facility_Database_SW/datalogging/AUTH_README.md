# Authentication System - Setup and Usage Guide

## Overview
A complete session-based authentication system has been implemented for your RFID reporting website with four user roles and full user management capabilities.

## Initial Setup

### Step 1: Run the Setup Script
1. Navigate to: `http://your-domain/datalogging/setup_auth.php`
2. This will create the `auth_users` table in your database
3. A default admin account will be created automatically
4. Log in to the Control Panel for your ISP and make sure the database user for this installation has Select, Insert, and Update permission.

### Step 2: Log in with Default Credentials
- **Username:** `admin`
- **Password:** `admin123`

### Step 3: Change the Default Password
1. After logging in, go to **User Management**
2. Click "Change Password" for the admin user
3. Set a secure password

### Step 4: Secure the Setup Script
After successful setup, you should:
- Delete `setup_auth.php` from your server, OR
- Restrict access to it via `.htaccess`

## User Roles

### 1. Admin
- Full access to all pages and reports
- Can manage users (create, edit, delete)
- Can change passwords for all users
- Can assign roles to users

### 2. Accounting
- Access to accounting-related reports
- Cannot manage users
- Can view all reports designated for accounting role

### 3. Reception
- Access to reception-related reports and functions
- Cannot manage users
- Can view all reports designated for reception role

### 4. MoD (Normal User)
- Standard access to reports
- Cannot manage users
- Limited to MoD-designated pages

## Files Created

### Core Authentication Files:
- `db_auth.php` - Database functions for user management
- `auth_check.php` - Session verification (include at top of protected pages)
- `login.php` - Login page
- `logout.php` - Logout functionality
- `user_management.php` - Admin interface for managing users
- `setup_auth.php` - One-time setup script

### Database Table:
```sql
auth_users
  - id (primary key)
  - username (unique)
  - password_hash (bcrypt)
  - full_name
  - role (admin/accounting/reception/MoD)
  - is_active (boolean)
  - created_at (timestamp)
  - last_login (timestamp)
```

## Protecting Additional Pages

To protect any PHP page, add this line at the very top (after `<?php`):

```php
include 'auth_check.php';
```

### Example:
```php
<?php
include 'auth_check.php';  // Require authentication
include 'commonfunctions.php';

// ... rest of your code
?>
```

### Role-Based Protection:
If you want to restrict a page to specific roles:

```php
<?php
include 'auth_check.php';
requireRole('admin');  // Only admins can access
// OR
requireRole(['admin', 'accounting']);  // Admins or accounting
?>
```

## Current Implementation Status

### Protected Pages:
✅ **rfidlast100members.php** - Now requires authentication

### Unprotected Pages (for you to protect as needed):
- rfidcurrentcheckins.php
- rfidcurrentcheckinsWithMOD.php
- rfidcurrentstudio.php
- rfidtop100.php
- rfidstudiousage.php
- And all other report pages...

## Session Management

- **Session Timeout:** 30 minutes of inactivity
- Sessions are automatically destroyed on logout
- Users are redirected to their originally requested page after login

## User Management Features

Admins can:
1. **Create Users** - Add new users with username, password, full name, and role
2. **Edit Users** - Modify username, full name, role, or active status
3. **Change Passwords** - Reset any user's password
4. **Delete Users** - Remove users (cannot delete yourself)
5. **View User Activity** - See last login times

## Security Features

✓ **Password Hashing** - Uses PHP's `password_hash()` with bcrypt  
✓ **Session Management** - Secure PHP sessions  
✓ **SQL Injection Protection** - Uses `mysqli_real_escape_string()`  
✓ **Session Timeout** - 30 minutes of inactivity  
✓ **XSS Protection** - Uses `htmlspecialchars()` for output  
✓ **Role-Based Access Control** - Three-tier permission system  

## Testing the System

1. Visit `setup_auth.php` to initialize the database
2. Go to `login.php` and log in with admin/admin123
3. Visit `user_management.php` to:
   - Change the admin password
   - Create additional test users
4. Test `rfidlast100members.php`:
   - Try accessing it without logging in (should redirect to login)
   - Log in and verify access
   - Log out and verify you're redirected to login again

## Next Steps

After testing rfidlast100members.php successfully:

1. Identify which other pages need protection
2. Add `include 'auth_check.php';` to those pages
3. Consider role requirements for different reports
4. Update index.html to indicate which pages are protected

## Troubleshooting

### "Access denied" errors:
- Check that user has correct role
- Verify session is active
- Check browser cookies are enabled

### Database connection errors:
- Verify rfidconfig.ini has correct writeUser credentials
- Ensure writeUser has permission to create tables

### Login redirects not working:
- Check that session_start() is called before any output
- Verify file permissions on session directory

## Best Practices

1. **Change default password immediately**
2. **Create individual accounts for each user**
3. **Regularly review user accounts**
4. **Deactivate (don't delete) users when they leave**
5. **Use strong passwords**
6. **Consider enabling HTTPS for production**

## Support

For issues or questions, refer to the code comments in:
- db_auth.php (database functions)
- auth_check.php (session verification)
- login.php (login interface)

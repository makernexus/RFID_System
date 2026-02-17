# User Roles Documentation

## Overview
The RFID system now supports multiple user roles with different permission levels.

## Available Roles

### Manager
- **Full system access** - highest permission level
- Can access all reports and configuration pages
- Can manage users (create, update, delete)
- Can modify client settings and photos
- Can access all debug and activity logs

### Admin  
- **Full system access** - same as Manager
- Can access all reports and configuration pages
- Can manage users (create, update, delete)
- Can modify client settings and photos
- Can access all debug and activity logs

**Note:** Manager and Admin roles have equivalent permissions. The distinction allows for organizational hierarchy if needed.

### MoD (Manager on Duty)
- Can access current check-in reports
- Can view check-in logs
- Can access some summary reports
- Limited configuration access

### Reception
- Basic access to check-in displays
- Can view current check-ins
- Cannot access configuration or detailed reports

## Permission Matrix

| Feature | Manager | Admin | MoD | Reception |
|---------|---------|-------|-----|-----------|
| Current CheckIns | ✓ | ✓ | ✓ | ✓ |
| CheckIn Logs | ✓ | ✓ | ✓ | - |
| Member Reports | ✓ | ✓ | - | - |
| Staff Activity | ✓ | ✓ | - | - |
| Studio Usage | ✓ | ✓ | - | - |
| Configuration | ✓ | ✓ | - | - |
| User Management | ✓ | ✓ | - | - |
| Debug Reports | ✓ | ✓ | - | - |

## Creating Users

Users can be created through the User Management interface (accessible to Manager/Admin only):

1. Navigate to Admin Dashboard → User Management
2. Click "Add New User"
3. Select the appropriate role from:
   - manager
   - admin
   - MoD
   - reception

## Role Implementation

Roles are checked using the `requireRole()` function in `auth_check.php`:

```php
// Single role
requireRole(['manager']);

// Multiple roles
requireRole(['manager', 'admin', 'MoD']);
```

Both Manager and Admin roles automatically pass all permission checks due to the logic in `requireRole()`.

## Database

Roles are stored in the `auth_users` table in the `role` column as strings.

Valid values:
- `manager`
- `admin`
- `MoD`
- `reception`

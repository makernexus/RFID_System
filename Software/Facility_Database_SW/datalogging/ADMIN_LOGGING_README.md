# Admin Activity Logging System

## Overview
The admin activity logging system tracks all changes made by administrators through the web interface. This provides an audit trail for compliance and troubleshooting purposes.

## Components

### 1. Database Table: `admin_log`
Location: `create_admin_log_table.sql`

**To create the table, run:**
```bash
mysql -u [writeUser] -p [databaseName] < create_admin_log_table.sql
```

**Table Structure:**
- `logID` - Auto-incrementing primary key
- `logDate` - Timestamp of the action (auto-populated)
- `adminUserID` - ID of the admin who made the change
- `adminUsername` - Username of the admin
- `actionType` - Type of action (update_mod, update_classes, photo_upload)
- `clientID` - ID of the client affected
- `fieldChanged` - Name of the field that was changed
- `beforeValue` - Value before the change (NULL for photo uploads)
- `afterValue` - Value after the change (NULL for photo uploads)
- `notes` - Optional notes about the change
- `ipAddress` - IP address of the admin

### 2. Logging Functions: `admin_log_functions.php`
Contains reusable functions for logging:

**`logAdminAction($connection, $actionType, $clientID, $fieldChanged, $beforeValue, $afterValue, $notes = '')`**
- Logs an admin action to the database
- Automatically captures admin info from session
- Returns true on success, false on failure

**`getClientDataForLogging($connection, $clientID)`**
- Retrieves current client data before making changes
- Used to capture "before" values
- Returns array with MOD_Eligible and displayClasses

### 3. Pages with Logging

#### rfidreportstaffmod.php
Logs changes to:
- `MOD_Eligible` field (action type: `update_mod`)
- `displayClasses` field (action type: `update_classes`)

#### rfidclientsearch.php
Logs:
- Photo uploads (action type: `photo_upload`)
- No before/after values for photos, just notes that photo was changed

### 4. Log Viewer: `rfidadminlog.php`
Web interface to view activity logs with:
- Filtering by Client ID
- Filtering by Admin Username
- Time range selection (7, 30, 90, 365 days)
- Displays up to 500 most recent logs
- Color-coded action badges
- Links to client detail pages

## Usage

### For Developers
To add logging to a new page:

1. Include the logging functions at the top:
   ```php
   require_once 'admin_log_functions.php';
   ```

2. Before updating data, get current values:
   ```php
   $beforeData = getClientDataForLogging($connection, $clientID);
   ```

3. After successful update, log the change:
   ```php
   logAdminAction($connection, 'action_type', $clientID, 'fieldName', 
                  $oldValue, $newValue, 'Optional notes');
   ```

### For Administrators
To view the activity log:
1. Log in as an admin
2. Navigate to `rfidadminlog.php`
3. Use filters to find specific activities
4. Click Client IDs to view member details

### Maintenance
**Archiving old logs:**
```sql
-- Create archive table
CREATE TABLE admin_log_archive LIKE admin_log;

-- Move logs older than 2 years
INSERT INTO admin_log_archive 
SELECT * FROM admin_log 
WHERE logDate < DATE_SUB(NOW(), INTERVAL 2 YEAR);

-- Delete archived logs
DELETE FROM admin_log 
WHERE logDate < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

**Query examples:**
```sql
-- All changes to a specific client
SELECT * FROM admin_log WHERE clientID = 'CLIENT123' ORDER BY logDate DESC;

-- All changes by a specific admin
SELECT * FROM admin_log WHERE adminUsername = 'jsmith' ORDER BY logDate DESC;

-- All MOD eligibility changes
SELECT * FROM admin_log WHERE actionType = 'update_mod' ORDER BY logDate DESC;

-- Changes in the last 24 hours
SELECT * FROM admin_log WHERE logDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

## Security Notes
- Only admins can make changes (enforced by role-based access control)
- IP addresses are logged for security tracking
- Logs are stored in the database with proper indexing for performance
- Session information is used to identify the admin making changes
- All logged values are sanitized using prepared statements

## Action Types
- `update_mod` - MOD_Eligible field changed
- `update_classes` - displayClasses field changed
- `photo_upload` - Client photo uploaded/updated

## Future Enhancements
Potential additions:
- Email notifications for critical changes
- Export logs to CSV
- More detailed change tracking (e.g., multiple fields in one action)
- Revert functionality to undo changes
- Dashboard with activity statistics

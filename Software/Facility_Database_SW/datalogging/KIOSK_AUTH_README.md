# Kiosk Authentication System

## Overview
The kiosk authentication system allows display screens (kiosks) to access protected pages without requiring manual login after each restart. This is perfect for lobby displays, workshop status boards, and other dedicated display screens.

## How It Works

### Traditional Authentication Problem
- Regular pages require login via username/password
- Sessions expire after 30 minutes of inactivity
- Kiosk reboots require manual re-login
- Not practical for unattended displays

### Kiosk Token Solution
- Admin generates a unique token for each kiosk
- One-time setup: visit a special URL on the kiosk browser
- Token stored in browser cookie (survives restarts)
- Token validates on each page load
- Revocable if compromised

## Setup Instructions

### 0. Create Database Table (One-Time Setup)

Before using the kiosk authentication system, you must manually create the `kiosk_tokens` table in your database. Run this SQL command:

```sql
CREATE TABLE kiosk_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    expires_at DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(100),
    INDEX (token),
    INDEX (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 1. Create a Kiosk Token (Admin)

1. Log in to the admin dashboard
2. Click **"🖥️ Manage Kiosk Tokens"**
3. Fill out the form:
   - **Kiosk Name**: e.g., "Lobby Display" (required)
   - **Location**: e.g., "Main Entrance" (optional)
   - **Expires In**: Choose expiration period or "Never"
4. Click **"Create Kiosk Token"**
5. **Copy the Setup URL** - you won't be able to retrieve it later!

### 2. Configure the Kiosk

1. Open the kiosk's browser
2. Paste the setup URL (looks like):
   ```
   https://your-domain.com/datalogging/rfidcurrentcheckinsWithMOD.php?kiosk_token=abc123...
   ```
3. The page will load and the token will be saved
4. Done! The kiosk is now authenticated

### 3. Protect Your Kiosk Pages

To protect a page with kiosk authentication, replace:
```php
include 'commonfunctions.php';
allowWebAccess();  // OLD: IP-based check
```

With:
NOTE the kiosk auth check MUST come first in the file before
any single character is sent to the browser. If not the token
cookie will not be saved!
```php
include 'kiosk_auth_check.php';  // NEW: Token-based authentication
```

**Example** - Update `rfidcurrentcheckinsWithMOD.php`:
```php
<?php
// Show photos of everyone who is checked in today.
include 'kiosk_auth_check.php';  // Replaces allowWebAccess()

$today = new DateTime();
// ... rest of your code
```

## Features

### Persistent Authentication
- Token stored in browser cookie
- Survives browser restarts
- Survives computer reboots
- No manual login required

### Security Features
- Unique 64-character random token per kiosk
- Tokens can be deactivated instantly
- Tokens can expire automatically
- Last-used timestamp tracking
- Admin-only token management

### Management Capabilities
- View all kiosks and their status
- Deactivate compromised tokens
- Reactivate tokens
- Delete old tokens
- Track last usage
- Set expiration dates

## Token Management

### Token States

| Status | Description |
|--------|-------------|
| **Active** | Token is valid and can authenticate |
| **Inactive** | Token disabled by admin, can be reactivated |
| **Expired** | Token past expiration date, must create new token |

### Admin Actions

**Deactivate**: Temporarily disable a token (e.g., kiosk being moved)
- Token stops working immediately
- Can be reactivated later

**Activate**: Re-enable a deactivated token
- Token starts working again
- Cannot activate expired tokens

**Delete**: Permanently remove a token
- Cannot be undone
- Kiosk must be set up again with new token

## Pages That Should Use Kiosk Auth

These display pages are good candidates:
- `rfidcurrentcheckinsWithMOD.php` - Current check-ins display
- `rfidcurrentcheckins.php` - Basic check-ins
- `rfidcurrentMOD.php` - MOD display
- `rfidmoddisplay.php` - MOD information
- `rfidcurrentstudio.php` - Studio status

## Set up a Raspberry Pi to be a kiosk

From the command line, see that you are denied
```
chromium --noerrdialogs --password-store=basic --disable-features=TranslateUI --user-data-dir=/home/admin/.config/chromium-kiosk http://rfid.makernexuswiki.com/kiosk_permissiontest.php &
```

Then set your token
```
https://rfid.makernexuswiki.com/kiosk_token_debug.php?kiosk_token=<<YOUR TOKEN HERE>>
```

See that you can get access
```
chromium --noerrdialogs --password-store=basic --disable-features=TranslateUI --user-data-dir=/home/admin/.config/chromium-kiosk http://rfid.makernexuswiki.com/kiosk_permissiontest.php &
```

Set up the RPi to boot as a kiosk
Disable screen blanking
```sudo raspi-config```
Set up autostart (if on an older RPi, look for other options)
```
mkdir -p ~/.config/labwc
nano ~/.config/labwc/autostart
```
add to the autostart
chromium --kiosk --noerrdialogs --password-store=basic --disable-features=TranslateUI --user-data-dir=/home/pi/.config/chromium-kiosk http://rfid.makernexuswiki.com/kiosk_permissiontest.php &
```
Make file executable
```
chmod +x ~/.config/labwc/autostart
```
To get out of Kiosk mode:  Alt F4


## Troubleshooting

### Kiosk Shows "Authentication Required" Error

**Possible causes:**
1. Token expired - Create new token and re-setup
2. Token deactivated - Reactivate in admin panel
3. Browser cookies cleared - Use setup URL again
4. Wrong browser used - Use same browser as setup

**Solution:**
- Visit the kiosk setup page as admin
- Find the kiosk in the list
- Click "Activate" if inactive
- Or create a new token if expired/deleted

### Token Not Saving

**Possible causes:**
1. Browser in private/incognito mode
2. Browser blocking cookies (might happen if the kiosk auth
   check is not the VERY FIRST thing, above all other includes)
3. Kiosk browser doesn't support cookies

**Solution:**
- Exit private browsing mode
- Enable cookies in browser settings
- Use a different browser (Chrome, Firefox recommended)

### "Token Cannot Be Retrieved" After Creation

This is by design for security. The token is only shown once during creation. If you lose it:
1. Delete the old token
2. Create a new token
3. Copy and save the setup URL immediately

## Security Best Practices

### Do:
- ✓ Create separate tokens for each kiosk
- ✓ Use descriptive names (location-based)
- ✓ Set expiration dates for temporary kiosks
- ✓ Regularly review active tokens
- ✓ Deactivate tokens for unused kiosks
- ✓ Delete tokens that are no longer needed

### Don't:
- ✗ Share token URLs publicly
- ✗ Email/text setup URLs (use secure method)
- ✗ Use same token for multiple kiosks
- ✗ Leave old tokens active indefinitely

## Database Schema

You must manually create this table before using the kiosk system:

```sql
CREATE TABLE kiosk_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    expires_at DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(100),
    INDEX (token),
    INDEX (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Advanced Usage

### Manual Token Validation in Code

If you need to check kiosk authentication in your code:

```php
include 'kiosk_auth_check.php';

// Get kiosk information
$kioskInfo = getKioskInfo();
echo "Kiosk: " . $kioskInfo['name'];
echo "Location: " . $kioskInfo['location'];
```

### Alternative Authentication Methods

The system checks for tokens in this order:
1. Cookie: `kiosk_token`
2. HTTP Header: `X-Kiosk-Token`
3. URL Parameter: `?kiosk_token=...`

This allows flexibility for different setup methods.

## Files

- **kiosk_auth_check.php** - Include this in kiosk pages
- **kiosk_setup.php** - Admin interface for token management
- **admin_dashboard.php** - Updated with kiosk management link

## Migration from IP-Based Auth

**Before:**
```php
include 'commonfunctions.php';
allowWebAccess();
```

**After:**
```php
include 'kiosk_auth_check.php';
```

That's it! The rest of your code remains unchanged.

## FAQ

**Q: Can I use both IP-based and token-based auth?**
A: Yes, but it's not recommended. Choose one method per page.

**Q: What happens if the token database is deleted?**
A: All kiosks will need to be set up again with new tokens.

**Q: Can I change a token after it's created?**
A: No. For security, tokens are immutable. Deactivate and create a new one.

**Q: How long do tokens last?**
A: Forever (unless you set an expiration date). The cookie persists until cleared.

**Q: Can users see their own tokens?**
A: No. Only admins can create and manage tokens.

**Q: What if someone steals my setup URL?**
A: Deactivate the token immediately and create a new one. Only send setup URLs via secure channels.

## Support

For issues or questions:
1. Check the kiosk status in the admin panel
2. Review browser console for errors
3. Check PHP error logs
4. Verify database table exists and is accessible

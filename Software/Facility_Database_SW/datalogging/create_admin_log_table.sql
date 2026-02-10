-- Admin Activity Log Table
-- Tracks changes made by administrators through web interface

CREATE TABLE IF NOT EXISTS admin_log (
    logID INT AUTO_INCREMENT PRIMARY KEY,
    logDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    adminUserID VARCHAR(50) NOT NULL,
    adminUsername VARCHAR(100) NOT NULL,
    actionType VARCHAR(50) NOT NULL,
    clientID VARCHAR(50) NOT NULL,
    fieldChanged VARCHAR(50) NOT NULL,
    beforeValue TEXT,
    afterValue TEXT,
    notes TEXT,
    ipAddress VARCHAR(45),
    INDEX idx_logDate (logDate),
    INDEX idx_adminUserID (adminUserID),
    INDEX idx_clientID (clientID),
    INDEX idx_actionType (actionType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example queries:
-- View all changes: SELECT * FROM admin_log ORDER BY logDate DESC LIMIT 100;
-- View changes for a client: SELECT * FROM admin_log WHERE clientID = 'client123' ORDER BY logDate DESC;
-- View changes by an admin: SELECT * FROM admin_log WHERE adminUserID = 'user123' ORDER BY logDate DESC;

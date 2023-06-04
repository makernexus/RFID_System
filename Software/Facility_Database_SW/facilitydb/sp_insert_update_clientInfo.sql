DELIMITER $$

-- This stored proc maintains the clientInfo table. When called it looks for
-- clientID in the table. It either inserts a new row or updates an existing
-- row with the parameters passed in.
-- 20230604 updated to include pictureURL. (also to correct isCheckedIn in the insert branch)

DELIMITER $$

BEGIN $$

DROP PROCEDURE IF EXISTS sp_insert_update_clientInfo$$

CREATE DEFINER=`makernexuswiki`@`localhost` PROCEDURE `sp_insert_update_clientInfo`(
    IN INclientID INT, 
    IN INfirstName VARCHAR(255), 
    IN INlastName VARCHAR(255), 
    IN INdateLastSeen DATETIME, 
    IN INisCheckedIn INT, 
    IN INpictureURL VARCHAR(255)
    )
BEGIN 

IF EXISTS (
    SELECT clientID FROM clientInfo 
	WHERE clientID = INclientID)
	
THEN
	UPDATE clientInfo set firstName=INfirstName, lastName=INLastName, pictureURL = INpictureURL,
    	dateLastSeen=INdateLastSeen 
    WHERE clientID = INclientID;
    
ELSE
	INSERT INTO clientInfo (clientID, firstName, lastName, dateLastSeen, isCheckedIn, pictureURL)
    VALUES (INclientID, INfirstName, INlastName, INdateLastSeen, INisCheckedIn, INpictureRUL);
    
END IF;


END$$
DELIMITER ;
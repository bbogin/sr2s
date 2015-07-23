-- Table: b02_activity

-- DROP TABLE `b02_activity`;

CREATE TABLE `timesheets`.`b02_activity` (
`activity_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`description` TEXT,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER b02_timestamp;
delimiter //
CREATE TRIGGER b02_timestamp BEFORE INSERT ON b02_activity
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


-- Table: b00_timelog

-- DROP TABLE `b00_timelog`;

CREATE TABLE `timesheets`.`b00_timelog` (
`timelog_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`activity_idref` INT NOT NULL DEFAULT '0',
`person_idref` INT NOT NULL DEFAULT '0',
`subtask_idref` INT NOT NULL DEFAULT '0',
`account_idref` INT NOT NULL DEFAULT '0',
`logdate` DATE NOT NULL ,
`hours` DOUBLE NOT NULL DEFAULT 0.00,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER b00_timestamp;
delimiter //
CREATE TRIGGER b00_timestamp BEFORE INSERT ON b00_timelog
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.logdate = 0) THEN
    SET NEW.logdate = CURDATE();
  END IF;
END
//
delimiter ;


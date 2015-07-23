-- Table: c00_person

-- DROP TABLE `c00_person`;

CREATE TABLE `timesheets`.`c00_person` (
`person_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`lastname` VARCHAR( 64 ) NOT NULL DEFAULT 'who?',
`lastsoundex` VARCHAR( 64 ) NOT NULL DEFAULT '??',
`firstname` VARCHAR( 64 ) NOT NULL DEFAULT 'who?',
`loginname` VARCHAR( 64 ) NULL ,
`password` VARCHAR( 64 ) NULL ,
`email` VARCHAR( 64 ) NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER c00_timestamp;
delimiter //
CREATE TRIGGER c00_timestamp BEFORE INSERT ON c00_person
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


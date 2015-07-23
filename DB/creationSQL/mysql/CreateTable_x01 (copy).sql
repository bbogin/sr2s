
-- Create MySql table:
-- Table: x01_classcount

-- DROP TABLE `x01_classcount`;

CREATE TABLE `timesheets`.`x01_classcount` (
`classcount_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`timelog_idref` INT NOT NULL DEFAULT '0',
`class_idref` INT NOT NULL DEFAULT '0',
`role` INT NOT NULL DEFAULT '0',
`count` INT NOT NULL DEFAULT '0',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

-- DROP TRIGGER x01_timestamp;
delimiter //
CREATE TRIGGER x01_timestamp BEFORE INSERT ON x01_classcount
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


-- Table: a20_accounting

-- DROP TABLE `a20_accounting`;

CREATE TABLE `timesheets`.`a20_accounting` (
`accounting_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organization_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`comment` TEXT NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a20_timestamp;
delimiter //
CREATE TRIGGER a20_timestamp BEFORE INSERT ON a20_accounting
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


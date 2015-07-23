-- Table: a21_account

-- DROP TABLE `a21_account`;

CREATE TABLE `timesheets`.`a21_account` (
`account_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`accounting_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a21_timestamp;
delimiter //
CREATE TRIGGER a21_timestamp BEFORE INSERT ON a21_account
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


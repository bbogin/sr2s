
-- Table: a30_event

-- DROP TABLE `a30_event`;

CREATE TABLE `timesheets`.`a30_event` (
`event_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`project_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`budget` DOUBLE NOT NULL DEFAULT 0.00,
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

-- DROP TRIGGER a30_timestamp;
delimiter //
CREATE TRIGGER a30_timestamp BEFORE INSERT ON a30_event
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: b10_eventlog

-- DROP TABLE `b10_eventlog`;

CREATE TABLE `timesheets`.`b10_eventlog` (
`eventlog_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`project_idref` INT NOT NULL DEFAULT '0',
`event_idref` INT NOT NULL DEFAULT '0',
`person_idref` INT NOT NULL DEFAULT '0',
`account_idref` INT NOT NULL DEFAULT '0',
`session_count` INT NOT NULL DEFAULT '0',
`attendance` INT NOT NULL DEFAULT '0',
`logdate` DATE NOT NULL ,
`comments` VARCHAR( 256 ) NOT NULL DEFAULT '',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

-- DROP TRIGGER b10_timestamp;
delimiter //
CREATE TRIGGER b10_timestamp BEFORE INSERT ON b10_eventlog
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


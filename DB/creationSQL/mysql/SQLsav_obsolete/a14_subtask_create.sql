-- Table: a14_subtask

-- DROP TABLE `a14_subtask`;

CREATE TABLE `timesheets`.`a14_subtask` (
`subtask_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`task_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a14_timestamp;
delimiter //
CREATE TRIGGER a14_timestamp BEFORE INSERT ON a14_subtask
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


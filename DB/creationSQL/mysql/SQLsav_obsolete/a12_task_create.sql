-- Table: a12_task

-- DROP TABLE `a12_task`;

CREATE TABLE `timesheets`.`a12_task` (
`task_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`project_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`budget` DOUBLE NOT NULL DEFAULT 0.00,
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a12_timestamp;
delimiter //
CREATE TRIGGER a12_timestamp BEFORE INSERT ON a12_task
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


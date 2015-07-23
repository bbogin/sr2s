-- Table: a10_project

-- DROP TABLE `a10_project`;

CREATE TABLE `timesheets`.`a10_project` (
`project_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organization_idref` INT NOT NULL DEFAULT '0',
`accounting_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new project',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new project',
`comment` TEXT NULL ,
`inactive_asof` DATE NULL ,
`close_date` DATE NOT NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a10_timestamp;
delimiter //
CREATE TRIGGER a10_timestamp BEFORE INSERT ON a10_project
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.close_date = 0) THEN
    SET NEW.close_date = CURDATE();
  END IF;
END
//
delimiter ;


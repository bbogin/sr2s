-- Table: a00_organization

-- DROP TABLE `a00_organization`;

CREATE TABLE `timesheets`.`a00_organization` (
`organization_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new organization',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new organization',
`logo` MEDIUMBLOB,
`logo_type` VARCHAR( 8 ) NOT NULL DEFAULT 'jpeg',
`currency_idref` INT NOT NULL DEFAULT '1',
`timezone` SMALLINT NOT NULL DEFAULT '-8' COMMENT 'TimeZoneOffset from Greenwich, in hours; west is negative',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER a00_timestamp;
delimiter //
CREATE TRIGGER a00_timestamp BEFORE INSERT ON a00_organization
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


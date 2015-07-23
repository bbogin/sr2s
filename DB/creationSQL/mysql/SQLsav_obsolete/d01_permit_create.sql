-- Table: d01_permit

-- DROP TABLE `d01_permit`;

CREATE TABLE `timesheets`.`d01_permit` (
`permit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 16 ) NOT NULL DEFAULT 'new permit',
`description` VARCHAR( 128 ) NOT NULL DEFAULT 'new permit',
`comment` TEXT NULL ,
`grade` SMALLINT NOT NULL DEFAULT '10' COMMENT 'Grades the permissions for convenient display grouping when doing grant/revoke:
0 = any signed on user
5 = organization administrator
10 = superuser',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER d01_permit;
delimiter //
CREATE TRIGGER d01_timestamp BEFORE INSERT ON d01_permit
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


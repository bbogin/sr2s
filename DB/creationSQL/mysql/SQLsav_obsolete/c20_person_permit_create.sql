-- Table: c20_person_permit

-- DROP TABLE `c20_person_permit`;

CREATE TABLE `timesheets`.`c20_person_permit` (
`person_permit_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_organization_idref` INT NOT NULL DEFAULT '0',
`permit_idref` INT NOT NULL DEFAULT '0',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM COMMENT = 'Connect a person to an organization - many-to-many - and is the connecting point for person properties that are organization specific, eg. permits';

-- DROP TRIGGER c20_timestamp;
delimiter //
CREATE TRIGGER c20_timestamp BEFORE INSERT ON c20_person_permit
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


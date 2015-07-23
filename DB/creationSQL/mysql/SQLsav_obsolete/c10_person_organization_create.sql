-- Table: c10_person_organization

-- DROP TABLE `c10_person_organization`;

CREATE TABLE `timesheets`.`c10_person_organization` (
`person_organization_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_idref` INT NOT NULL DEFAULT '0',
`organization_idref` INT NOT NULL DEFAULT '0',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM COMMENT = 'Connect a person to an organization - many-to-many - and is the connecting point for person properties that are organization specific, eg. permits';

-- DROP TRIGGER c10_timestamp;
delimiter //
CREATE TRIGGER c10_timestamp BEFORE INSERT ON c10_person_organization
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;


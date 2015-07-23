-- Table: c02_rate

-- DROP TABLE c02_rate;

CREATE TABLE `timesheets`.`c02_rate` (
`rate_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_idref` INT NOT NULL DEFAULT '0',
`project_idref` INT NOT NULL DEFAULT '0',
`rate` DOUBLE NOT NULL DEFAULT 0.00,
`effective_asof` DATE NOT NULL ,
`expire_asof` DATE NULL COMMENT 'De-normalized: one day less than next up effective_asof',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = MYISAM ;

-- DROP TRIGGER c02_timestamp;
delimiter //
CREATE TRIGGER c02_timestamp BEFORE INSERT ON c02_rate
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.effective_asof = 0) THEN
    SET NEW.effective_asof = DATE_SUB(CURDATE(), INTERVAL 2 YEAR);
  END IF;
END
//
delimiter ;


-- to create triggers, must put DB name before trigger and table names...


-- Create MySql tables:
-- note: the "DROP" statement must be a comment with two dashes + one space + "DROP" in order to be activated by
-- the creation script, ie. the script will change "-- DROP" to "DROP" if the option to drop is selected.

-- Table: t_a00_organization

-- DROP TRIGGER `timesheets`.`t_a00_timestamp`;
-- DROP TABLE `t_a00_organization`;

CREATE TABLE `timesheets`.`t_a00_organization` (
`organization_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new organization',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new organization',
`logo` MEDIUMBLOB,
`logo_type` VARCHAR( 8 ) NOT NULL DEFAULT 'jpeg',
`currency_idref` INT NOT NULL DEFAULT '1',
`timezone` SMALLINT NOT NULL DEFAULT '-8' COMMENT 'TimeZoneOffset from Greenwich, in hours; west is negative',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a00_timestamp` BEFORE INSERT ON `timesheets`.`t_a00_organization`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_a10_project

-- DROP TRIGGER `timesheets`.`t_a10_timestamp`;
-- DROP TABLE `t_a10_project`;

CREATE TABLE `timesheets`.`t_a10_project` (
`project_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organization_idref` INT NOT NULL DEFAULT '0',
`accounting_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new project',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new project',
`comment` TEXT NULL ,
`inactive_asof` DATE NULL ,
`close_date` DATE NOT NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a10_timestamp` BEFORE INSERT ON `timesheets`.`t_a10_project`
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.close_date = 0) THEN
    SET NEW.close_date = CURDATE();
  END IF;
END
//
delimiter ;

-- Table: t_a12_task

-- DROP TRIGGER `timesheets`.`t_a12_timestamp`;
-- DROP TABLE `timesheets`.`t_a12_task`;

CREATE TABLE `timesheets`.`t_a12_task` (
`task_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`project_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`budget` DOUBLE NOT NULL DEFAULT 0.00,
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a12_timestamp` BEFORE INSERT ON `timesheets`.`t_a12_task`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_a14_subtask

-- DROP TRIGGER `timesheets`.`t_a14_timestamp`;
-- DROP TABLE `timesheets`.`t_a14_subtask`;

CREATE TABLE `timesheets`.`t_a14_subtask` (
`subtask_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`task_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`extension` VARCHAR( 64 ) NULL DEFAULT NULL,
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a14_timestamp` BEFORE INSERT ON `timesheets`.`t_a14_subtask`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_a20_accounting

-- DROP TRIGGER `timesheets`.`t_a20_timestamp`;
-- DROP TABLE `timesheets`.`t_a20_accounting`;

CREATE TABLE `timesheets`.`t_a20_accounting` (
`accounting_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organization_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`comment` TEXT NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a20_timestamp` BEFORE INSERT ON `timesheets`.`t_a20_accounting`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_a21_account

-- DROP TRIGGER `timesheets`.`t_a21_timestamp`;
-- DROP TABLE `timesheets`.`t_a21_account`;

CREATE TABLE `timesheets`.`t_a21_account` (
`account_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`accounting_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a21_timestamp` BEFORE INSERT ON `timesheets`.`t_a21_account`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_a30_event

-- DROP TRIGGER `timesheets`.`t_a30_timestamp`;
-- DROP TABLE `timesheets`.`t_a30_event`;

CREATE TABLE `timesheets`.`t_a30_event` (
`event_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`project_idref` INT NOT NULL DEFAULT '0',
`name` VARCHAR( 64 ) NOT NULL DEFAULT 'new task',
`description` VARCHAR( 256 ) NOT NULL DEFAULT 'new task',
`budget` DOUBLE NOT NULL DEFAULT 0.00,
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_a30_timestamp` BEFORE INSERT ON `timesheets`.`t_a30_event`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_b00_timelog

-- DROP TRIGGER `timesheets`.`t_b00_timestamp`;
-- DROP TABLE `timesheets`.`t_b00_timelog`;

CREATE TABLE `timesheets`.`t_b00_timelog` (
`timelog_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`activity_idref` INT NOT NULL DEFAULT '0',
`person_idref` INT NOT NULL DEFAULT '0',
`subtask_idref` INT NOT NULL DEFAULT '0',
`account_idref` INT NOT NULL DEFAULT '0',
`logdate` DATE NOT NULL ,
`hours` DOUBLE NOT NULL DEFAULT 0.00,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_b00_timestamp` BEFORE INSERT ON `timesheets`.`t_b00_timelog`
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.logdate = 0) THEN
    SET NEW.logdate = CURDATE();
  END IF;
END
//
delimiter ;

-- Table: t_b02_activity

-- DROP TRIGGER `timesheets`.`t_b02_timestamp`;
-- DROP TABLE `timesheets`.`t_b02_activity`;

CREATE TABLE `timesheets`.`t_b02_activity` (
`activity_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`description` TEXT,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_b02_timestamp` BEFORE INSERT ON `timesheets`.`t_b02_activity`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_b10_eventlog

-- DROP TRIGGER `timesheets`.`t_b10_timestamp`;
-- DROP TABLE `timesheets`.`t_b10_eventlog`;

CREATE TABLE `timesheets`.`t_b10_eventlog` (
`eventlog_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`event_idref` INT NOT NULL DEFAULT '0',
`person_idref` INT NOT NULL DEFAULT '0',
`account_idref` INT NOT NULL DEFAULT '0',
`session_count` INT NOT NULL DEFAULT '0',
`attendance` INT NOT NULL DEFAULT '0',
`logdate` DATE NOT NULL ,
`comments` VARCHAR( 256 ) NOT NULL DEFAULT '',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_b10_timestamp` BEFORE INSERT ON `timesheets`.`t_b10_eventlog`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_c00_person

-- DROP TRIGGER `timesheets`.`t_c00_timestamp`;
-- DROP TABLE `timesheets`.`t_c00_person`;

CREATE TABLE `timesheets`.`t_c00_person` (
`person_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`lastname` VARCHAR( 64 ) NOT NULL DEFAULT 'who?',
`lastsoundex` VARCHAR( 64 ) NOT NULL DEFAULT '??',
`firstname` VARCHAR( 64 ) NOT NULL DEFAULT 'who?',
`loginname` VARCHAR( 64 ) NULL ,
`password` VARCHAR( 255 ) NULL ,
`email` VARCHAR( 64 ) NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_c00_timestamp` BEFORE INSERT ON `timesheets`.`t_c00_person`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_c02_rate

-- DROP TRIGGER `timesheets`.`t_c02_timestamp`;
-- DROP TABLE `timesheets`.`t_c02_rate`;

CREATE TABLE `timesheets`.`t_c02_rate` (
`rate_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_idref` INT NOT NULL DEFAULT '0',
`project_idref` INT NOT NULL DEFAULT '0',
`rate` DOUBLE NOT NULL DEFAULT 0.00,
`effective_asof` DATE NOT NULL ,
`expire_after` DATE NULL COMMENT 'De-normalized: one day less than next up effective_asof',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_c02_timestamp` BEFORE INSERT ON `timesheets`.`t_c02_rate`
FOR EACH ROW
BEGIN
  SET NEW.timestamp = UTC_TIMESTAMP();
  IF (NEW.effective_asof = 0) THEN
    SET NEW.effective_asof = DATE_SUB(CURDATE(), INTERVAL 2 YEAR);
  END IF;
END
//
delimiter ;

-- Table: t_c10_person_organization

-- DROP TRIGGER `timesheets`.`t_c10_timestamp`;
-- DROP TABLE `timesheets`.`t_c10_person_organization`;

CREATE TABLE `timesheets`.`t_c10_person_organization` (
`person_organization_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_idref` INT NOT NULL DEFAULT '0',
`organization_idref` INT NOT NULL DEFAULT '0',
`inactive_asof` DATE NULL ,
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB COMMENT = 'Connect a person to an organization - many-to-many - 
the connecting point for person properties that are organization specific';

delimiter //
CREATE TRIGGER `timesheets`.`t_c10_timestamp` BEFORE INSERT ON `timesheets`.`t_c10_person_organization`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_c20_person_permit

-- DROP TRIGGER `timesheets`.`t_c20_timestamp`;
-- DROP TABLE `timesheets`.`t_c20_person_permit`;

CREATE TABLE `timesheets`.`t_c20_person_permit` (
`person_permit_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`person_idref` INT NOT NULL DEFAULT '0',
`permit_idref` INT NOT NULL DEFAULT '0',
`organization_idref` INT NOT NULL DEFAULT '0',
`project_idref` INT NOT NULL DEFAULT '0',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB COMMENT = 'connect a person to a permission - many-to-many';

delimiter //
CREATE TRIGGER `timesheets`.`t_c20_timestamp` BEFORE INSERT ON `timesheets`.`t_c20_person_permit`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_d01_permit

-- DROP TRIGGER `timesheets`.`t_d01_timestamp`;
-- DROP TABLE `timesheets`.`t_d01_permit`;

CREATE TABLE `timesheets`.`t_d01_permit` (
`permit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 16 ) NOT NULL DEFAULT 'new permit',
`description` VARCHAR( 128 ) NOT NULL DEFAULT 'new permit',
`comment` TEXT NULL ,
`grade` SMALLINT NOT NULL DEFAULT '10' COMMENT 'Security grade: 1 = system wide, 10 = organization specific, 100 = project specific',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_d01_timestamp` BEFORE INSERT ON `timesheets`.`t_d01_permit`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- Table: t_d02_currency

-- DROP TABLE `timesheets`.`t_d02_currency`;

CREATE TABLE `timesheets`.`t_d02_currency` (
`currency_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 32 ) NOT NULL DEFAULT 'US dollar',
`symbol` VARCHAR( 8 ) NOT NULL DEFAULT '$',
`decimal_cnt` INT NOT NULL DEFAULT '2'
) ENGINE = INNODB ;

-- Table: t_d10_preferences

-- DROP TRIGGER `timesheets`.`t_d10_timestamp`;
-- DROP TABLE `timesheets`.`t_d10_preferences`;

CREATE TABLE `timesheets`.`t_d10_preferences` (
`preferences_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organization_idref` INT NOT NULL DEFAULT '0',
`project_idref` INT NOT NULL DEFAULT '0',
`person_idref` INT NOT NULL DEFAULT '0',
`theme` VARCHAR( 64 ) NOT NULL DEFAULT '',
`menu` VARCHAR( 512 ) NOT NULL DEFAULT '',
`date` VARCHAR( 16 ) NOT NULL DEFAULT '',
`currency_idref` INT NOT NULL DEFAULT '1',
`decimal_char` CHAR(1) NOT NULL DEFAULT '.',
`timestamp` DATETIME NOT NULL COMMENT 'Time is UTC, alias GMT, alias Greenwich Mean Time (created by timestamp trigger)'
) ENGINE = INNODB ;

delimiter //
CREATE TRIGGER `timesheets`.`t_d10_timestamp` BEFORE INSERT ON `timesheets`.`t_d10_preferences`
FOR EACH ROW
  SET NEW.timestamp = UTC_TIMESTAMP()
//
delimiter ;

-- View: t_v00_timelog

-- DROP VIEW `timesheets`.`t_v00_timelog`;

CREATE OR REPLACE VIEW `timesheets`.`t_v00_timelog` AS 
 SELECT b00.timelog_id, b00.logdate, b00.hours,
        b02.activity_id, b02.description AS activity,
        a14.subtask_id, a14.name AS subtask, a14.description AS subtask_desc, a14.extension, a14.inactive_asof AS subtask_inactive_asof,
        a12.task_id, a12.name AS task, a12.description AS task_desc, a12.inactive_asof AS task_inactive_asof,
        a10.project_id, a10.name AS project, a10.description AS project_desc,
        a21.account_id, a21.name AS account, a21.description AS account_desc, a21.inactive_asof AS account_inactive_asof,
        a00.organization_id, c00.person_id
   FROM t_b00_timelog b00
   JOIN t_b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN t_a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN t_a12_task a12 ON a12.task_id = a14.task_idref
   JOIN t_a10_project a10 ON a10.project_id = a12.project_idref
   JOIN t_a00_organization a00 ON a00.organization_id = a10.organization_idref
   JOIN t_c00_person c00 ON c00.person_id = b00.person_idref
   JOIN t_a21_account a21 ON a21.account_id = b00.account_idref
  ORDER BY b00.logdate;

-- View: t_v10_logreport

-- DROP VIEW `timesheets`.`t_v10_logreport`;

CREATE OR REPLACE VIEW `timesheets`.`t_v10_logreport` AS 
 SELECT a12.project_idref AS project_id, b00.logdate, b00.hours, b02.description AS activity, b02.activity_id,
        a14.name AS subtask, a14.description AS subtask_desc, a12.name AS task, a12.description AS task_desc,
        a21.name AS account, a21.description AS account_desc, c00.lastname, c00.firstname, c00.person_id, rate
   FROM t_b00_timelog b00
   JOIN t_b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN t_a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN t_a12_task a12 ON a12.task_id = a14.task_idref
   JOIN t_a21_account a21 ON a21.account_id = b00.account_idref
   JOIN t_c00_person c00 ON c00.person_id = b00.person_idref
   JOIN t_c02_rate c02 ON c02.person_idref = b00.person_idref AND c02.project_idref = a12.project_idref
  WHERE logdate >= c02.effective_asof AND (c02.expire_after IS NULL OR logdate <= c02.expire_after)
  ORDER BY b00.logdate;

-- View: t_v12_taskreport

-- DROP VIEW `timesheets`.`t_v12_taskreport`;

CREATE OR REPLACE VIEW `timesheets`.`t_v12_taskreport` AS 
 SELECT project_idref AS project_id, task_id, a12.name AS taskname, a12.description AS task_desc, budget,
        a12.inactive_asof AS task_inactive_asof, a14.name AS subtaskname, a14.description AS subtask_desc,
        a14.inactive_asof AS subtask_inactive_asof
   FROM t_a12_task AS a12
   JOIN t_a14_subtask a14 ON a14.task_idref = a12.task_id;


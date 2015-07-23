-- Table: d02_currency

-- DROP TABLE `d02_currency`;

CREATE TABLE `timesheets`.`d02_currency` (
`currency_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` VARCHAR( 32 ) NOT NULL DEFAULT 'US dollar',
`symbol` VARCHAR( 8 ) NOT NULL DEFAULT '$'
) ENGINE = MYISAM ;


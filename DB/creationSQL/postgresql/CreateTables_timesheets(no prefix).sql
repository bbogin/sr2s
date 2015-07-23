-- Create PostgreSql tables:
-- note: the "DROP" statement must be a comment with two dashes + one space + "DROP" in order to be activated by
-- the creation script, ie. the script will change "-- DROP" to "DROP" if the option to drop is selected.

-- Table: a00_organization

-- DROP TABLE a00_organization;

CREATE TABLE a00_organization
(
  organization_id serial NOT NULL,
  "name" character varying(64) NOT NULL DEFAULT 'new organization'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new organization'::character varying,
  logo oid NOT NULL DEFAULT 0,
  logo_type character varying(8) NOT NULL DEFAULT 'jpeg'::character varying,
  currency_idref integer NOT NULL DEFAULT 1,
  timezone smallint NOT NULL DEFAULT (-8), -- TimeZoneOffset from Greenwich, in hours; west is negative
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT organization_Key PRIMARY KEY (organization_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a00_organization OWNER TO ts_admin;
GRANT ALL ON TABLE a00_organization TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a00_organization TO ts_editor;
GRANT SELECT ON TABLE a00_organization TO ts_reader;
COMMENT ON COLUMN a00_organization."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
COMMENT ON COLUMN a00_organization.timezone IS 'TimeZoneOffset from Greenwich, in hours; west is negative';
GRANT UPDATE ON TABLE a00_organization_organization_ID_seq TO ts_editor;

-- Table: a10_project

-- DROP TABLE a10_project;

CREATE TABLE a10_project
(
  project_id serial NOT NULL,
  organization_idref integer NOT NULL DEFAULT 0,
  accounting_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new project'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new project'::character varying,
  "comment" text,
  inactive_asof date,
  close_date date NOT NULL DEFAULT now(),
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT project_key PRIMARY KEY (project_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a10_project OWNER TO ts_admin;
GRANT ALL ON TABLE a10_project TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a10_project TO ts_editor;
GRANT SELECT ON TABLE a10_project TO ts_reader;
COMMENT ON COLUMN a10_project."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a10_project_project_id_seq TO ts_editor;

-- Table: a12_task

-- DROP TABLE a12_task;

CREATE TABLE a12_task
(
  task_id serial NOT NULL,
  project_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  budget numeric NOT NULL DEFAULT 0.00,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT task_Key PRIMARY KEY (task_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a12_task OWNER TO ts_admin;
GRANT ALL ON TABLE a12_task TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a12_task TO ts_editor;
GRANT SELECT ON TABLE a12_task TO ts_reader;
COMMENT ON COLUMN a12_task."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a12_task_task_id_seq TO ts_editor;

-- Table: a14_subtask

-- DROP TABLE a14_subtask;

CREATE TABLE a14_subtask
(
  subtask_id serial NOT NULL,
  task_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new subtask'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new subtask'::character varying,
  extension character varying(64) NULL DEFAULT NULL,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT subtask_Key PRIMARY KEY (subtask_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a14_subtask OWNER TO ts_admin;
GRANT ALL ON TABLE a14_subtask TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a14_subtask TO ts_editor;
GRANT SELECT ON TABLE a14_subtask TO ts_reader;
COMMENT ON COLUMN a14_subtask."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a14_subtask_subtask_id_seq TO ts_editor;

-- Table: a20_accounting

-- DROP TABLE a20_accounting;

CREATE TABLE a20_accounting
(
  accounting_id serial NOT NULL,
  organization_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  "comment" text,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT accounting_Key PRIMARY KEY (accounting_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a20_accounting OWNER TO ts_admin;
GRANT ALL ON TABLE a20_accounting TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a20_accounting TO ts_editor;
GRANT SELECT ON TABLE a20_accounting TO ts_reader;
COMMENT ON COLUMN a20_accounting."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a20_accounting_accounting_id_seq TO ts_editor;

-- Table: a21_account

-- DROP TABLE a21_account;

CREATE TABLE a21_account
(
  account_id serial NOT NULL,
  accounting_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT account_Key PRIMARY KEY (account_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a21_account OWNER TO ts_admin;
GRANT ALL ON TABLE a21_account TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a21_account TO ts_editor;
GRANT SELECT ON TABLE a21_account TO ts_reader;
COMMENT ON COLUMN a21_account."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a21_account_account_id_seq TO ts_editor;

-- Table: a30_event

-- DROP TABLE a30_event;

CREATE TABLE a30_event
(
  event_id serial NOT NULL,
  project_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  budget numeric NOT NULL DEFAULT 0.00,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT event_Key PRIMARY KEY (event_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a30_event OWNER TO ts_admin;
GRANT ALL ON TABLE a30_event TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a30_event TO ts_editor;
GRANT SELECT ON TABLE a30_event TO ts_reader;
COMMENT ON COLUMN a30_event."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a30_event_event_id_seq TO ts_editor;

-- Table: b00_timelog

-- DROP TABLE b00_timelog;

CREATE TABLE b00_timelog
(
  timelog_id serial NOT NULL,
  activity_idref integer NOT NULL DEFAULT 0,
  person_idref integer NOT NULL DEFAULT 0,
  subtask_idref integer NOT NULL DEFAULT 0,
  account_idref integer NOT NULL DEFAULT 0,
  logdate date NOT NULL DEFAULT now(),
  hours numeric(4,2) NOT NULL DEFAULT 0.00,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT timelog_Key PRIMARY KEY (timelog_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE b00_timelog OWNER TO ts_admin;
GRANT ALL ON TABLE b00_timelog TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE b00_timelog TO ts_editor;
GRANT SELECT ON TABLE b00_timelog TO ts_reader;
COMMENT ON COLUMN b00_timelog."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE b00_timelog_timelog_id_seq TO ts_editor;

-- Table: b02_activity

-- DROP TABLE b02_activity;

CREATE TABLE b02_activity
(
  activity_id serial NOT NULL,
  description text,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT activity_Key PRIMARY KEY (activity_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE b02_activity OWNER TO ts_admin;
GRANT ALL ON TABLE b02_activity TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE b02_activity TO ts_editor;
GRANT SELECT ON TABLE b02_activity TO ts_reader;
COMMENT ON COLUMN b02_activity."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE b02_activity_activity_id_seq TO ts_editor;

-- Table: b10_eventlog

-- DROP TABLE b10_eventlog;

CREATE TABLE b10_eventlog
(
  eventlog_id serial NOT NULL,
  event_idref integer NOT NULL DEFAULT 0,
  person_idref integer NOT NULL DEFAULT 0,
  account_idref integer NOT NULL DEFAULT 0,
  session_count integer NOT NULL DEFAULT 0,
  attendance integer NOT NULL DEFAULT 0,
  logdate date NOT NULL DEFAULT now(),
  "comments" character varying(256) NOT NULL DEFAULT ''::character varying,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT eventlog_Key PRIMARY KEY (eventlog_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE b10_eventlog OWNER TO ts_admin;
GRANT ALL ON TABLE b10_eventlog TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE b10_eventlog TO ts_editor;
GRANT SELECT ON TABLE b10_eventlog TO ts_reader;
COMMENT ON COLUMN b10_eventlog."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE b10_eventlog_eventlog_id_seq TO ts_editor;

-- Table: c00_person

-- DROP TABLE c00_person;

CREATE TABLE c00_person
(
  person_id serial NOT NULL,
  lastname character varying(64) NOT NULL DEFAULT 'who?'::character varying,
  lastsoundex character varying(64) NOT NULL DEFAULT '??'::character varying,
  firstname character varying(64) NOT NULL DEFAULT 'who?'::character varying,
  loginname character varying(64),
  "password" character varying(255),
  email character varying(64),
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT person_KEY PRIMARY KEY (person_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c00_person OWNER TO ts_admin;
GRANT ALL ON TABLE c00_person TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c00_person TO ts_editor;
GRANT SELECT ON TABLE c00_person TO ts_reader;
COMMENT ON COLUMN c00_person."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE c00_person_person_id_seq TO ts_editor;

-- Table: c02_rate

-- DROP TABLE c02_rate;

CREATE TABLE c02_rate
(
  rate_id serial NOT NULL,
  person_idref integer NOT NULL DEFAULT 0,
  project_idref integer NOT NULL DEFAULT 0,
  rate numeric NOT NULL DEFAULT 0.00,
  effective_asof date NOT NULL DEFAULT (now() - '2 years'::interval),
  expire_after date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT rate_Key PRIMARY KEY (rate_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c02_rate OWNER TO ts_admin;
COMMENT ON COLUMN c02_rate.expire_after IS 'De-normalized: one day less than next up effective_asof';
COMMENT ON COLUMN c02_rate."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT ALL ON TABLE c02_rate TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c02_rate TO ts_editor;
GRANT SELECT ON TABLE c02_rate TO ts_reader;
GRANT UPDATE ON TABLE c02_rate_rate_id_seq TO ts_editor;

-- Table: c10_person_organization

-- DROP TABLE c10_person_organization;

CREATE TABLE c10_person_organization
(
  person_organization_id serial NOT NULL,
  person_idref integer NOT NULL DEFAULT 0,
  organization_idref integer NOT NULL DEFAULT 0,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT person_organization_KEY PRIMARY KEY (person_organization_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c10_person_organization OWNER TO ts_admin;
GRANT ALL ON TABLE c10_person_organization TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c10_person_organization TO ts_editor;
GRANT SELECT ON TABLE c10_person_organization TO ts_reader;
COMMENT ON TABLE c10_person_organization IS 'Connect a person to an organization - many-to-many - 
the connecting point for person properties that are organization specific';
COMMENT ON COLUMN c10_person_organization."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE c10_person_organization_person_organization_id_seq TO ts_editor;

-- Table: c20_person_permit

-- DROP TABLE c20_person_permit;

CREATE TABLE c20_person_permit
(
  person_permit_id serial NOT NULL,
  person_idref integer NOT NULL DEFAULT 0,
  permit_idref integer NOT NULL DEFAULT 0,
  organization_idref integer NOT NULL DEFAULT 0,
  project_idref integer NOT NULL DEFAULT 0,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT person_permit_KEY PRIMARY KEY (person_permit_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c20_person_permit OWNER TO ts_admin;
GRANT ALL ON TABLE c20_person_permit TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c20_person_permit TO ts_editor;
GRANT SELECT ON TABLE c20_person_permit TO ts_reader;
GRANT UPDATE ON TABLE c20_person_permit_person_permit_id_seq TO ts_editor;
COMMENT ON TABLE c20_person_permit IS 'connect a person to a permission - many-to-many';
COMMENT ON COLUMN c20_person_permit."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';

-- Table: d01_permit

-- DROP TABLE d01_permit;

CREATE TABLE d01_permit
(
  permit_id serial NOT NULL,
  "name" character varying(16) NOT NULL DEFAULT 'new permit'::character varying,
  description character varying(128) NOT NULL DEFAULT 'new permit'::character varying,
  "comment" text,
  grade smallint NOT NULL DEFAULT 10, -- Security grade: 1 = system wide, 10 = organization specific, 100 = project specific
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT permit_KEY PRIMARY KEY (permit_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE d01_permit OWNER TO ts_admin;
GRANT ALL ON TABLE d01_permit TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE d01_permit TO ts_editor;
GRANT SELECT ON TABLE d01_permit TO ts_reader;
GRANT UPDATE ON TABLE d01_permit_permit_id_seq TO ts_editor;
COMMENT ON TABLE d01_permit IS 'List the possible user permissions';
COMMENT ON COLUMN d01_permit."grade" IS 'Security grade: 0 = system wide, 10 = organization specific, 100 = project specific';
COMMENT ON COLUMN d01_permit."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';

-- Table: d02_currency

-- DROP TABLE d02_currency;

CREATE TABLE d02_currency
(
  currency_id serial NOT NULL,
  "name" character varying(32) NOT NULL DEFAULT 'US dollar'::character varying,
  "symbol" character varying(8) NOT NULL DEFAULT '$'::character varying,
  "decimal_cnt" integer NOT NULL DEFAULT 2,
  CONSTRAINT currency_Key PRIMARY KEY (currency_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE d02_currency OWNER TO ts_admin;
GRANT ALL ON TABLE d02_currency TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE d02_currency TO ts_editor;
GRANT SELECT ON TABLE d02_currency TO ts_reader;
GRANT UPDATE ON TABLE d02_currency_currency_id_seq TO ts_editor;

-- Table: d10_preferences

-- DROP TABLE d10_preferences;

CREATE TABLE d10_preferences
(
  preferences_id serial NOT NULL,
  organization_idref integer NOT NULL DEFAULT 0,
  project_idref integer NOT NULL DEFAULT 0,
  person_idref integer NOT NULL DEFAULT 0,
  "theme" character varying(64) NOT NULL DEFAULT ''::character varying,
  "menu" character varying(512) NOT NULL DEFAULT ''::character varying,
  "date" character varying(16) NOT NULL DEFAULT ''::character varying,
  currency_idref integer NOT NULL DEFAULT 1,
  "decimal_char" character(1) NOT NULL DEFAULT'.',
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT preferences_Key PRIMARY KEY (preferences_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE d10_preferences OWNER TO ts_admin;
GRANT ALL ON TABLE d10_preferences TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE d10_preferences TO ts_editor;
GRANT SELECT ON TABLE d10_preferences TO ts_reader;
COMMENT ON COLUMN d10_preferences."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE d10_preferences_preferences_id_seq TO ts_editor;

-- View: v00_timelog

-- DROP VIEW v00_timelog;

CREATE OR REPLACE VIEW v00_timelog AS 
 SELECT b00.timelog_id, b00.logdate, b00.hours,
        b02.activity_id, b02.description AS activity,
        a14.subtask_id, a14.name AS subtask, a14.description AS subtask_desc, a14.extension, a14.inactive_asof AS subtask_inactive_asof,
        a12.task_id, a12.name AS task, a12.description AS task_desc, a12.inactive_asof AS task_inactive_asof,
        a10.project_id, a10.name AS project, a10.description AS project_desc,
        a21.account_id, a21.name AS account, a21.description AS account_desc, a21.inactive_asof AS account_inactive_asof,
        a00.organization_id, c00.person_id
   FROM b00_timelog b00
   JOIN b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN a12_task a12 ON a12.task_id = a14.task_idref
   JOIN a10_project a10 ON a10.project_id = a12.project_idref
   JOIN a00_organization a00 ON a00.organization_id = a10.organization_idref
   JOIN c00_person c00 ON c00.person_id = b00.person_idref
   JOIN a21_account a21 ON a21.account_id = b00.account_idref
  ORDER BY b00.logdate;

ALTER TABLE v00_timelog OWNER TO ts_admin;
GRANT ALL ON TABLE v00_timelog TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE v00_timelog TO ts_editor;
GRANT SELECT ON TABLE v00_timelog TO ts_reader;

-- View: v10_logreport

-- DROP VIEW v10_logreport;

CREATE OR REPLACE VIEW v10_logreport AS 
 SELECT a12.project_idref AS project_id, b00.logdate, b00.hours, b02.description AS activity, b02.activity_id,
        a14.name AS subtask, a14.description AS subtask_desc, a12.name AS task, a12.description AS task_desc,
        a21.name AS account, a21.description AS account_desc, c00.lastname, c00.firstname, c00.person_id, rate
   FROM b00_timelog b00
   JOIN b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN a12_task a12 ON a12.task_id = a14.task_idref
   JOIN a21_account a21 ON a21.account_id = b00.account_idref
   JOIN c00_person c00 ON c00.person_id = b00.person_idref
   JOIN c02_rate c02 ON c02.person_idref = b00.person_idref AND c02.project_idref = a12.project_idref
  WHERE logdate >= c02.effective_asof AND (c02.expire_after IS NULL OR logdate <= c02.expire_after)
  ORDER BY b00.logdate;

ALTER TABLE v10_logreport OWNER TO ts_admin;
GRANT ALL ON TABLE v10_logreport TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE v10_logreport TO ts_editor;
GRANT SELECT ON TABLE v10_logreport TO ts_reader;

-- View: v12_taskreport

-- DROP VIEW v12_taskreport;

CREATE OR REPLACE VIEW v12_taskreport AS 
 SELECT project_idref AS project_id, task_id, a12.name AS taskname, a12.description AS task_desc, budget,
        a12.inactive_asof AS task_inactive_asof, a14.name AS subtaskname, a14.description AS subtask_desc,
        a14.inactive_asof AS subtask_inactive_asof
   FROM a12_task AS a12
   JOIN a14_subtask a14 ON a14.task_idref = a12.task_id;

ALTER TABLE v12_taskreport OWNER TO ts_admin;
GRANT ALL ON TABLE v12_taskreport TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE v12_taskreport TO ts_editor;
GRANT SELECT ON TABLE v12_taskreport TO ts_reader;


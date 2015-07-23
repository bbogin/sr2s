
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

-- Table: b10_eventlog

-- DROP TABLE b10_eventlog;

CREATE TABLE b10_eventlog
(
  eventlog_id serial NOT NULL,
  project_idref integer NOT NULL DEFAULT 0,
  event_idref integer NOT NULL DEFAULT 0,
  person_idref integer NOT NULL DEFAULT 0,
  account_idref integer NOT NULL DEFAULT 0,
  sessio_count integer NOT NULL DEFAULT 0,
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


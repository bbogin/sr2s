-- Table: b02_activity

-- DROP TABLE b02_activity;

CREATE TABLE b02_activity
(
  activity_id serial NOT NULL,
  description text,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "activity_Key" PRIMARY KEY (activity_id)
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


-- Create PostgreSql table:
-- Table: x01_classcount

-- DROP TABLE x01_classcount;

CREATE TABLE x01_classcount
(
  classcount_id serial NOT NULL,
  project_idref integer NOT NULL DEFAULT 0,
  event_idref integer NOT NULL DEFAULT 0,
  person_idref integer NOT NULL DEFAULT 0,
  account_idref integer NOT NULL DEFAULT 0,
  sessions integer NOT NULL DEFAULT 0,
  attendees integer NOT NULL DEFAULT 0,
  event_date date NOT NULL DEFAULT now(),
  "comments" character varying(256) NOT NULL DEFAULT ''::character varying,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT classcount_Key PRIMARY KEY (classcount_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE x01_classcount OWNER TO ts_admin;
GRANT ALL ON TABLE x01_classcount TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE x01_classcount TO ts_editor;
GRANT SELECT ON TABLE x01_classcount TO ts_reader;
COMMENT ON COLUMN x01_classcount."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE x01_classcount_classcount_ID_seq TO ts_editor;


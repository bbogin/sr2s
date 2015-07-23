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
  CONSTRAINT "timelog_Key" PRIMARY KEY (timelog_id)
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

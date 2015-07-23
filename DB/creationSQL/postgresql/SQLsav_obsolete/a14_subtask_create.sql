-- Table: a14_subtask

-- DROP TABLE a14_subtask;

CREATE TABLE a14_subtask
(
  subtask_id serial NOT NULL,
  task_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new subtask'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new subtask'::character varying,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "subtask_Key" PRIMARY KEY (subtask_id)
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

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
  CONSTRAINT "task_Key" PRIMARY KEY (task_id)
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


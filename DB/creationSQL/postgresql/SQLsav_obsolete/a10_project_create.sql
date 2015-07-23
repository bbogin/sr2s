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

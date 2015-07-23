-- Table: c00_person

-- DROP TABLE c00_person;

CREATE TABLE c00_person
(
  person_id serial NOT NULL,
  lastname character varying(64) NOT NULL DEFAULT 'who?'::character varying,
  lastsoundex character varying(64) NOT NULL DEFAULT '??'::character varying,
  firstname character varying(64) NOT NULL DEFAULT 'who?'::character varying,
  loginname character varying(64),
  "password" character varying(64),
  email character varying(64),
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "person_KEY" PRIMARY KEY (person_id)
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

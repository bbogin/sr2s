-- Table: c10_person_organization

-- DROP TABLE c10_person_organization;

CREATE TABLE c10_person_organization
(
  person_organization_id serial NOT NULL,
  person_idref integer NOT NULL DEFAULT 0,
  organization_idref integer NOT NULL DEFAULT 0,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "person_organization_KEY" PRIMARY KEY (person_organization_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c10_person_organization OWNER TO ts_admin;
GRANT ALL ON TABLE c10_person_organization TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c10_person_organization TO ts_editor;
GRANT SELECT ON TABLE c10_person_organization TO ts_reader;
COMMENT ON TABLE c10_person_organization IS 'Connect a person to an organization - many-to-many - and is the connecting point for person properties that are organization specific, eg. permits';
COMMENT ON COLUMN c10_person_organization."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE c10_person_organization_person_organization_id_seq TO ts_editor;


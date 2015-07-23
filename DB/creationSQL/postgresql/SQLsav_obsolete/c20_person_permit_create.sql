-- Table: c20_person_permit

-- DROP TABLE c20_person_permit;

CREATE TABLE c20_person_permit
(
  person_permit_id serial NOT NULL,
  person_organization_idref integer NOT NULL DEFAULT 0,
  permit_idref integer NOT NULL DEFAULT 0,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()),
  CONSTRAINT "person_permit_KEY" PRIMARY KEY (person_permit_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c20_person_permit OWNER TO ts_admin;
COMMENT ON TABLE c20_person_permit IS 'connect a person to a permission (organization specific) - many-to-many - through the person/organization table';
GRANT ALL ON TABLE c20_person_permit TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c20_person_permit TO ts_editor;
GRANT SELECT ON TABLE c20_person_permit TO ts_reader;
GRANT UPDATE ON TABLE c20_person_permit_person_permit_id_seq TO ts_editor;


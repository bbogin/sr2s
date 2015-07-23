-- Table: a00_organization

-- DROP TABLE a00_organization;

CREATE TABLE a00_organization
(
  organization_id serial NOT NULL,
  "name" character varying(64) NOT NULL DEFAULT 'new organization'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new organization'::character varying,
  logo oid NOT NULL DEFAULT 0,
  logo_type character varying(8) NOT NULL DEFAULT 'jpeg'::character varying,
  currency_idref integer NOT NULL DEFAULT 1,
  timezone smallint NOT NULL DEFAULT (-8), -- TimeZoneOffset from Greenwich, in hours; west is negative
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "organization_Key" PRIMARY KEY (organization_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a00_organization OWNER TO ts_admin;
GRANT ALL ON TABLE a00_organization TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a00_organization TO ts_editor;
GRANT SELECT ON TABLE a00_organization TO ts_reader;
COMMENT ON COLUMN a00_organization."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
COMMENT ON COLUMN a00_organization.timezone IS 'TimeZoneOffset from Greenwich, in hours; west is negative';
GRANT UPDATE ON TABLE a00_organization_organization_ID_seq TO ts_editor;



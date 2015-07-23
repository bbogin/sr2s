-- Table: d01_permit

-- DROP TABLE d01_permit;

CREATE TABLE d01_permit
(
  permit_id serial NOT NULL,
  "name" character varying(16) NOT NULL DEFAULT 'new permit'::character varying,
  description character varying(128) NOT NULL DEFAULT 'new permit'::character varying,
  "comment" text,
  grade smallint NOT NULL DEFAULT 10, -- 'Grades' the permissions for convenient display grouping when doing grant/revoke:...
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "permit_KEY" PRIMARY KEY (permit_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE d01_permit OWNER TO ts_admin;
GRANT ALL ON TABLE d01_permit TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE d01_permit TO ts_editor;
GRANT SELECT ON TABLE d01_permit TO ts_reader;
COMMENT ON TABLE d01_permit IS 'List the possible user permissions';
COMMENT ON COLUMN d01_permit."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
COMMENT ON COLUMN d01_permit.grade IS '''Grades'' the permissions for convenient display grouping when doing grant/revoke:
0 = any signed on user
5 = organization administrator
10 = superuser';
GRANT UPDATE ON TABLE d01_permit_permit_id_seq TO ts_editor;

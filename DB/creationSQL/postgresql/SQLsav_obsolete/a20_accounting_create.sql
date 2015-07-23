-- Table: a20_accounting

-- DROP TABLE a20_accounting;

CREATE TABLE a20_accounting
(
  accounting_id serial NOT NULL,
  organization_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  "comment" text,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "accounting_Key" PRIMARY KEY (accounting_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a20_accounting OWNER TO ts_admin;
GRANT ALL ON TABLE a20_accounting TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a20_accounting TO ts_editor;
GRANT SELECT ON TABLE a20_accounting TO ts_reader;
COMMENT ON COLUMN a20_accounting."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a20_accounting_accounting_id_seq TO ts_editor;


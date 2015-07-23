-- Table: a21_account

-- DROP TABLE a21_account;

CREATE TABLE a21_account
(
  account_id serial NOT NULL,
  accounting_idref integer NOT NULL DEFAULT 0,
  "name" character varying(64) NOT NULL DEFAULT 'new task'::character varying,
  description character varying(256) NOT NULL DEFAULT 'new task'::character varying,
  inactive_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "account_Key" PRIMARY KEY (account_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE a21_account OWNER TO ts_admin;
GRANT ALL ON TABLE a21_account TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE a21_account TO ts_editor;
GRANT SELECT ON TABLE a21_account TO ts_reader;
COMMENT ON COLUMN a21_account."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT UPDATE ON TABLE a21_account_account_id_seq TO ts_editor;


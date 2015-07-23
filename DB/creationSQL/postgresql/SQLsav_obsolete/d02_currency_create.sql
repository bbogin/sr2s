-- Table: d02_currency

-- DROP TABLE d02_currency;

CREATE TABLE d02_currency
(
  currency_id serial NOT NULL,
  "name" character varying(32) NOT NULL DEFAULT 'US dollar'::character varying,
  symbol character varying(8) NOT NULL DEFAULT '$'::character varying,
  CONSTRAINT "currency_Key" PRIMARY KEY (currency_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE d02_currency OWNER TO ts_admin;
GRANT ALL ON TABLE d02_currency TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE d02_currency TO ts_editor;
GRANT SELECT ON TABLE d02_currency TO ts_reader;
GRANT UPDATE ON TABLE d02_currency_currency_id_seq TO ts_editor;

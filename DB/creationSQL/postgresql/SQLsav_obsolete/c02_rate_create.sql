-- Table: c02_rate

-- DROP TABLE c02_rate;

CREATE TABLE c02_rate
(
  rate_id serial NOT NULL,
  person_idref integer NOT NULL DEFAULT 0,
  project_idref integer NOT NULL DEFAULT 0,
  rate numeric NOT NULL DEFAULT 0.00,
  effective_asof date NOT NULL DEFAULT (now() - '2 years'::interval),
  expire_asof date,
  "timestamp" timestamp without time zone DEFAULT timezone('UTC'::text, now()), -- Time is UTC, alias GMT, alias Greenwich Mean Time
  CONSTRAINT "rate_Key" PRIMARY KEY (rate_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE c02_rate OWNER TO ts_admin;
COMMENT ON COLUMN c02_rate.expire_asof IS 'De-normalized: one day less than next up effective_asof';
COMMENT ON COLUMN c02_rate."timestamp" IS 'Time is UTC, alias GMT, alias Greenwich Mean Time';
GRANT ALL ON TABLE c02_rate TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE c02_rate TO ts_editor;
GRANT SELECT ON TABLE c02_rate TO ts_reader;
GRANT UPDATE ON TABLE c02_rate_rate_id_seq TO ts_editor;


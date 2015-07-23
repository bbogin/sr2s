-- View: v10_logreport

-- DROP VIEW v10_logreport;

CREATE OR REPLACE VIEW v10_logreport AS 
 SELECT a12.project_idref AS project_id, b00.logdate, b00.hours, b02.description AS activity, b02.activity_id, a14.name AS subtask, a14.description AS subtask_desc, a12.name AS task, a12.description AS task_desc, a21.name AS account, a21.description AS account_desc, c00.lastname, c00.firstname, c00.person_id, rate
   FROM b00_timelog b00
   JOIN b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN a12_task a12 ON a12.task_id = a14.task_idref
   JOIN a21_account a21 ON a21.account_id = b00.account_idref
   JOIN c00_person c00 ON c00.person_id = b00.person_idref
   JOIN c02_rate c02 ON c02.person_idref = b00.person_idref AND c02.project_idref = a12.project_idref
  WHERE logdate >= c02.effective_asof AND (c02.expire_asof IS NULL OR logdate <= c02.expire_asof)
  ORDER BY b00.logdate;

ALTER TABLE v10_logreport OWNER TO ts_admin;
GRANT ALL ON TABLE v10_logreport TO ts_admin;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE v10_logreport TO ts_editor;
GRANT SELECT ON TABLE v10_logreport TO ts_reader;


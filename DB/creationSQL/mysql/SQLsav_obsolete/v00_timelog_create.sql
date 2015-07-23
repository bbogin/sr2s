-- View: v00_timelog

-- DROP VIEW v00_timelog;

CREATE OR REPLACE VIEW v00_timelog AS 
 SELECT b00.timelog_id, b00.logdate, b00.hours, b02.activity_id, b02.description AS activity, a14.subtask_id, a14.name AS subtask, a14.description AS subtask_desc, a12.task_id, a12.name AS task, a12.description AS task_desc, a10.project_id, a10.name AS project, a10.description AS project_desc, a21.account_id, a21.name AS account, a21.description AS account_desc, a00.organization_id, c00.person_id
   FROM b00_timelog b00
   JOIN b02_activity b02 ON b02.activity_id = b00.activity_idref
   JOIN a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
   JOIN a12_task a12 ON a12.task_id = a14.task_idref
   JOIN a10_project a10 ON a10.project_id = a12.project_idref
   JOIN a00_organization a00 ON a00.organization_id = a10.organization_idref
   JOIN c00_person c00 ON c00.person_id = b00.person_idref
   JOIN a21_account a21 ON a21.account_id = b00.account_idref
  ORDER BY b00.logdate;


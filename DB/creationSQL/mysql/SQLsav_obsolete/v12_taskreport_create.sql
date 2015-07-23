-- View: v12_taskreport

-- DROP VIEW v12_taskreport;

CREATE OR REPLACE VIEW v12_taskreport AS 
 SELECT project_idref AS project_id, task_id, a12.name AS taskname, a12.description AS task_desc, budget, a12.inactive_asof AS task_inactive_asof, a14.name AS subtaskname, a14.description AS subtask_desc, a12.inactive_asof AS subtask_inactive_asof
   FROM a12_task AS a12
   JOIN a14_subtask a14 ON a14.task_idref = a12.task_id;


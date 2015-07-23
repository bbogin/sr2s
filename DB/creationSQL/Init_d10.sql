INSERT INTO d10_preferences (project_idref,menu) VALUES(1,'EL=Classes Edit&TP=Download Invoice Logs');
INSERT INTO d10_preferences (organization_idref,menu) VALUES(1,'EL=Classes Edit&TP=Download Invoice Logs');

UPDATE d10_preferences SET menu='EL=Classes Edit&TP=Download Invoice Logs' WHERE project_idref=1;

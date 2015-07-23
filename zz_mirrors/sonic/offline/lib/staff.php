<?php //copyright 2014 C.D.Price
define ('MENU', 0);
define ('PAGE', 1);
define ('PERMIT', 2);
define ('PRE_EXEC', 3);
//					MENU					PAGE					PERMIT				PRE_EXEC
$EX_staff = array(
	"AC" => array('Account Edit',			"account_edit.php",		'account_edit',		""			),
	"AG" => array('Accounting group Edit',	"accounting_edit.php",	'accounting_edit',	""			),
	"CF" => array('Site Config',			"config.php",			PERMITS::_SUPERUSER,""			),
	"EL" => array('Event Log',				"eventlog.php",			'',					""			),
	"LE" => array('Edit Logs',				"timelog.php",			'edit_logs',		'$_EDIT=true;'),
	"MC" => array('Load MDB',				"loadMDB_csv.php",		PERMITS::_SUPERUSER,""			),
	"OE" => array('Org Edit',				"org_edit.php",			'org_edit',			""			),
	"OS" => array('Org Select',				"org_select.php",		'',					""			),
	"PE" => array('Person Edit',			"person_edit.php",		'',					""			),
	"PI" => array('phpInfo',				"phpinfo.php",			PERMITS::_SUPERUSER,""			),
	"PJ" => array('Project Edit',			"project_edit.php",		'project_edit',		""			),
	"PM" => array('Grant/revoke permits',	"assign_permits.php",	'assign_permits',	""			),
	"RC" => array('Refresh CSV',			"refresh_csv.php",		PERMITS::_SUPERUSER,""			),
	"SC" => array('Save CSV',				"save_csv.php",			PERMITS::_SUPERUSER,""			),
	"SR" => array('Set Rates',				"set_rates.php",		'set_rates',		""			),
	"ST" => array('Subtask Edit',			"subtask_edit.php",		'subtask_edit',		""			),
	"TK" => array('Task Edit',				"task_edit.php",		'task_edit',		""			),
	"TL" => array('Time Log Entry',			"timelog.php",			'',					'$_EDIT=false;'),
	"TP" => array('Download Project Logs',	"timelog_put.php",		'project_logs',		""			),
	"TR" => array('Download Task Report',	"taskreport_put.php",	'reports',			""			),
	"UP" => array('Upyear Timelog',			"upyear_timelog.php",	PERMITS::_SUPERUSER,			""			),
	);
?>

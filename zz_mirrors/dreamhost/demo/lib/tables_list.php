<?php

class TABLE {
	public $name;
	public $MDBname;
	public $idname;
	public $fields;

	function __construct($iname, $iMDBname, $iidname, $ifields="") {
		$this->name = $iname;
		$this->MDBname = $iMDBname;
		$this->idname = $iidname;
		$this->fields = $ifields;
	}
}
class TFIELD {
	public $MDBname;
	public $type;
	public $MDBndx;
	public $editor;

	function __construct($iMDBname, $itype, $ieditor="") {
		$this->MDBname = $iMDBname;
		$this->type = $itype;
		$this->editor = $ieditor;
	}
}

function tables_list() {
	global $_STATE;

	$DBPREFIX = $_SESSION['_SITE_CONF']['DBPREFIX'];

	$fields = array(
		"organization_id" => new TFIELD("Department_ID",PDO::PARAM_INT),
		"name" => new TFIELD("Name",PDO::PARAM_STR),
		"description" => new TFIELD("Description",PDO::PARAM_STR,"string"),
//		"logo" => new TFIELD("",db_connect::PARAM_LOB),
		"logo_type" => new TFIELD("",PDO::PARAM_STR),
		"currency_idref" => new TFIELD("",PDO::PARAM_INT),
		"timezone" => new TFIELD("",PDO::PARAM_INT),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["organization"] = new TABLE(
		$DBPREFIX."a00_organization","t01_Department", "organization_id", $fields);

	$fields = array(
		"project_id" => new TFIELD("Project_ID",PDO::PARAM_INT),
		"organization_idref" => new TFIELD("Department_IDRef",PDO::PARAM_INT),
		"accounting_idref" => new TFIELD("",PDO::PARAM_INT),
		"name" => new TFIELD("Name",PDO::PARAM_STR),
		"description" => new TFIELD("Description",PDO::PARAM_STR,"string"),
		"comment" => new TFIELD("",PDO::PARAM_STR,"string"),
		"inactive_asof" => new TFIELD("Inactive_AsOf",db_connect::PARAM_DATE,"date"),
		"close_date" => new TFIELD("",db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["project"] = new TABLE(
		$DBPREFIX."a10_project","t02_Project", "project_id", $fields);

	$fields = array(
		"task_id" => new TFIELD("Task_ID", PDO::PARAM_INT),
		"project_idref" => new TFIELD("Project_IDRef", PDO::PARAM_INT),
		"name" => new TFIELD("Name", PDO::PARAM_STR),
		"description" => new TFIELD("Description", PDO::PARAM_STR,"string"),
		"budget" => new TFIELD("Budget", PDO::PARAM_STR, "money"),
		"inactive_asof" => new TFIELD("Inactive_AsOf", db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["task"] = new TABLE(
		$DBPREFIX."a12_task","t03_Task","task_id", $fields);

	$fields = array(
		"subtask_id" => new TFIELD("Subtask_ID", PDO::PARAM_INT),
		"task_idref" => new TFIELD("Task_IDRef", PDO::PARAM_INT),
		"name" => new TFIELD("Name", PDO::PARAM_STR),
		"description" => new TFIELD("Description", PDO::PARAM_STR,"string"),
		"extension" => new TFIELD("", PDO::PARAM_STR),
		"inactive_asof" => new TFIELD("Inactive_AsOf", db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["subtask"] = new TABLE(
		$DBPREFIX."a14_subtask","t04_Subtask","subtask_id", $fields);

	$fields = array(
		"accounting_id" => new TFIELD("ProjPropName_ID", PDO::PARAM_INT),
		"organization_idref" => new TFIELD("",PDO::PARAM_INT),
		"name" => new TFIELD("Name", PDO::PARAM_STR),
		"description" => new TFIELD("Description", PDO::PARAM_STR),
		"comment" => new TFIELD("",PDO::PARAM_STR,"string"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["accounting"] = new TABLE(
		$DBPREFIX."a20_accounting","t91_ProjPropName","accounting_id", $fields);

	$fields = array(
		"account_id" => new TFIELD("ProjPropEnum_ID", PDO::PARAM_INT),
		"accounting_idref" => new TFIELD("ProjPropName_IDRef", PDO::PARAM_INT),
		"name" => new TFIELD("Name", PDO::PARAM_STR),
		"description" => new TFIELD("Description", PDO::PARAM_STR,"string"),
		"inactive_asof" => new TFIELD("Inactive_AsOf", db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["account"] = new TABLE(
		$DBPREFIX."a21_account","t92_ProjPropEnum","account_id", $fields);

	$fields = array(
		"event_id" => new TFIELD("", PDO::PARAM_INT),
		"project_idref" => new TFIELD("", PDO::PARAM_INT),
		"name" => new TFIELD("", PDO::PARAM_STR),
		"description" => new TFIELD("", PDO::PARAM_STR,"string"),
		"budget" => new TFIELD("", PDO::PARAM_STR, "money"),
		"inactive_asof" => new TFIELD("", db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["event"] = new TABLE(
		$DBPREFIX."a30_event","","event_id", $fields);

	$fields = array(
		"timelog_id" => new TFIELD("TimeLog_ID", PDO::PARAM_INT),
		"activity_idref" => new TFIELD("Action_IDRef", PDO::PARAM_INT),
		"person_idref" => new TFIELD("Person_IDRef", PDO::PARAM_INT),
		"subtask_idref" => new TFIELD("", PDO::PARAM_INT),
		"account_idref" => new TFIELD("", PDO::PARAM_INT),
		"logdate" => new TFIELD("LogDate", db_connect::PARAM_DATE,"date"),
		"hours" => new TFIELD("Hours", db_connect::PARAM_STR),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["timelog"] = new TABLE(
		$DBPREFIX."b00_timelog", "t06_TimeLog","timelog_id", $fields);

	$fields = array(
		"activity_id" => new TFIELD("Action_ID", PDO::PARAM_INT),
		"description" => new TFIELD("Description", PDO::PARAM_STR,"string"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["activity"] = new TABLE(
		$DBPREFIX."b02_activity","t05_Action","activity_id", $fields);

	$fields = array(
		"eventlog_id" => new TFIELD("", PDO::PARAM_INT),
		"event_idref" => new TFIELD("", PDO::PARAM_INT),
		"person_idref" => new TFIELD("", PDO::PARAM_INT),
		"account_idref" => new TFIELD("", PDO::PARAM_INT),
		"session_count" => new TFIELD("", db_connect::PARAM_INT),
		"attendance" => new TFIELD("", db_connect::PARAM_INT),
		"logdate" => new TFIELD("", db_connect::PARAM_DATE,"date"),
		"comments" => new TFIELD("", PDO::PARAM_STR),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["eventlog"] = new TABLE(
		$DBPREFIX."b10_eventlog", "","eventlog_id", $fields);

	$fields = array(
		"person_id" => new TFIELD("Person_ID", PDO::PARAM_INT),
		"lastname" => new TFIELD("LastName", db_connect::PARAM_STR,"string"),
		"lastsoundex" => new TFIELD("", db_connect::PARAM_STR),
		"firstname" => new TFIELD("FirstName", db_connect::PARAM_STR,"string"),
		"loginname" => new TFIELD("", db_connect::PARAM_STR),
		"password" => new TFIELD("", db_connect::PARAM_STR),
		"email" => new TFIELD("", db_connect::PARAM_STR),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["person"] = new TABLE(
		$DBPREFIX."c00_person","t10_Person","person_id", $fields);

	$fields = array(
		"rate_id" => new TFIELD("Rate_ID", PDO::PARAM_INT),
		"person_idref" => new TFIELD("Person_IDRef", PDO::PARAM_INT),
		"project_idref" => new TFIELD("", PDO::PARAM_INT),
		"rate" => new TFIELD("Rate", PDO::PARAM_STR, "money"),
		"effective_asof" => new TFIELD("Effective_AsOf", db_connect::PARAM_DATE,"date"),
		"expire_after" => new TFIELD("",db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["rate"] = new TABLE(
		$DBPREFIX."c02_rate", "t11_Rate","rate_id", $fields);

	$fields = array(
		"person_organization_id" => new TFIELD("", PDO::PARAM_INT),
		"person_idref" => new TFIELD("", PDO::PARAM_INT),
		"organization_idref" => new TFIELD("", PDO::PARAM_INT),
		"inactive_asof" => new TFIELD("", db_connect::PARAM_DATE,"date"),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["person_organization"] = new TABLE(
		$DBPREFIX."c10_person_organization","","person_organization_id", $fields);

	$fields = array(
		"person_permit_id" => new TFIELD("", PDO::PARAM_INT),
		"person_idref" => new TFIELD("", PDO::PARAM_INT),
		"permit_idref" => new TFIELD("", PDO::PARAM_INT),
		"organization_idref" => new TFIELD("", PDO::PARAM_INT),
		"project_idref" => new TFIELD("", PDO::PARAM_INT),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["person_permit"] = new TABLE(
		$DBPREFIX."c20_person_permit","","person_permit_id", $fields);

	$fields = array(
		"permit_id" => new TFIELD("", PDO::PARAM_INT),
		"name" => new TFIELD("", PDO::PARAM_STR),
		"description" => new TFIELD("", PDO::PARAM_STR,"string"),
		"comment" => new TFIELD("", PDO::PARAM_STR,"string"),
		"grade" => new TFIELD("", PDO::PARAM_INT),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["permit"] = new TABLE(
		$DBPREFIX."d01_permit","","permit_id", $fields);

	$fields = array(
		"currency_id" => new TFIELD("", PDO::PARAM_INT),
		"name" => new TFIELD("", PDO::PARAM_STR),
		"symbol" => new TFIELD("", PDO::PARAM_STR),
		"decimal_cnt" => new TFIELD("", PDO::PARAM_INT)
		);
	$_STATE->records["currency"] = new TABLE(
		$DBPREFIX."d02_currency","","currency_id", $fields);

	$fields = array(
		"preferences_id" => new TFIELD("", PDO::PARAM_INT),
		"organization_idref" => new TFIELD("", PDO::PARAM_INT),
		"project_idref" => new TFIELD("", PDO::PARAM_INT),
		"person_idref" => new TFIELD("", PDO::PARAM_INT),
		"theme" => new TFIELD("", PDO::PARAM_STR),
		"menu" => new TFIELD("", PDO::PARAM_STR),
		"date" => new TFIELD("", PDO::PARAM_STR),
		"currency_idref" => new TFIELD("", PDO::PARAM_INT),
		"decimal_char" => new TFIELD("", PDO::PARAM_STR),
		"timestamp" => new TFIELD("",db_connect::PARAM_DATE)
		);
	$_STATE->records["preferences"] = new TABLE(
		$DBPREFIX."d10_preferences","","preferences_id", $fields);
}
?>

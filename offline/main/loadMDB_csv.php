<?php
if (($_SESSION["_SITE_CONF"]["RUNLEVEL"] < 1) || (!$_PERMITS->can_pass(PERMITS::_SUPERUSER)))
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");
require_once ("rarities.php");
require_once ("tables_list.php");

/*
To set up a new DB using the MDB csv files:
1. Load d01_permit, d02_currency, and d10_preferences from the saved csv data;
2. Create person 0 - the super-duper user (use CreatePwdHash then copy into password field);
Notes: 0 records WILL NOT BE deleted by subsequent reloads of MDB data;
      In MySQL, "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; when creating these records;
	phpmyadmin will initially set the value to 1 but editing the record back to 0 works;
*/

function a00(&$db) { //organization

	connect_person_org($db);
	return true;
}

function a10(&$db) { //project

	$close = NOW();
	$close->sub(new DateInterval('P1M'));
	$sql = "UPDATE ".$db->prefix."a10_project
			SET close_date='".$close->format('Y-m-d')."', accounting_idref=1;";
	$db->exec($sql);

	return true;
}

function a20(&$db) { //accounting

	$sql = "UPDATE ".$db->prefix."a20_accounting SET organization_idref=1;";
	$db->exec($sql);
	$sql = "UPDATE ".$db->prefix."a10_project SET accounting_idref=1
			WHERE project_id=1;";
	$db->exec($sql);
	return true;
}

function a21(&$db) { //account (school)

	$sql = "INSERT INTO ".$db->prefix."a21_account (accounting_idref, name, description)
			VALUES (1, '*', 'Empty account');";
	$db->exec($sql);

	return true;
}

function b00(&$db) { //timelog
	//must load a21_account BEFORE b00_timelog to get 'empty account' id_ref for unassociated timelogs
	//must load b02_activity AFTER b00_timelog to connect timelog to subtask, etc.

	$file = $GLOBALS["MDBload_path"]."t07_TimeLogProperty.csv";
	if (!file_exists($file)) return false;
	$handle = fopen($file, "r");

	$headers = fgetcsv($handle); //headers
	$MDBndxs = array();
	$ndx = 0;
	foreach ($headers as $name) {
		$MDBndxs[$name] = $ndx;
		++$ndx;
	}

	$sql = "UPDATE ".$db->prefix."b00_timelog
			SET account_idref=:account_id
			WHERE timelog_id=:timelog_id;";
	$stmt = $db->prepare($sql);
	while (($csvdata = fgetcsv($handle)) !== FALSE) {
		$stmt->bindValue(":timelog_id", $csvdata[1], PDO::PARAM_INT);
		$stmt->bindValue(":account_id", $csvdata[2], PDO::PARAM_INT);
		$stmt->execute();
	}
	fclose($handle);

	$sql = "SELECT account_id
			FROM ".$db->prefix."a21_account
			WHERE name='*';";
	$stmt = $db->query($sql);
	$idref = $stmt->fetchObject()->account_id;
	$stmt->closeCursor();
	$sql = "UPDATE ".$db->prefix."b00_timelog
			SET account_idref=".$idref." WHERE account_idref=0;";
	$db->exec($sql);

	return true;
}

function c00(&$db) { //person

	connect_person_org($db);
	$sql = "DELETE FROM ".$db->prefix."c20_person_permit
			WHERE person_permit_id > 0;";
	$db->exec($sql);

/*
	//give person 1 (Wendi) org privileges:
	$sql = "SELECT ".$db->prefix."permit_id FROM d01_permit WHERE grade <= 5;";
	$values = "";
	$stmt = $db->query($sql);
	while ($row = $stmt->fetchObject()) {
		$values .= ",(1, ".intval($row->permit_id).")";
	}
	$stmt->closeCursor();
	$sql = "INSERT INTO c20_person_permit (person_idref, permit_idref) VALUES ".substr($values,1).";";
	$db->exec($sql);
*/

	//create the soundex of each person's lastname; set loginname and password
	$sql = "SELECT * FROM ".$db->prefix."c00_person WHERE person_id > 0;";
	$stmt = $db->query($sql);
	$sql = "UPDATE ".$db->prefix."c00_person
			SET lastsoundex=:soundex, loginname=:loginname, password=:password
			WHERE person_id=:person_id;";
	$stmt2 = $db->prepare($sql);
	while ($row = $stmt->fetchObject()) {
		$stmt2->bindValue(":soundex", soundex($row->lastname), PDO::PARAM_STR);
		$stmt2->bindValue(":loginname", substr($row->lastname,0,5), PDO::PARAM_STR);
		$stmt2->bindValue(":password", password_hash(substr($row->firstname,0,5), PASSWORD_DEFAULT), PDO::PARAM_STR);
		$stmt2->bindValue(":person_id", $row->person_id, PDO::PARAM_INT);
		$stmt2->execute();
	}
	return true;
}

function c02(&$db) { //rate

	$sql = "UPDATE ".$db->prefix."c02_rate SET project_idref=1;";
	$db->exec($sql);

	$sql = "SELECT person_id FROM ".$db->prefix."c00_person;";
	$stmt = $db->query($sql);
	while ($row = $stmt->fetchObject()) {
		$sql = "SELECT rate_id, effective_asof FROM ".$db->prefix."c02_rate
				WHERE person_idref=".$row->person_id." ORDER BY effective_asof DESC;";
		$stmt2 = $db->query($sql);
		if (!($row2 = $stmt2->fetchObject())) {
			$sql = "INSERT INTO ".$db->prefix."c02_rate (person_idref, project_idref)
					VALUES (".$row->person_id.", 1);";
			$db->exec($sql);
		} else {
			$expire = new DateTime($row2->effective_asof);
			while ($row2 = $stmt2->fetchObject()) {
				$expire->modify("-1 day");
				$sql = "UPDATE ".$db->prefix."c02_rate
						SET expire_after='".$expire->format("Y-m-d")."' WHERE rate_id=".$row2->rate_id.";";
				$db->exec($sql);
				$expire = new DateTime($row2->effective_asof);
			}
		}
		$stmt2->closeCursor();
	}
	$stmt->closeCursor();

	return true;
}

function setup_activity(&$db, &$csvdata, &$MDBndxs) {

	$action_id = $csvdata[$MDBndxs["Action_ID"]];
	$subtask_id = $csvdata[$MDBndxs["Subtask_IDRef"]];

	$sql = "UPDATE ".$db->prefix."b00_timelog
			SET subtask_idref=".$subtask_id." WHERE activity_idref=".$action_id.";";
	$db->exec($sql);

	$sql = "SELECT timelog_id, logdate FROM ".$db->prefix."b00_timelog
			WHERE activity_idref=".$action_id.";";
	$stmt = $db->query($sql);
	if (!($row = $stmt->fetchObject())) {
		error_log("no timelog found for action ID ".$action_id);
		return true;
//		$_STATE->msgStatus .= "<br>Load aborted: no timelog found for action ID ".$action_id;
//		return false;
	}
	$sql = "UPDATE ".$db->prefix."b02_activity
			SET timestamp='".$row->logdate."' WHERE activity_id=".$action_id.";";
	$db->exec($sql);

	return true;
}

function connect_person_org(&$db) {

	$sql = "DELETE FROM ".$db->prefix."c10_person_organization
			WHERE person_organization_id > 0;";
	$db->exec($sql);
	$db->reset_auto($db->prefix."c10_person_organization", "person_organization_id", 1); //sets next seq number
	$sql = "SELECT person_id FROM ".$db->prefix."c00_person
			WHERE person_id > 0;";
	$values = "";
	$stmt = $db->query($sql);
	while ($row = $stmt->fetchObject()) {

		//set inactive asof day after last log
		$sql2 = "SELECT logdate FROM ".$db->prefix."b00_timelog WHERE person_idref=".$row->person_id."
				ORDER BY logdate DESC;";
		$stmt2 = $db->query($sql2);
		$inactive = 'NULL';
		if ($row2 = $stmt2->fetchObject()) {
			$logdate = DateTime::createFromFormat('Y-m-d', $row2->logdate);
			$logdate->add(new DateInterval('P1D'));
			$inactive = $logdate->format("Y-m-d");
		}

		$values .= ",(".intval($row->person_id).", 1, '".$inactive."')";
	}
	$stmt->closeCursor();

	if ($values != "") {
		$sql = "INSERT INTO ".$db->prefix."c10_person_organization
				(person_idref, organization_idref, inactive_asof) VALUES ".substr($values,1).";";
		$db->exec($sql);
	}
}

function money_check($value) {
	if (substr($value,0,1) == "$" ) $value = substr($value,1); //remove $sign
	$value = "0".$value; //deal with an empty value
	$value = str_replace(",", "", $value); //remove commas
	return $value;
}

function date_check($value) {

	if ($value == "") return NULL;
	$value = str_replace("/", "-", $value);
	$date = explode("-",$value);
	if (strlen($date[0]) <= 2) $value = $date[2]."-".$date[0]."-".$date[1];
	return $value;
}

function string_check($value) {

	return string_decode($value);
}

function load(&$db, &$table) {

	$file = $GLOBALS["MDBload_path"].$table->MDBname.".csv";
	if (!file_exists($file)) return false;
	$handle = fopen($file, "r");

	$headers = fgetcsv($handle); //headers
	$MDBndxs = array();
	$ndx = 0;
	foreach ($headers as $name) {
		$MDBndxs[$name] = $ndx;
		++$ndx;
	}

	$fields = "";
	$values = "";
	foreach ($table->fields as $name => &$field) {
		if ($field->MDBname == "") continue;
		$fields .= ",".$name;
		$values .= ",:".$name;
		$field->MDBndx = $MDBndxs[$field->MDBname];
	}

	$sql = "DELETE FROM ".$table->name;
	if ($table->idname != "") $sql .= " WHERE ".$table->idname." > 0";
	$sql .= ";";
	$db->exec($sql);
	$db->reset_auto($table->name, $table->idname, 1);

	$sql = "INSERT INTO ".$table->name." (".substr($fields,1).") VALUES (".substr($values,1).")";
	$stmt = $db->prepare($sql);
	while (($csvdata = fgetcsv($handle)) !== FALSE) {
		foreach ($table->fields as $name => &$field) {
			if ($field->MDBname == "") continue; //not loadable
			$value = $csvdata[$field->MDBndx];
			if ($field->editor != "") {
				$editor = $field->editor."_check";
				$value = $editor($value);
			}
			if (($name == "description") && ($value == "")) $value = "*";
			$stmt->bindValue(":".$name, $value, $field->type);
		}
		$stmt->execute();
		if ($table->name == $db->prefix."b02_activity") {
			if (!setup_activity($db, $csvdata, $MDBndxs)) return false;
		}
	}
	fclose($handle);

	if ($table->idname != "") {
		$sql = "SELECT max(\"".$table->idname."\") AS maxauto FROM ".$table->name.";";
		$stmt = $db->query($sql);
		$row = $stmt->fetchObject();
		$stmt->closeCursor();

		$db->reset_auto($table->name, $table->idname, $row->maxauto+1); //sets next seq number
	}

	$prefix = substr($table->name, strlen($db->prefix), 3);
	if (function_exists($prefix)) {
		if (!$prefix($db)) return false;
	}

	return true;
}

function entry_audit() {
	global $_STATE;

	if (!isset($_POST["chkTable"])) {
		$_STATE->msgStatus = "No tables were loaded";
		return;
	}

	tables_list();
	try {
		//Use an unprintable char as the delimiter:
		$db = new db_connect("\r".$_POST["txtName"]."\r".$_POST["txtPswd"]);
	} catch (PDOException $e) {
	    $_STATE->msgStatus = "Connection failed: ".$e->getMessage();
		return;
	}
	foreach ($_POST["chkTable"] as $ID => $value) {
		if (!array_key_exists($ID, $_STATE->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid table name ".$_POST["chkTable"]);
		}
		if ($value == "on") {
			$_STATE->msgStatus .= $ID;
			if ($_STATE->records[$ID]->MDBname == "") {
				throw_the_bum_out(NULL,"Evicted(".__LINE__."): non-loadable table ".$_POST["chkTable"]);
			}
			if (!load($db, $_STATE->records[$ID])) {
				$_STATE->msgStatus .= "<br>attempted load of ". $GLOBALS["MDBload_path"].$_STATE->records[$ID]->MDBname.".csv"." failed";
			}
			$_STATE->msgStatus .= "<br>";
		}
	}
	$db = NULL;
	return;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	tables_list();
	$_STATE->msgGreet = "Check the tables to load";
	$_STATE->status = STATE::UPDATE;
	break 2;
case STATE::UPDATE:
	$_STATE->msgGreet = "Tables loaded:";
	entry_audit();
	$_STATE->status = STATE::DONE;
	break 2;
default:
	$_STATE->status = STATE::ERROR;
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} } //while & switch

EX_pageStart(); //standard HTML page start stuff - insert scripts here
EX_pageHead(); //standard page headings - after any scripts
?>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<table align='center'>
<?php
	foreach($_STATE->records as $ID => $table) {
		if ($table->MDBname == "") continue; //not loadable
		echo "  <tr><td>";
  		echo "<input type=\"checkbox\" name=\"chkTable[".strval($ID)."]\">";
		echo $ID;
		if ($ID == "timelog") echo " (must load activity to connect subtask, etc.)";
		echo "</td></tr>\n";
	} ?>
</table>
<p>
Username: <input name="txtName" id="txtName_ID" type="text" class="formInput" maxlength="32" size="32">
  Password: <input name="txtPswd" type="password" class="formInput" maxlength="32" size="32">
</p>
  <button type="submit">Load</button>
</form>
<?php
EX_pageEnd(); //standard end of page stuff
?>


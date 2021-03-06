<?php //copyright 2010,2014-2015 C.D.Price
if (!$_PERMITS->can_pass("project_edit")) throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");

require_once "field_edit.php";

function state_fields() {
	global $_DB, $_STATE;

	$_STATE->fields = array( //pagename,DBname,load from DB?,write to DB?,required?,maxlength
			"Name"=>new FIELD("txtName","name",TRUE,TRUE,TRUE,64),
			"Description"=>new AREA_FIELD("txtDesc","description",TRUE,TRUE,TRUE,256),
			"Close Date"=>new DATE_FIELD("txtClose","close_date",TRUE,TRUE,TRUE,0),
			"Inactive As Of"=>new DATE_FIELD("txtInactive","inactive_asof",TRUE,TRUE,FALSE,0),
			);
	$_STATE->accounting = array();
	$sql = "SELECT * FROM ".$_DB->prefix."a20_accounting
			WHERE organization_idref=".$_SESSION["organization_id"]." ORDER BY timestamp;";
	$stmt = $_DB->query($sql);
	while ($row = $stmt->fetchObject()) {
		$_STATE->accounting[strval($row->accounting_id)] = substr($row->name.": ".$row->description,0,25);
	}
	$stmt->closeCursor();
}

function record_info() {
	global $_DB, $_STATE;

	$sql = "SELECT * FROM ".$_DB->prefix."a10_project WHERE project_id=".$_STATE->record_id.";";
	$stmt = $_DB->query($sql);
	$row = $stmt->fetchObject();
	foreach($_STATE->fields as $field => &$props) { //preset record info on the page
		if ($props->load_from_DB) {
			$props->value($row->{$props->dbname});
		}
	}
	$_STATE->accounting_id = $row->accounting_idref;
	$stmt->closeCursor();
}

function field_input_audit() {
	global $_STATE;

	$errors = "";
	foreach($_STATE->fields as $name => $field) {
		if (($msg = $field->audit()) === true) continue;
		$errors .= "<br>".$name.": ".$msg;
	}
	if ($errors != "") {
		$_STATE->msgStatus = "Error:".$errors;
		return false;
	}

	$diff = date_diff($_STATE->fields["Close Date"]->value, COM_NOW(), true);
	if ($diff->m > 2) {
		$_STATE->msgStatus = "The Close Date is suspect - proceeding anyway";
	}

	if (!array_key_exists(strval($_POST["selAccounting"]), $_STATE->accounting)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid accounting id ".$_POST["selAccounting"]); //we're being spoofed
	}
	$_STATE->accounting_id = intval($_POST["selAccounting"]);

//Should check to see if inactive is greater than any timelogs?

	foreach ($_STATE->fields as $name => $field) {
		$field->disabled = true;
	}

	return TRUE;

}

function update_db() {
	global $_DB, $_STATE;

	$sql = "UPDATE ".$_DB->prefix."a10_project
			SET name=:name, description=:description, close_date=:close,
			accounting_idref=:accounting, inactive_asof=:inactive
			WHERE project_id=".$_STATE->record_id.";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':name', $_STATE->fields["Name"]->value(), PDO::PARAM_STR);
	$stmt->bindValue(':description', $_STATE->fields["Description"]->value(), PDO::PARAM_STR);
	$stmt->bindValue(':close', $_STATE->fields["Close Date"]->value(), db_connect::PARAM_DATE);
	$stmt->bindValue(':accounting', $_STATE->accounting_id, PDO::PARAM_INT);
	if ($_STATE->fields["Inactive As Of"]->value() == "") {
		$stmt->bindValue(':inactive', NULL, db_connect::PARAM_DATE);
	} else {
		$stmt->bindValue(':inactive', $_STATE->fields["Inactive As Of"]->value(), db_connect::PARAM_DATE);
	}
	$stmt->execute();
}

function update_audit() {
	global $_STATE;

	if (!field_input_audit()) return FALSE;

	update_db();

	$_STATE->msgStatus = "The project record for \"".$_STATE->fields["Name"]->value()."\" has been updated";
	return TRUE;
}

function new_audit() {
	global $_DB, $_STATE;

	if (!field_input_audit()) return FALSE;

	//hash the name to make sure we get this record back - then update it with correct name:
	$hash = md5($_STATE->fields["Name"]->value().$_STATE->fields["Description"]->value());
	$sql = "INSERT INTO ".$_DB->prefix."a10_project (name, organization_idref)
			VALUES (:hash,".$_SESSION["organization_id"].");";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();

	$sql = "SELECT project_id FROM ".$_DB->prefix."a10_project WHERE name=:hash;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();
	$_STATE->record_id = $stmt->fetchObject()->project_id;
	$stmt->closeCursor();

	update_db();

	$sql = "INSERT INTO ".$_DB->prefix."a12_task (project_idref,name,description)
			VALUES (".$_STATE->record_id.",'".$hash."','initial seed task - please change');";
	$_DB->exec($sql);
	$sql = "SELECT task_id FROM ".$_DB->prefix."a12_task WHERE name='".$hash."';";
	$stmt = $_DB->query($sql);
	$ID = $stmt->fetchObject()->task_id;
	$stmt->closeCursor();
	$sql = "UPDATE ".$_DB->prefix."a12_task SET name='seed' WHERE task_id=".$ID.";";
	$_DB->exec($sql);

	$sql = "INSERT INTO ".$_DB->prefix."a14_subtask (task_idref,name,description)
			VALUES (".$ID.",'seed','initial seed subtask - please change');";
	$_DB->exec($sql);

	$_STATE->msgStatus = "The project record for \"".$_STATE->fields["Name"]->value()."\" has been added to your organization";
	return TRUE;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->accounting_id = 0;
	$_STATE->accounting = array();
	$_STATE->noSleep[] = "accounting";
	require_once "project_select.php";
	$projects = new PROJECT_SELECT();
	$projects->show_new = true;
	$_STATE->project_select = serialize(clone($projects));
	$_STATE->msgGreet = "Select a project record to edit";
	$_STATE->status = STATE::SELECT;
	break 2;
case STATE::SELECT:
	require_once "project_select.php"; //catches $_GET list refresh (assumes break 2)
	$projects = unserialize($_STATE->project_select);
	$projects->set_state();
	$_STATE->record_id = $_STATE->project_id;
	$_STATE->status = STATE::SELECTED; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case STATE::SELECTED:
	state_fields(); //creates the accounting list for display
	if ($_STATE->record_id == -1) {
		$_STATE->msgGreet = "New project record";
		$_STATE->status = STATE::ADD;
	} else {
		record_info();
		$_STATE->msgGreet = "Edit project record";
		$_STATE->status = STATE::UPDATE;
	}
	break 2;
case STATE::ADD:
	state_fields(); //creates the accounting list for audit
	$_STATE->msgGreet = "New project record";
	if (isset($_POST["btnReset"])) {
		break 2;
	}
//	if ($_POST["btnSubmit"] != "add") { //IE < v8 submits name/InnerText NOT name/value
//		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid btnSubmit ".$_POST["btnSubmit"]);
//	}
	if (new_audit()) {
		$_STATE->status = STATE::DONE;
		$_STATE->goback(1); //setup for goback
	}
	break 2;
case STATE::UPDATE:
	state_fields(); //creates the accounting list for audit
	$_STATE->msgGreet = "Edit project record";
	if (isset($_POST["btnReset"])) {
		record_info();
		break 2;
	}
//	if ($_POST["btnSubmit"] != "update") {
//		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid btnSubmit ".$_POST["btnSubmit"]);
//	}
	if (update_audit()) {
		$_STATE->status = STATE::DONE;
		$_STATE->goback(1); //setup for goback
	}
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} } //while & switch

EX_pageStart(); //standard HTML page start stuff - insert scripts here

if ($_STATE->status == STATE::SELECT)
	echo "<script type='text/javascript' src='".$_SESSION["_SITE_CONF"]["_REDIRECT"].
			"/scripts/call_server.js'></script>\n";

EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case STATE::SELECT:

	echo $projects->set_list();

	break; //end STATE::SELECT status ----END STATUS PROCESSING----
default:
?>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
  <table align="center">
    <tr>
      <td class="label"><?php echo $_STATE->fields['Name']->HTML_label("Name: "); ?></td>
      <td colspan="2"><?php echo $_STATE->fields['Name']->HTML_input(20) ?></td>
    </tr>
    <tr>
      <td class="label"><?php echo $_STATE->fields['Description']->HTML_label("Description: "); ?></td>
      <td colspan="2"><?php echo $_STATE->fields['Description']->HTML_input(32); ?></td>
    </tr>
    <tr>
      <td class="label"><?php echo $_STATE->fields['Close Date']->HTML_label("Log entry Close Date(YYYY-MM-DD): "); ?></td>
      <td><?php echo $_STATE->fields['Close Date']->HTML_input(0) ?></td>
       <td>&nbsp</td>
    </tr>
    <tr>
      <td class="label"><?php echo $_STATE->fields['Inactive As Of']->HTML_label("Inactive As Of(YYYY-MM-DD): "); ?></td>
      <td><?php echo $_STATE->fields['Inactive As Of']->HTML_input(0) ?></td>
      <td>&nbsp</td>
    </tr>
    <tr>
      <td class="label"><label for="selAccounting_ID" class='required'>*Accounting group:</label></td>
      <td>
        <select name='selAccounting' id='selAccounting_ID' size="<?php echo count($_STATE->accounting); ?>">
<?php
	foreach($_STATE->accounting as $value => $name) {
  		echo "        <option value=\"".$value."\"";
		if ($_STATE->accounting_id == $value) echo " selected";
		echo ">".$name."\n";
	} ?>
        </select>
      </td>
      <td>&nbsp</td>
    </tr>
  </table>
  <p>
<?php
	if ($_STATE->status != STATE::DONE) {
		if ($_STATE->status == STATE::ADD ) {
			echo FIELD_edit_buttons(FIELD_ADD);
		} else {
			echo Field_edit_buttons(FIELD_UPDATE);
		}
	} ?>
</form>
<?php //end default status ----END STATUS PROCESSING----
}
EX_pageEnd(); //standard end of page stuff
?>


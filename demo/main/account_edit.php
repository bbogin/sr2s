<?php //copyright 2010,2014 C.D.Price
if (!$_PERMITS->can_pass("account_edit")) throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");

require_once "field_edit.php";
define('SELECT_ACCOUNTING', STATE::SELECT);
define('SELECTED_ACCOUNTING', STATE::SELECTED);
define('SELECT_ACCOUNT', STATE::SELECT + 1);
define('SELECTED_ACCOUNT', STATE::SELECTED + 1);

function state_fields() {
	global $_STATE;

	$_STATE->fields = array( //pagename,DBname,load from DB?,write to DB?,required?,maxlength
			"Name"=>new FIELD("txtName","name",TRUE,TRUE,TRUE,64),
			"Description"=>new AREA_FIELD("txtDesc","description",TRUE,TRUE,TRUE,256),
			"Inactive As Of"=>new DATE_FIELD("txtInactive","inactive_asof",TRUE,TRUE,FALSE,0),
			);
}

function accounting_list() {
	global $_DB, $_STATE;

	$_STATE->records = array();
//	$_STATE->records["-1"] = "--create a new accounting group--";

	$sql = "SELECT * FROM ".$_DB->prefix."a20_accounting
			WHERE organization_idref=".$_SESSION["organization_id"]." ORDER BY timestamp;";
	$stmt = $_DB->query($sql);
	while ($row = $stmt->fetchObject()) {
		$_STATE->records[strval($row->accounting_id)] = substr($row->name.": ".$row->description,0,25);
	}
	$stmt->closeCursor();
}

function accounting_select($ID=-1) {
	global $_DB, $_STATE;

	if ($ID < 0) { //not yet selected
		accounting_list(); //restore the record list
		if (!array_key_exists(strval($_POST["selAccounting"]), $_STATE->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid accounting id ".$_POST["selAccounting"]); //we're being spoofed
		}
		$ID = intval($_POST["selAccounting"]);
	}
	$_STATE->accounting_id = $ID;
	$sql = "SELECT name FROM ".$_DB->prefix."a20_accounting
			WHERE accounting_id=".$_STATE->accounting_id.";";
	$_STATE->accounting = $_DB->query($sql)->fetchObject()->name;
}

function account_list() {
	global $_DB, $_STATE;

	$_STATE->records = array();
	$_STATE->records["-1"] = "--create a new ".$_STATE->accounting." record--";

	$sql = "SELECT * FROM ".$_DB->prefix."a21_account
			WHERE accounting_idref=".$_STATE->accounting_id." ORDER BY name;";
	$stmt = $_DB->query($sql);
	while ($row = $stmt->fetchObject()) {
		$_STATE->records[strval($row->account_id)] = substr($row->name.": ".$row->description,0,40);
	}
	$stmt->closeCursor();
}

function account_select($ID=-1) {
	global $_STATE;

	if ($ID < 0) { //not yet selected
		account_list(); //restore the record list
		if (!array_key_exists(strval($_POST["selAccount"]), $_STATE->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid account id ".$_POST["selAccount"]); //we're being spoofed
		}
		$ID = intval($_POST["selAccount"]);
	}
	$_STATE->record_id = $ID;
}

function account_info() {
	global $_DB, $_STATE;

	$sql = "SELECT * FROM ".$_DB->prefix."a21_account WHERE account_id=".$_STATE->record_id.";";
	$stmt = $_DB->query($sql);
	$row = $stmt->fetchObject();
	foreach($_STATE->fields as $field=>&$props) { //preset record info on the page
		if ($props->load_from_DB) {
			$props->value($row->{$props->dbname});
		}
	}
	$stmt->closeCursor();
}

function field_input_audit() {
	global $_STATE;

	$errors = "";
	foreach($_STATE->fields as $name => $field) {
		//allow an "*" for the name field:
		if (($name == "Name") && ($_POST[$field->pagename] == "*")) {
			$field->value = "*";
			continue;
		}
		if (($msg = $field->audit()) === true) continue;
		$errors .= "<br>".$name.": ".$msg;
	}
	if ($errors != "") {
		$_STATE->msgStatus = "Error:".$errors;
		return false;
	}

//Should check to see if inactive is greater than any timelogs?

	foreach ($_STATE->fields as $name => $field) {
		$field->disabled = true;
	}

	return TRUE;
}

function update_db() {
	global $_DB, $_STATE;

	$sql = "UPDATE ".$_DB->prefix."a21_account
			SET name=:name, description=:description, inactive_asof=:inactive
			WHERE account_id=".$_STATE->record_id.";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':name',$_STATE->fields["Name"]->value(),PDO::PARAM_STR);
	$stmt->bindValue(':description',$_STATE->fields["Description"]->value(),PDO::PARAM_STR);
	if ($_STATE->fields["Inactive As Of"]->value() == "") {
		$stmt->bindValue(':inactive', NULL, db_connect::PARAM_DATE);
	} else {
		$stmt->bindValue(':inactive',$_STATE->fields["Inactive As Of"]->value(),db_connect::PARAM_DATE);
	}
	$stmt->execute();
}

function update_audit() {
	global $_STATE;

	if (!field_input_audit()) return FALSE;

	update_db();

	$_STATE->msgStatus = "The ".$_STATE->accounting." record for \"".$_STATE->fields["Name"]->value()."\" has been updated";
	return TRUE;
}

function new_audit() {
	global $_DB, $_STATE;

	if (!field_input_audit()) return FALSE;
	
	$hash = md5($_STATE->fields["Name"]->value().$_STATE->fields["Description"]->value());
	$sql = "INSERT INTO ".$_DB->prefix."a21_account (name, accounting_idref)
			VALUES (:hash, ".$_STATE->accounting_id.");";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();

	$sql = "SELECT account_id FROM ".$_DB->prefix."a21_account WHERE name=:hash;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();
	$_STATE->record_id = $stmt->fetchObject()->account_id;
	$stmt->closeCursor();

	update_db();

	$_STATE->msgStatus = "The ".$_STATE->accounting." record for \"".$_STATE->fields["Name"]->value()."\" has been added to the accounting group";
	return TRUE;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->accounting_id = 0;
	accounting_list();
	if (count($_STATE->records) == 1) { //solo group?
		$record = each($_STATE->records);
		accounting_select($record[0]); //select this one
		$_STATE->status = SELECTED_ACCOUNTING;
		break 1; //re-switch to SELECTED_ACCOUNTING
	}
	$_STATE->msgGreet = "Select the accounting group";
	$_STATE->status = SELECT_ACCOUNTING;
	break 2;
case SELECT_ACCOUNTING:
	accounting_select(); //select from POST
	$_STATE->heading .= "<br>Accounting group: ".$_STATE->records[$_STATE->accounting_id];
	$_STATE->status = SELECTED_ACCOUNTING;
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_ACCOUNTING:
	account_list();
	$_STATE->msgGreet = "Select the ".$_STATE->accounting." record to edit";
	$_STATE->status = SELECT_ACCOUNT;
	break 2;
case SELECT_ACCOUNT:
	account_select();
	$_STATE->status = SELECTED_ACCOUNT;
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_ACCOUNT:
	state_fields();
	if ($_STATE->record_id == -1) {
		$_STATE->msgGreet = "New ".$_STATE->accounting." record";
		$_STATE->status = STATE::ADD;
	} else {
		account_info();
		$_STATE->msgGreet = "Edit ".$_STATE->accounting." record?";
		$_STATE->status = STATE::UPDATE;
	}
	break 2;
case STATE::ADD:
	$_STATE->msgGreet = "New ".$_STATE->accounting." record";
	if (isset($_POST["btnReset"])) {
		break 2;
	}
//	if ($_POST["btnSubmit"] != "add") { //IE < v8 submits name/InnerText NOT name/value
//		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid btnSubmit ".$_POST["btnSubmit"]);
//	}
	state_fields();
	if (new_audit()) {
		$_STATE->status = STATE::DONE;
		$_STATE->goback(1); //setup for goback
	}
	break 2;
case STATE::UPDATE:
	$_STATE->msgGreet = "Edit ".$_STATE->accounting." record";
	if (isset($_POST["btnReset"])) {
		record_info($_DB, $_STATE);
		break 2;
	}
//	if ($_POST["btnSubmit"] != "update") {
//		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid btnSubmit ".$_POST["btnSubmit"]);
//	}
	state_fields();
	if (update_audit()) {
		$_STATE->status = STATE::DONE;
		$_STATE->goback(1); //setup for goback
	}
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} } //while & switch

EX_pageStart(); //standard HTML page start stuff - insert scripts here
EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case SELECT_ACCOUNTING:
?>
  <p>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
  <select name='selAccounting' size="<?php echo count($_STATE->records); ?>" onclick="this.form.submit()">
<?php
	foreach($_STATE->records as $value => $name) {
		echo "    <option value=\"".$value."\">".$name."\n";
	} ?>
  </select>
</form>
  </p>
<?php //end SELECT_ACCOUNTING status ----END STATUS PROCESSING----
	break;
case SELECT_ACCOUNT:
?>
  <p>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
  <select name='selAccount' size="<?php echo count($_STATE->records); ?>" onclick="this.form.submit()">
<?php
	foreach($_STATE->records as $value => $name) {
		echo "    <option value=\"".$value."\">".$name."\n";
	} ?>
  </select>
</form>
  </p>
<?php //end SELECT_ACCOUNT status ----END STATUS PROCESSING----
	break;
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
      <td class="label"><?php echo $_STATE->fields['Inactive As Of']->HTML_label("Inactive As Of(yyyy-mm-dd): "); ?></td>
      <td><?php echo $_STATE->fields['Inactive As Of']->HTML_input(10) ?></td>
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


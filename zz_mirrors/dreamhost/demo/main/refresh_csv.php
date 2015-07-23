<?php //copyright 2010,2014-2015 C.D.Price
if (($_SESSION["_SITE_CONF"]["RUNLEVEL"] < 1) || (!$_PERMITS->can_pass(PERMITS::_SUPERUSER)))
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");
require_once ("rarities.php");

require_once ("tables_list.php");

function money_check($value) {

	if (substr($value,0,1) == "$" ) $value = substr($value,1,0); //remove $sign
	$value = "0".$value; //deal with an empty value
	$value = str_replace(",", "", $value); //remove commas
	return $value;
}

function date_check($value) {

	if ($value == "") return NULL;
	$value = str_replace("/", "-", $value);
	$date = explode("-",$value);
	//create form yyy-mm-dd:
	if (strlen($date[0]) <= 2) $value = $date[2]."-".$date[0]."-".$date[1];
	return $value;
}

function string_check($value) {

	return COM_string_decode($value);
}

function refresh(&$db, &$table) {
	global $_STATE;

	$file = $GLOBALS["refresh_path"].$table->name.".csv";
	if (!file_exists($file)) {
		$_STATE->msgStatus = "Cannot find file ".$file;
		return false;
	}
	$handle = fopen($file, "r");

	$fields = "";
	$values = "";
	if (($names = fgetcsv($handle)) === false) { //csv headers
		$sql = "DELETE FROM ".$table->name." WHERE ".$table->idname." > 0;";
		$db->exec($sql);
		$db->reset_auto($table->name, $table->idname, 1);
		return true; //empty
	}
	foreach ($names as $name) {
		$fields .= ",".$name;
		$values .= ",:".$name;
	}

	if (isset($_POST["btnRestart"])) {
		$sql = "SELECT max(".$table->idname.") AS maxauto FROM ".$table->name.";";
		$stmt = $db->query($sql);
		$row = $stmt->fetchObject();
		$restart = $row->maxauto;
		$stmt->closeCursor();
		do {
			if (($data = fgetcsv($handle)) === FALSE) {
				$_STATE->msgStatus = "Restart beyond input range; restart at ".$restart;
				return false;
			}
		} while ($data[0] < $restart);

	} else {
		$sql = "DELETE FROM ".$table->name." WHERE ".$table->idname." > 0;";
		$db->exec($sql);
		$db->reset_auto($table->name, $table->idname, 1);
	}

	if ($_POST["txtCount"] == "") {
		$count = -1;
	} else {
		$count = $_POST["txtCount"];
	}

	$sql = "INSERT INTO ".$table->name." (".substr($fields,1).") VALUES (".substr($values,1).")";
	$stmt = $db->prepare($sql);
	while (($data = fgetcsv($handle)) !== FALSE) {
		if ($count == 0) break;
		--$count;
		$ndx = 0;
		foreach ($names as $name) {
			$field = $table->fields[$name];
			$value = $data[$ndx];
			if ($field->editor != "") {
				$editor = $field->editor."_check";
				$value = $editor($value);
			}
			$stmt->bindValue(":".$name, $value,$field->type);
			++$ndx;
		}
		$stmt->execute();
	}
	fclose($handle);

	if ($table->idname != "") {
		$sql = "SELECT max(".$table->idname.") AS maxauto FROM ".$table->name.";";
		$stmt = $db->query($sql);
		$row = $stmt->fetchObject();
		$stmt->closeCursor();

		$db->reset_auto($table->name, $table->idname, $row->maxauto+1);
	}

	return true;
}

function entry_audit() {
	global $_STATE;

	if (!isset($_POST["chkTable"])) {
		$_STATE->msgStatus = "No tables were refreshed";
		return;
	}

	if (($_POST["txtCount"] != "") && (!is_numeric($_POST["txtCount"]))) {
		$_STATE->msgStatus = "Invalid 'Stop after' count";
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
			if (!refresh($db, $_STATE->records[$ID])) {
				$_STATE->msgStatus .= ": attempted refresh failed";
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
	$_STATE->msgGreet = "Check the tables to refresh";
	$_STATE->status = STATE::UPDATE;
	break 2;
case STATE::UPDATE:
	$_STATE->msgGreet = "Tables refreshed:";
	entry_audit();
	$_STATE->status = STATE::DONE;
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} } //while & switch

EX_pageStart(); //standard HTML page start stuff - insert scripts here

EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case STATE::UPDATE:
?>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<table align='center'>
<?php
	foreach($_STATE->records as $ID => $name) {
		echo "  <tr>\n";
	  	echo "    <td><input type=\"checkbox\" name=\"chkTable[".strval($ID)."]\"></td>\n";
		echo "    <td style='text-align:left'>".$ID."</td>\n";
		echo "  </tr>\n";
	} ?>
</table>
<p>
Username: <input name="txtName" id="txtName_ID" type="text" class="formInput" maxlength="32" size="32">
  Password: <input name="txtPswd" type="password" class="formInput" maxlength="32" size="32">
</p>
  <button type="submit" name="btnRefresh">Refresh</button>
  <button type="submit" name="btnRestart">Restart</button>
Stop after <input name="txtCount" type="text" class="formInput" maxlength="5" size="5" value=""> records
</form>
<?php //end STATE::UPDATE status ----END STATUS PROCESSING----
} ?>

<?php
EX_pageEnd(); //standard end of page stuff
?>


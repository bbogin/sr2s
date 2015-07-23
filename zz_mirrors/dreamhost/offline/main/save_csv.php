<?php //copyright 2010,2014 C.D.Price
if (($_SESSION["_SITE_CONF"]["RUNLEVEL"] < 1) || (!$_PERMITS->can_pass(PERMITS::_SUPERUSER)))
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");
require_once ("rarities.php");

require_once ("tables_list.php");

function save(&$table) {
	global $_DB;

	$file = $GLOBALS["refresh_path"].$table->name.".csv";
	$handle = fopen($file, "w");

//	if (substr($table->name, 0, 3) == "a00") {
//		$sql = ""; //skip the logo blog (headers?)
//	} else {
		$sql = "SELECT * FROM ".$table->name." WHERE ".$table->idname." > 0;";
//	}
	$stmt = $_DB->query($sql);
	if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
		fclose($handle);
		return true;
	}
	$line = array();
	foreach ($row as $name => $value) {
		$line[] = $name;
	}
	fputcsv ($handle, $line); //headers
	do {
		fputcsv ($handle, $row);
	} while ($row = $stmt->fetch(PDO::FETCH_NUM));
	$stmt->closeCursor();

	fclose($handle);

	return true;
}

function entry_audit() {
	global $_STATE;

	if (!isset($_POST["chkTable"])) {
		$_STATE->msgStatus = "No tables were saved";
		return;
	}

	tables_list();
	foreach ($_POST["chkTable"] as $ID => $value) {
		if (!array_key_exists($ID, $_STATE->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid table name ".$_POST["chkTable"]);
		}
		if ($value == "on") {
			$_STATE->msgStatus .= $ID;
			if (!save($_STATE->records[$ID])) {
				$_STATE->msgStatus .= ": attempted save failed";
			}
			$_STATE->msgStatus .= "<br>";
		}
	}
	return;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	tables_list();
	$_STATE->msgGreet = "Check the tables to save";
	$_STATE->status = STATE::UPDATE;
	break 2;
case STATE::UPDATE:
	$_STATE->msgGreet = "Tables saved:";
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
  		echo "    <td><input type=\"checkbox\" name=\"chkTable[".strval($ID)."]\" checked></td>\n";
		echo "    <td style='text-align:left'>".$ID."</td>\n";
		echo "  </tr>\n";
	} ?>
</table>
  <button type="submit">Save</button>
</form>
<?php //end STATE::UPDATE status ----END STATUS PROCESSING----
} ?>

<?php
EX_pageEnd(); //standard end of page stuff
?>


<?php //copyright 2014 C.D.Price
define ('FILL_CELLS', STATE::SELECT + 4);

function setup(&$db, &$state) {
	$sql = "SELECT b00.logdate, a21.account_id, a21.name, a21.description FROM b00_timelog AS b00
			INNER JOIN a21_account AS a21 ON a21.account_id = b00.account_idref
			WHERE b00.timelog_id = ".$_GET["recid"].";";
	$stmt = $db->query($sql);
	$row = $stmt->fetchObject();
	$state->heading .= "<br>for ".$row->name.": ".$row->description;
	$state->heading .= "<br>on ".$row->logdate;
	$state->logdate = $row->logdate;
	$state->account_id = $row->account_id;
	$stmt->closeCursor();
	log_list($db, $state);
}

function log_list(&$db, &$state) {

	$state->records = array();

	$sql = "SELECT b10.eventlog_id, b10.logdate, b10.session_count, b10.attendance, b10.comments,
			a30.event_id, a30.name AS event, a30.description AS event_desc,
			a21.account_id, a21.name AS account, a21.description AS account_desc
			FROM ".$db->prefix."b10_eventlog AS b10
			JOIN ".$db->prefix."a30_event AS a30 ON a30.event_id = b10.event_idref
			JOIN ".$db->prefix."a10_project AS a10 ON a10.project_id = a30.project_idref
			JOIN ".$db->prefix."a00_organization AS a00 ON a00.organization_id = a10.organization_idref
			JOIN ".$db->prefix."a21_account AS a21 ON a21.account_id = b10.account_idref
			WHERE (b10.person_idref=".$state->person_id.") AND (project_id=".$state->project_id.")
			AND (logdate = :logdate)
			ORDER BY logdate, event_id, account_id;";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':logdate', $state->logdate, db_connect::PARAM_DATE);
	$stmt->execute();
	while ($row = $stmt->fetchObject()) {
		$record = array(
			"ID" => $row->eventlog_id,
			"event" => substr($row->event.": ".$row->event_desc,0,25),
			"event_id" => $row->event_id,
			"logdate" => new DateTime($row->logdate),
			"session_count" => $row->session_count,
			"attendance" => $row->attendance,
			"comments" => $row->comments
		);
		$state->records[strval($row->eventlog_id)] = $record;
	}
	$stmt->closeCursor();
}

function cell_desc(&$db, &$state) {

	$HTML = "alert(myCell.title);\n";
	$field = "description";
	switch ($_GET["getdesc"]) {
	case "EV":
		$table = $db->prefix."a30_event";
		$id = "event_id";
		break;
	case "CM":
		$HTML = "got_comments();\n";
		$field = "comments";
		$table = $db->prefix."b10_eventlog";
		$id = "eventlog_id";
		break;
	default:
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid cell ID ".$_GET["getdesc"], true);
	}
	$key = $_GET["ID"];
	$sql = "SELECT ".$field." FROM ".$table." WHERE ".$id."=:key;";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(":key", $key, PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchObject();
	$HTML = "@myCell.title = '".$row->{$field}."';\n".$HTML;
	echo $HTML;
}

function event_list(&$db, &$state) {

	$state->records = array();

	$sql = "SELECT * FROM ".$db->prefix."a30_event
			WHERE project_idref=".$state->project_id."
			ORDER BY name;";
	$stmt = $db->query($sql);
	while ($row = $stmt->fetchObject()) {
		$element = array();
		if ($row->name == "*") {
			$element[0] = "N/A";
		} else {
			$element[0] = substr($row->name.": ".$row->description,0,25);
		}
		$element[1] = "";
		if (!is_null($row->inactive_asof)) {
			$inact = new DateTime($row->inactive_asof);
			if ($inact <= $state->logdate) continue;
		}
		$state->records[strval($row->event_id)] = $element;
	}
	$stmt->closeCursor();
}

function event_send(&$db, &$state) {
	event_list($db, $state);

	echo "@//next cell...\n";
	echo "cell = myCell;\n";
	echo "fill=new Object();\n";
	echo "fill.nextCell='SN';\n"; //Sessions cell will be filled next
	echo "fill.HTML = '';\n";
	if (count($state->records) == 1) {
		reset($state->records);
		$solo = each($state->records);
		echo "fill.getNext=true;\n"; //no choice: display this and go to next cell
		echo "fill.selectedOption=".intval($solo["key"]).";\n";
		echo "fill.HTML += \"".$solo['value']."\";\n";

	} else {
		echo "fill.getNext=false;\n"; //wait for user to select before going to next cell
		echo "fill.msg='Select the ".$state->title_singular."';\n";
		echo "fill.HTML += \"<select name='selEvent' id='selEvent' size='1' onchange='list_item(this)'>\";\n";
		foreach($state->records as $value => $name) {
			$title = $name[1];
			$opacity = "1.0";
			if ($title != "") {
				$date = explode("-", $title);
				$date[1] -= 1; //month is 0 rel in JS
				$title = " title='inactive as of ".$title."' data-inact='new Date(".implode(",",$date).")'";
				$opacity = "0.5";
			}
			echo "fill.HTML += \"<option ".$title." value='".$value."' style='opacity:".$opacity."'>".$name[0]."\";\n";
		}
		echo "fill.HTML += \"</select>\";\n";
		echo "fill.run = \"document.getElementById('selEvent').selectedIndex=-1\";\n";
	}
	echo "fill_cell(cell, fill);\n";
}

function event_select(&$db, &$state) {

	if (!isset($_GET["selectedOption"])) return;
	$rec = strval($_GET["selectedOption"]);

	event_list($db, $state); //restore the record list
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid event id ".$rec,true);
	}
	if ($state->records[$rec][1] != "") {
		$inactive = new DateTime($state->records[$rec][1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
		}
	}
	$state->event_id = $rec;
	$state->msgStatus = "";
}

function input_send(&$db, &$state) {

	$recID = $_GET["recID"];
	if ($recID == 0) {
		$sessions = 0;
		$attendance = 0;
		$comments = "...click here...";
	} else {
		log_list($db, $state);
		if (!array_key_exists(strval($recID), $state->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid recID ".$recID,true);
		}
		$sessions = $state->records[$recID]["session_count"];
		$attendance = $state->records[$recID]["attendance"];
		$comments = $state->records[$recID]["comments"];
	}

	echo "@//Sessions...\n";
	echo "cell = myCell;\n";
	echo "fill=new Object();\n";
	echo "fill.nextCell='';\n"; //no more cells to fill
	echo "fill.getNext=false;\n"; //..ditto..
	echo "fill.selectedOption=0;\n";
	echo "fill.HTML = \"<input type='text' name='txtSessions' id='txtSessions_ID' size='3'";
	echo " maxlength='3' class='number' onblur='audit_count(this,4)' value='".$sessions."'>\";\n";
	echo "fill_cell(cell, fill);\n";

	echo "//Attendance...\n";
	echo "cell = document.getElementById('AD_'+selectedRow);\n";
	echo "fill=new Object();\n";
	echo "fill.nextCell='';\n";
	echo "fill.getNext=false;\n";
	echo "fill.selectedOption=0;\n";
	echo "fill.HTML = \"<input type='text' name='txtAttendance' id='txtAttendance_ID' size='5'";
	echo " maxlength='5' class='number' onblur='audit_count(this,99)' value='".$attendance."'>\";\n";
	echo "fill_cell(cell, fill);\n";

	echo "//Comments...\n";
	echo "cell = document.getElementById('CM_'+selectedRow);\n";
	echo "cell.onclick = new Function('show_comments(this, false)');\n";
	echo "cell.title = '';\n";
	echo "fill=new Object();\n";
	echo "fill.nextCell='';\n";
	echo "fill.getNext=false;\n";
	echo "fill.selectedOption=0;\n";
	echo "fill.HTML = '".$comments."';\n";
	echo "fill_cell(cell, fill);\n";

	echo "//Buttons...\n";
	echo "cell = document.getElementById('BN_'+selectedRow);\n";
	echo "fill=new Object();\n";
	echo "fill.nextCell='';\n";
	echo "fill.getNext=false;\n";
	echo "fill.msg='Enter the ".$state->title_singular." info";
	if ($recID != 0) echo " (Sessions = 0 deletes)";
	echo "';\n";
	echo "fill.HTML=\"<button type='button' onclick='new_info(";
	if ($recID == 0) {
		echo "0)'>Submit the ".$state->title_singular." info</button>";
	} else {
		echo $recID.")'>Submit the changes</button>";
	}
	echo "<br><button type='button' name='btnReset' onclick='Reset()'>Cancel</button>";
	echo "\";\n";
	echo "fill_cell(cell, fill);\n";

}

function audit_counts(&$db, &$state) {

	$state->msgStatus = "!Invalid counts";
	if (!isset($_POST["sessions"]) || !isset($_POST["attendance"])) return false;
	$sessions = $_POST["sessions"];
	$attendance = $_POST["attendance"];

	if (!is_numeric($sessions) || !is_numeric($attendance)) return false;
	if (($sessions > 24) || ($attendance > 2400)) return false;

	return true;

}

function add_log(&$db, &$state) {

	$sql = "INSERT INTO ".$db->prefix."b10_eventlog
			(event_idref, person_idref, account_idref, session_count, attendance, logdate, comments)
			VALUES (".$state->event_id.", ".$state->person_id.", ".$state->account_id.", ".
			$_POST["sessions"].", ".$_POST["attendance"].", :logdate, :comments);";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':logdate', $state->logdate, db_connect::PARAM_DATE);
	$stmt->bindValue(':comments',input_edit("comments"),PDO::PARAM_STR);
	$stmt->execute();

	$state->msgStatus = "-"; //tell server_call to reset page
}

function update_log(&$db, &$state) {

	$sql = "UPDATE ".$db->prefix."b10_eventlog
			SET session_count=".$_POST["sessions"].", attendance=".$_POST["attendance"].",
			comments=:comments
			WHERE eventlog_id=".$_POST["recID"].";";
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':comments',input_edit("comments"),PDO::PARAM_STR);
	$stmt->execute();

	$state->msgStatus = "-"; //tell server_call to reset page
}

function delete_log(&$db, &$state) {

	$sql = "DELETE FROM ".$db->prefix."b10_eventlog
			WHERE eventlog_id=".$_POST["recID"].";";
	$db->exec($sql);

	$state->msgStatus = "-"; //tell server_call to reset page
}

function new_info(&$db, &$state) {

	if (!isset($_POST["recID"])) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST",true);
	}
	if (!audit_counts($db, $state)) return false;
	$recID = $_POST["recID"];

	if ($recID == 0) { //adding
		add_log($db, $state);
		return true;
	}

	log_list($db, $state);
	if (!array_key_exists(strval($recID), $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid recID ".$recID,true);
	}
	if ($state->records[$recID]["event_id"] != $_POST["event"]) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid record ".$recID,true);
	}

	if ($_POST["sessions"] == 0) {
		delete_log($db, $state);
	} else {
		update_log($db, $state);
	}
	return true;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->title_singular = $_SESSION["_EXTENSION"]["title_singular"];
	$_STATE->title_plural = $_SESSION["_EXTENSION"]["title_plural"];
	setup($_DB, $_STATE);
//	$_STATE->msgGreet = "Enter the ".$_STATE->title_singular." data";
	$_STATE->msgGreet = "Add or change info: click on the lefthand column";
	$_STATE->EC_status = ""; //no line selected
	STATE_new_status($_STATE, FILL_CELLS);
	break 2;
case FILL_CELLS: //fill cells (if edit, starts with Sessions)
	if (isset($_GET["getdesc"])) { //asking for the description of a cell
		cell_desc($_DB, $_STATE);;
		break 2;
	}
	if (isset($_GET["reset"])) {
		log_list($_DB, $_STATE);
		$_STATE->msgGreet = "Add or change info: click on the lefthand column";
		$_STATE->EC_status = ""; //no line selected
		break 2;
	}
	switch ($_GET["cell"]) {
	case "EV": //event
		event_send($_DB, $_STATE);
		$_STATE->EC_status = "a"; //add line selected
		break 1;
	case "SN":	//Info input starting with sessions
		if ($_GET["selectedOption"] != 0)
			event_select($_DB, $_STATE);
		input_send($_DB, $_STATE);
		STATE_new_status($_STATE, STATE::CHANGE);
		break 1;
	default:
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid cell=".$_GET["cell"]);
	}
	break 2;
case STATE::CHANGE:
	$_STATE->EC_status = ""; //no line selected
	if (isset($_GET["reset"])) {
		$_STATE = STATE_get(NULL,$_STATE->thread); //go back to log_list
		break 1;
	}
	new_info($_DB, $_STATE);
	echo $_STATE->msgStatus;
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} }
//EX_pageStart must be here to intercept any server_call:
EX_pageStart(); //standard HTML page start stuff - insert scripts here

echo "<script type='text/javascript' src='/scripts/call_server.js'></script>\n";
echo "<script type='text/javascript' src='/scripts/eventlog.js'></script>\n";

EX_pageHead(); //standard page headings - after any scripts
?>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<?php
//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case STATE::SELECT:
	break;
default: //list the hours and allow new entry:
?>
<div id="divPopopen_ID" style="visibility:hidden;position:fixed;right:10px;top:50px;background-color:lightyellow;border:1px solid black;padding:10px;">
  Enter comments:<br>
  <textarea name="txtComments" id="txtComments_ID" rows="2" cols="50"></textarea><br>
  <input type="button" onclick="save_comments(true)" value="OK">
  <input type="button" onclick="save_comments(false)" value="cancel">
</div>

<table align="center" id="tblLog" cellpadding="4" border="2">
  <tr>
    <th width='100'>&nbsp;</th>
    <th width='140'><?php echo $_STATE->title_singular; ?></th>
    <th width='30'>Sessions</th>
    <th width='30'>Attendance</th>
    <th width='140'>Comments</th>
  </tr>
  <tr id="add">
    <td id="BN_0" data-recid="0" onclick="init_cell(this,'EV',0)"
      title="Click to add new <?php echo $_STATE->title_singular; ?> counts">
      <img src="/images/add.png"></td>
    <td id="EV_0" data-recid="0"></td>
	<td id='SN_0' data-recid='0'></td>
	<td id='AD_0' data-recid='0'></td>
    <td id="CM_0" data-recid="0"></td>
  </tr>
<?php
$counter = 1;
$totals = array(0,0); //totals: sessions, attendance
reset($_STATE->records);
foreach ($_STATE->records AS $ID=>$record) {
	echo "  <tr>\n";
	echo "    <td id='BN_".$counter."' data-recid='".$record["ID"]."' onclick=\"init_cell(this,'SN',".$counter.")\" class=seq>";
	echo $counter."</td>\n";
	echo "    <td id='EV_".$counter."' data-recid='".$record["event_id"]."'>".$record["event"]."</td>\n";
	echo "    <td id='SN_".$counter."' data-recid='".$record["ID"]."' class='number'>".$record["session_count"]."</td>\n";
	echo "    <td id='AD_".$counter."' data-recid='".$record["ID"]."' class='number'>".$record["attendance"]."</td>\n";
	echo "    <td id='CM_".$counter."' data-recid='".$record["ID"]."'>".$record["comments"]."</td>\n";
	echo "  </tr>\n";
	++$counter;
	$totals[0] += $record["session_count"];
	$totals[1] += $record["attendance"];
}
?>
  <tr>
    <td></td>
    <td>Totals:</td><td class='number'><?php echo $totals[0]; ?></td>
    <td class='number'><?php echo $totals[1]; ?></td>
    <td></td>
  </tr>
</table>

<?php if ($_SESSION["_SITE_CONF"]["RUNLEVEL"] == 1) {
	echo "<textarea id='msgDebug' cols='100' rows='20'></textarea>\n";
	} ?>
<?php //end select ($_STATE->status) ----END STATE: EXITING FROM PROCESS----
}

EX_pageEnd(); //standard end of page stuff
?>


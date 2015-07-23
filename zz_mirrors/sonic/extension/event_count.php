<?php //copyright 2014 C.D.Price

define ('STATE_RESUME', STATE::INIT +1);
define ('SHEET_DISP', STATE::SELECT + 3);

define ('ACCOUNT_DISP', STATE::SELECT);
define ('ACCOUNT_PICK', STATE::SELECTED);
define ('EVENT_DISP', STATE::SELECT + 2);
define ('EVENT_PICK', STATE::SELECTED + 1);
define ('DATE_DISP', STATE::SELECT + 3);
define ('DATE_PICK', STATE::SELECT + 4);
define ('COMMENTS_DISP', STATE::SELECT + 5);
define ('SESSIONS_DISP', STATE::SELECT + 6);
define ('BUTTON_DISP', STATE::SELECT + 7);

function setup() {
	global $_DB, $_STATE;

	$sql = "SELECT b00.logdate, a21.account_id, a21.name, a21.description
			FROM ".$_DB->prefix."b00_timelog AS b00
			INNER JOIN ".$_DB->prefix."a21_account AS a21 ON a21.account_id = b00.account_idref
			WHERE b00.timelog_id = ".$_GET["recid"].";";
	$stmt = $_DB->query($sql);
	$row = $stmt->fetchObject();
	$_STATE->heading .= "<br>for ".$row->name.": ".$row->description;
	$_STATE->heading .= "<br>on ".$row->logdate;
	$_STATE->logdate = $row->logdate;
	$_STATE->account_id = $row->account_id;
	$stmt->closeCursor();
}

function log_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT b10.eventlog_id, b10.logdate, b10.session_count, b10.attendance, b10.comments,
			a30.event_id, a30.name AS event, a30.description AS event_desc, a30.inactive_asof AS event_inactive_asof,
			a21.account_id, a21.name AS account, a21.description AS account_desc, a21.inactive_asof AS account_inactive_asof
			FROM ".$_DB->prefix."b10_eventlog AS b10
			JOIN ".$_DB->prefix."a30_event AS a30 ON a30.event_id = b10.event_idref
			JOIN ".$_DB->prefix."a10_project AS a10 ON a10.project_id = a30.project_idref
			JOIN ".$_DB->prefix."a00_organization AS a00 ON a00.organization_id = a10.organization_idref
			JOIN ".$_DB->prefix."a21_account AS a21 ON a21.account_id = b10.account_idref
			WHERE (b10.person_idref=".$state->person_id.") AND (project_id=".$state->project_id.")
			AND (logdate = :logdate)
			ORDER BY logdate, event_id, account_id;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':logdate', $state->logdate, db_connect::PARAM_DATE);
	$stmt->execute();
	$row_count = 0;
	while ($row = $stmt->fetchObject()) {
		$record = array(
			"row" =>		++$row_count, //1 rel - 0 indicates add row
			"ID" =>			$row->eventlog_id,
			"event" =>		substr($row->event.": ".$row->event_desc,0,25),
			"event_id" =>	$row->event_id,
			"logdate" =>	new DateTime($row->logdate),
			"session_count" => $row->session_count,
			"attendance" =>	$row->attendance,
			"comments" =>	substr($row->comments,0,25),
		);
		foreach (array("event") as $name) {
			$item = $name."_inactive_asof";
			if (!is_null($row->{$item})) {
				$inact = new DateTime($row->{$item});
				if ($inact <= $state->to_date) {
					$record[$name] .= "<br>inactive as of (".$row->{$item}.")";
				}
			}
		}
		$state->records[strval($row->eventlog_id)] = $record;
	}
	$stmt->closeCursor();
}

function cell_desc() {
	global $_DB;

	$HTML = "alert(myCell.title);\n";
	$field = "description";
	switch ($_GET["getdesc"]) {
	case "EV":
		$table = $_DB->prefix."a30_event";
		$id = "event_id";
		break;
	case "CM":
		$HTML = "got_comments();\n";
		$field = "comments";
		$table = $_DB->prefix."b10_eventlog";
		$id = "eventlog_id";
		break;
	default:
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid cell ID ".$_GET["getdesc"], true);
	}
	$key = $_GET["ID"];
	$sql = "SELECT ".$field." FROM ".$table." WHERE ".$id."=:key;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(":key", $key, PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchObject();
	$HTML = "@myCell.title = '".$row->{$field}."';\n".$HTML;
	echo $HTML;
}

function event_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT * FROM ".$_DB->prefix."a30_event
			WHERE project_idref=".$state->project_id."
			ORDER BY name;";
	$stmt = $_DB->query($sql);
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

function event_send(&$state, &$HTML) {

	event_list($state);

	$HTML .= "//Events...\n";
	if (count($state->records) == 1) {
		reset($state->records);
		$solo = each($state->records); //get first available "key","value" pair
		$state->event_id = intval($solo["key"]); //event_select wants to see this

	} else {
    	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Select the ".$state->title_singular."';\n";
		$HTML .= "fill = \"<select name='selEvent' id='selEvent' size='1' onchange='proceed(this.parentNode,this.options[this.selectedIndex].value)'>\";\n";
		foreach($state->records as $value => $name) {
			$title = $name[1];
			$opacity = "1.0";
			if ($title != "") {
				$date = explode("-", $title);
				$date[1] -= 1; //month is 0 rel in JS
				$title = " title='inactive as of ".$title."'";
				$opacity = "0.5";
			}
			$HTML .= "fill += \"<option ".$title." value='".$value."' style='opacity:".$opacity."'>".$name[0]."\";\n";
		}
		$HTML .= "fill += \"</select>\";\n";
		$HTML .= "cell = document.getElementById('EV_0');\n";
		$HTML .= "cell.innerHTML = fill;\n";
		$HTML .= "document.getElementById('selEvent').selectedIndex=-1;\n";
	}

	return count($state->records);
}

function event_select(&$state, &$HTML, $rec=-1) {

	if ($rec < 0) { //checking returned
		if (!isset($_GET["row"])) return;
		$rec = strval($_GET["row"]);
	}

	event_list($state); //restore the record list
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid event id ".$rec,true);
	}
	$record = $state->records[$rec];
	if ($record[1] != "") {
		$inactive = new DateTime($record[1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
		$record[0] .= "<br>(inactive as of ".$record[1].")";
		}
	}
	$state->event_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('EV_0');\n";
	$HTML .= "cell.innerHTML = '".$record[0]."';\n";
}

function comments_send(&$state, &$HTML) {

	$HTML .= "//Comments...\n";
	$HTML .= "cell = document.getElementById('CM_".$state->row."');\n";
	$HTML .= "cell.onclick = new Function('show_comments(this)');\n";
	$HTML .= "cell.title = '';\n";
	$HTML .= "cell.innerHTML = '...click here...';\n";
}

function input_send(&$state, &$HTML) {

	if ($state->process == "a") {
		$sessions = 0;
		$attendance = 0;
	} else {
		log_list($state);
		foreach ($state->records as $recID=>$record) {
			if ($record["row"] == $state->row) break;
		}
		$sessions = $record["session_count"];
		$attendance = $record["attendance"];
	}

	$HTML .= "//Sessions...\n";
	$HTML .= "fill = \"<input type='text' name='txtSessions' id='txtSessions_ID' size='3'";
	$HTML .= " maxlength='3' class='number' onblur='audit_count(this,4)' value='".$sessions."'>\";\n";
	$HTML .= "document.getElementById('SN_".$state->row."').innerHTML = fill;\n";

	$HTML .= "//Attendance...\n";
	$HTML .= "cell = document.getElementById('AD_".$state->row."');\n";
	$HTML .= "fill = \"<input type='text' name='txtAttendance' id='txtAttendance_ID' size='5'";
	$HTML .= " maxlength='5' class='number' onblur='audit_count(this,99)' value='".$attendance."'>\";\n";
	$HTML .= "cell = document.getElementById('AD_".$state->row."').innerHTML = fill;\n";

}

function button_send(&$state, &$HTML) {
	$HTML .= "//Buttons...\n";
	$HTML .= "cellID = 'BN_".$state->row."';\n";
	$HTML .= "cell = document.getElementById(cellID);\n";
	$HTML .= "cell.title = '';\n";
 	$HTML .= "fill = 'Enter the ".$state->title_singular." info";
	if ($state->row != 0) $HTML .= " (Sessions = 0 deletes)";
	$HTML .= "';\n";
   	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = fill;\n";
	$HTML .= "fill = \"<button type='button' onclick='new_info(";
	if ($state->row == 0) {
		$HTML .= "0)'>Submit the ".$state->title_singular." info</button>";
	} else {
		$HTML .= $state->row.")'>Submit the changes</button>";
	}
	$HTML .= "<br><button type='button' name='btnReset' onclick='Reset()'>Cancel</button>";
	$HTML .= "\";\n";
	$HTML .= "cell.innerHTML = fill;\n";
	if ($state->process == "u")
		//this guy has to be last because it doesn't return to allow more stuff to be sent:
		$HTML .= "get_desc(document.getElementById('CM_".$state->row."'));\n";
}

function audit_counts(&$state) {

	$state->msgStatus = "!Invalid counts";
	if (!isset($_POST["sessions"]) || !isset($_POST["attendance"])) return false;
	$sessions = $_POST["sessions"];
	$attendance = $_POST["attendance"];

	if (!is_numeric($sessions) || !is_numeric($attendance)) return false;
	if (($sessions > 24) || ($attendance > 2400)) return false;

	return true;

}

function add_log(&$state) {
	global $_DB;

	$sql = "INSERT INTO ".$_DB->prefix."b10_eventlog
			(event_idref, person_idref, account_idref, session_count, attendance, logdate, comments)
			VALUES (".$state->event_id.", ".$state->person_id.", ".$state->account_id.", ".
			$_POST["sessions"].", ".$_POST["attendance"].", :logdate, :comments);";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':logdate', $state->logdate, db_connect::PARAM_DATE);
	$stmt->bindValue(':comments',input_edit("comments"),PDO::PARAM_STR);
	$stmt->execute();

	$state->msgStatus = "-"; //tell server_call to reset page
}

function update_log(&$state) {
	global $_DB;

	$sql = "UPDATE ".$_DB->prefix."b10_eventlog
			SET session_count=".$_POST["sessions"].", attendance=".$_POST["attendance"].",
			comments=:comments
			WHERE eventlog_id=".$state->recID.";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':comments',input_edit("comments"),PDO::PARAM_STR);
	$stmt->execute();

	$state->msgStatus = "-"; //tell server_call to reset page
}

function delete_log(&$state) {
	global $_DB;

	$sql = "DELETE FROM ".$_DB->prefix."b10_eventlog
			WHERE eventlog_id=".$state->recID.";";
	$_DB->exec($sql);

	$state->msgStatus = "-"; //tell server_call to reset page
}

function new_info(&$state) {
	global $_DB;

	$state->recID = 0;
	if ($state->process == "u") {
		log_list($state);
		foreach ($state->records as $recID=>$record) {
			$state->recID = $recID;
			if ($record["row"] == $state->row) break;
		}
		if ($state->recID == 0)
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST",true);
	}

	if (!audit_counts($state)) return false;

	if (substr($_POST["comments"],0,1) == "\n") $_POST["comments"] = "---";
	if ($state->recID == 0) { //adding
		add_log($state);
		return true;
	}
	if ($state->records[$state->recID]["event_id"] != $_POST["event"]) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid record ".$recID,true);
	}
	if ($_POST["sessions"] == 0) {
		delete_log($state);
	} else {
		update_log($state);
	}
	return true;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->title_singular = $_SESSION["_EXTENSION"]["title_singular"];
	$_STATE->title_plural = $_SESSION["_EXTENSION"]["title_plural"];
	setup();
	$_STATE->status = STATE_RESUME;
	$_STATE->replace();
//	break 1; //re_switch
case STATE_RESUME:
	log_list($_STATE);
	$_STATE->msgGreet = "Add or change info: click on the lefthand column";
	$_STATE->scion_start("EXT_EVENTS"); //create the child state stack
	$_STATE->status = SHEET_DISP;
	break 2;
case SHEET_DISP: //fill cells (if edit, starts with Sessions)
	if (isset($_GET["reset"])) {
		$_STATE = $_STATE->goback(1); //go back to log_list
		break 1;
	}
	if (isset($_GET["getdesc"])) { //asking for the description of a cell
		cell_desc();;
		break 2;
	}
	if (!(isset($_GET["row"]) || isset($_POST["row"])))
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): GET/POST row not supplied");

	$SCION = $_STATE->scion_pull(); //use the child thread
	$HTML = "@"; //tell server_call to do an eval
	while (1==1) { switch ($SCION->status) {
	case STATE::INIT:
		$SCION->row = $_GET["row"]; //working on this displayed row
		if ($SCION->row == 0) {
			$SCION->process = "a"; //adding
			$SCION->status = EVENT_DISP;
		} else {
			$SCION->process = "u"; //updating
			$SCION->status = SESSIONS_DISP;
		}
		$HTML .= "document.getElementById('BN_".$SCION->row."')";
		$HTML .= ".innerHTML = \"<button type='button' name='btnReset' onclick='Reset()'>Cancel</button>\";\n";
		break 1; //go back thru switch
	case EVENT_DISP:
		if (event_send($SCION, $HTML) == 1) {
			event_select($SCION, $HTML, $SCION->event_id);
			$SCION->status = COMMENTS_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = EVENT_PICK;
		echo $HTML;
		break 2;
	case EVENT_PICK:
		event_select($SCION, $HTML);
		$SCION->status = COMMENTS_DISP;
//		break 1; //no need to break, just fall through
	case COMMENTS_DISP:
		comments_send($SCION, $HTML);
		$SCION->status = SESSIONS_DISP;
//		break 1; //no need to break, just fall through
	case SESSIONS_DISP:	//Info input starting with sessions
		input_send($SCION, $HTML);
		$SCION->status = BUTTON_DISP;
//		break 1;
	case BUTTON_DISP:
		button_send($SCION, $HTML);
		echo $HTML;
		$SCION->status = STATE::CHANGE;
		break 2;
	case STATE::CHANGE:
		if (isset($_GET["getdesc"])) { //asking for the description of a cell
			cell_desc();;
			break 2;
		}
		if (isset($_GET["reset"])) {
			$_STATE->goback(1); //go back to log_list
			break 3;
		}
		new_info($SCION);
		echo $SCION->msgStatus;
		break 2;
	default:
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): error");
	} } //while & switch
	$SCION->push();

	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid state=".$_STATE->status);
} } //while & switch

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
    <td id="BN_0" data-recid="0" onclick="begin(this)"
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
	$row = $record["row"];
	echo "  <tr>\n";
	echo "    <td id='BN_".$row."' data-recid='".$row."' onclick=\"begin(this)\" class=seq>";
	echo $counter."</td>\n";
	echo "    <td id='EV_".$row."' data-recid='".$record["event_id"]."'>".$record["event"]."</td>\n";
	echo "    <td id='SN_".$row."' data-recid='".$record["ID"]."' class='number'>".$record["session_count"]."</td>\n";
	echo "    <td id='AD_".$row."' data-recid='".$record["ID"]."' class='number'>".$record["attendance"]."</td>\n";
	echo "    <td id='CM_".$row."' data-recid='".$record["ID"]."'>".$record["comments"]."</td>\n";
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

<?php //end select ($_STATE->status) ----END STATE: EXITING FROM PROCESS----
}

EX_pageEnd(); //standard end of page stuff
?>


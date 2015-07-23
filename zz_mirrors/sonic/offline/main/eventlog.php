<?php //copyright 2014-2015 C.D.Price

require_once "field_edit.php";
define ('SELECT_PROJECT', STATE::SELECT + 1);
define ('SELECTED_PROJECT', STATE::SELECTED + 1);
define ('SELECT_SPECS', STATE::SELECT + 2);
define ('SELECTED_SPECS', STATE::SELECTED + 2);
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

define ('EVENT_HEAD', "Class");

$version = "v1.0"; //goes with the downloaded logs file for client verification

function set_state(&$dates) {
	global $_STATE;

	$_STATE->from_date = clone($dates->from);
	$_STATE->to_date = clone($dates->to);

	switch ($dates->checked) {
	case "w":
		$_STATE->heading .= "<br>for the week of ".$_STATE->from_date->format('Y-m-d').
							" to ".$_STATE->to_date->format('Y-m-d');
		break;
	case "m":
		$_STATE->heading .= "<br>for the month of ".$_STATE->from_date->format("M-Y");
		break;
	default:
		$_STATE->heading .= "<br>for dates from ".$_STATE->from_date->format('Y-m-d').
							" to ".$_STATE->to_date->format('Y-m-d');
	}
	return true;
}

function log_put() {
	global $_DB, $_STATE, $_PERMITS;
	global $version;

	$sql = "SELECT name FROM ".$_DB->prefix."a00_organization
			WHERE organization_id=".$_SESSION["organization_id"].";";
	$orgname = $_DB->query($sql)->fetchObject()->name;
	$sql = "SELECT name FROM ".$_DB->prefix."a10_project
			WHERE project_id=".$_STATE->project_id.";";
	$row = $_DB->query($sql)->fetchObject();
	$projname = $row->name;
	$from = $_STATE->from_date->format('Y-m-d');
	$to = $_STATE->to_date->format('Y-m-d');

	$sql = "";
	if (!$_PERMITS->can_pass("project_logs")) $sql = "(b10.person_idref=".$_SESSION["person_id"].") AND ";
	$sql = "SELECT b10.eventlog_id, b10.logdate, b10.session_count, b10.attendance, b10.comments,
			a30.event_id, a30.name AS event, a30.description AS event_desc,
			a21.account_id, a21.name AS account, a21.description AS account_desc
			FROM ".$_DB->prefix."b10_eventlog AS b10
			JOIN ".$_DB->prefix."a30_event AS a30 ON a30.event_id = b10.event_idref
			JOIN ".$_DB->prefix."a21_account AS a21 ON a21.account_id = b10.account_idref
			WHERE ".$sql."(a30.project_idref=".$_STATE->project_id.")
			AND (logdate BETWEEN :fromdate AND :todate)
			ORDER BY logdate, event_id, account_id;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':fromdate', $from, db_connect::PARAM_DATE);
	$stmt->bindValue(':todate', $to, db_connect::PARAM_DATE);
	$stmt->execute();
	if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
		$_STATE->msgStatus = "No logs were downloaded";
		return;
	}

	$filename = EVENT_HEAD."Log_".$orgname."_".$projname."_".$from."_to_".$to.".csv"; //for file_put...
	require_once "file_put.php";

	$out = fopen('php://output', 'w');

	$outline = array();
	$outline[] = EVENT_HEAD."Log";
	$outline[] = $orgname;
	$outline[] = $projname;
	$outline[] = $from;
	$outline[] = $to;
	$outline[] = $version;
	fputcsv($out, $outline); //ID row
	$outline = array();
	$idoffset = 0;
	$count = 0;
	foreach ($row as $name=>$value) { //headings
		$outline[] = $name;
		$count++;
	}
	fputcsv($out, $outline);

	do {
		fputcsv($out, $row);
	} while ($row = $stmt->fetch(PDO::FETCH_NUM));
	$stmt->closeCursor();
	fclose($out);

	FP_end();
	$_STATE->msgStatus = "Logs successfully downloaded";
}

function log_list(&$state) {
	global $_DB, $_PERMITS;

	$state->records = array();

	$sql = "";
	if (!$_PERMITS->can_pass("project_logs")) $sql = "(b10.person_idref=".$_SESSION["person_id"].") AND ";
	$sql = "SELECT b10.eventlog_id, b10.logdate, b10.session_count, b10.attendance, b10.comments,
			a30.event_id, a30.name AS event, a30.description AS event_desc, a30.inactive_asof AS event_inactive_asof,
			a10.project_id, a10.name AS project, a10.description AS project_desc,
			a21.account_id, a21.name AS account, a21.description AS account_desc, a21.inactive_asof AS account_inactive_asof,
			a00.organization_id
			FROM ".$_DB->prefix."b10_eventlog AS b10
			JOIN ".$_DB->prefix."a30_event AS a30 ON a30.event_id = b10.event_idref
			JOIN ".$_DB->prefix."a10_project AS a10 ON a10.project_id = a30.project_idref
			JOIN ".$_DB->prefix."a00_organization AS a00 ON a00.organization_id = a10.organization_idref
			JOIN ".$_DB->prefix."a21_account AS a21 ON a21.account_id = b10.account_idref
			WHERE ".$sql."(project_id=".$state->project_id.")
			AND (logdate BETWEEN :fromdate AND :todate)
			ORDER BY account_desc, logdate, event_id;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':fromdate', $state->from_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->bindValue(':todate', $state->to_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->execute();
	$row_count = 0;
	while ($row = $stmt->fetchObject()) {
		$record = array(
			"closed" =>		false, //true if logdate prior to close
			"row" =>		++$row_count, //1 rel - 0 indicates add row
			"ID" =>			$row->eventlog_id,
			"account" =>	substr($row->account.": ".$row->account_desc,0,25),
			"account_id" =>	$row->account_id,
			"event" =>		substr($row->event.": ".$row->event_desc,0,25),
			"event_id" =>	$row->event_id,
			"logdate" =>	new DateTime($row->logdate),
			"session_count" => $row->session_count,
			"attendance" =>	$row->attendance,
			"comments" =>	substr($row->comments,0,25),
		);
		if ($record["logdate"] <= $state->close_date) $record["closed"] = true;
		if ($row->account == "*") $record["account"] = "";
		foreach (array("account","event") as $name) {
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

function cell_desc(&$state) {
	global $_DB;

	$HTML = "alert(myCell.title);\n";
	$field = "description";
	switch ($_GET["getdesc"]) {
	case "EV":
		$table = $_DB->prefix."a30_event";
		$id = "event_id";
		break;
	case "AC":
		$table = $_DB->prefix."a21_account";
		$id = "account_id";
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
			if ($inact <= $state->from_date) continue;
			if ($state->to_date >= $inact)
				$element[1] = $row->inactive_asof;
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
		}
		$record[0] .= "<br>(inactive as of ".$record[1].")";
	}
	$state->event_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('EV_0');\n";
	$HTML .= "cell.innerHTML = '".$record[0]."';\n";
}

function account_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT * FROM ".$_DB->prefix."a21_account
			WHERE accounting_idref=".$state->accounting_id." ORDER BY description;";
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
			if ($inact <= $state->from_date) continue;
			if ($state->to_date >= $inact)
				$element[1] = $row->inactive_asof;
		}
		$state->records[strval($row->account_id)] = $element;
	}
	$stmt->closeCursor();
}

function account_send(&$state, &$HTML) {

	account_list($state);

	$HTML .= "//Accounts...\n";
	if (count($state->records) == 1) {
		reset($state->records);
		$solo = each($state->records); //get first available "key","value" pair
		$state->account_id = intval($solo["key"]); //account_select wants to see this

	} else {
    	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Select the ".$state->accounting."';\n";
		$HTML .= "fill = \"<select name='selAccount' id='selAccount' size='1' onchange='proceed(this.parentNode,this.options[this.selectedIndex].value)'>\";\n";
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
		$HTML .= "cell = document.getElementById('AC_0');\n";
		$HTML .= "cell.innerHTML = fill;\n";
		$HTML .= "document.getElementById('selAccount').selectedIndex=-1;\n";
	}

	return count($state->records);
}

function account_select(&$state, &$HTML, $rec=-1) {

	if ($rec < 0) { //checking returned
		if (!isset($_GET["row"])) return;
		$rec = strval($_GET["row"]);
	}

	account_list($state); //restore the record list
//	if ($rec != 0) {
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid accounting id ".$rec,true);
	}
	$record = $state->records[$rec];
	if ($record[1] != "") {
		$inactive = new DateTime($record[1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
		}
		$record[0] .= "<br>(inactive as of ".$record[1].")";
	}
//	}
	$state->account_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('AC_0');\n";
	$HTML .= "cell.innerHTML = '".$record[0]."';\n";
}

function date_send(&$state, &$HTML) {

	$HTML .= "//Date...\n";
	$HTML .= "cell = document.getElementById('DT_".$state->row."');\n";
	if ($state->row == 0) { //0 is add row
		$HTML .= "var date = ['".$state->to_date->format("Y")."','".$state->to_date->format("m")."','".$state->to_date->format("d")."'];\n";
	} else {
		$HTML .= "var date = cell.innerHTML.split('-');\n";
	}
   	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Enter the ".$state->title_singular." date';\n";
	$HTML .= "fill = \"<table><tr><td>yyyy</td><td>mm</td><td>dd</td><td></td></tr><tr>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtYYYY' id='txtYYYY_ID' size='4' maxlength='4'></td>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtMM' id='txtMM_ID' size='2' maxlength='2'></td>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtDD' id='txtDD_ID' size='2' maxlength='2'></td>\";\n";
	$HTML .= "fill += \"<td onclick='audit_date(this.parentNode.parentNode.parentNode);' class=seq>&rArr;</td></tr></table>\";\n"; //this(td).parentNode(tr).parentNode(tbody).parentNode(table)
	$HTML .= "cell.innerHTML = fill;\n";
	$HTML .= "document.getElementById('txtYYYY_ID').value = date[0];\n";
	$HTML .= "document.getElementById('txtMM_ID').value = date[1];\n";
	$HTML .= "document.getElementById('txtDD_ID').value = date[2];\n";
}

function comments_send(&$state, &$HTML) {

	$HTML .= "//Comments...\n";
	$HTML .= "cell = document.getElementById('CM_".$state->row."');\n";
	$HTML .= "cell.onclick = new Function('show_comments(this)');\n";
	$HTML .= "cell.title = '';\n";
	$HTML .= "cell.innerHTML = '...click here...';\n";
}

function input_send(&$state, &$HTML) {

	if ($state->row == 0) { //0 is add row
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
	$HTML .= " maxlength='3' class='number' onblur='return audit_count(this,4)' value='".$sessions."'>\";\n";
	$HTML .= "document.getElementById('SN_".$state->row."').innerHTML = fill;\n";

	$HTML .= "//Attendance...\n";
	$HTML .= "cell = document.getElementById('AD_".$state->row."');\n";
	$HTML .= "fill = \"<input type='text' name='txtAttendance' id='txtAttendance_ID' size='5'";
	$HTML .= " maxlength='5' class='number' onblur='return audit_count(this,99)' value='".$attendance."'>\";\n";
	$HTML .= "cell = document.getElementById('AD_".$state->row."').innerHTML = fill;\n";

}

function button_send(&$state, &$HTML) {
	$HTML .= "//Buttons...\n";
	$HTML .= "cellID = 'BN_".$state->row."';\n";
	$HTML .= "cell = document.getElementById(cellID);\n";
	$HTML .= "cell.title = '';\n";
	$HTML .= "fill = 'Enter the ".$state->title_singular." info (Sessions = 0 deletes)';\n";
   	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = fill;\n";
	//onclick=onmousedown + onmouseup; if audit_count() caused by onblur of numbers issues confirm(),
	//onmouseup will not happen; in that case, mouseDown() will assure new_info() gets executed:
	$HTML .= "fill = \"<button type='button' onmousedown='mouseDown(".$state->row.")' onclick='new_info(".$state->row.")'>";
	if ($state->row == 0) {
		$HTML .= "Submit the ".$state->title_singular." info</button>";
	} else {
		$HTML .= "Submit the changes</button>";
	}
	$HTML .= "<br><button type='button' name='btnReset' onclick='Reset()'>Cancel</button>";
	$HTML .= "\";\n";
	$HTML .= "cell.innerHTML = fill;\n";
	if ($state->row > 0) //0 is add row
		//this guy has to be last because it doesn't return to allow more stuff to be sent:
		$HTML .= "get_desc(document.getElementById('CM_".$state->row."'));\n";
}

function audit_date(&$state, &$logdate) {

	$state->msgStatus = "!Invalid date";
	if (!isset($_POST["date"])) return false;
	$state->msgStatus = "!Invalid date2";
	$pieces =  explode("-",$_POST["date"]);
	if (count($pieces) != 3) return false;
	$state->msgStatus = "!Invalid date3";
	if (!is_numeric($pieces[0]) || !is_numeric($pieces[1]) || !is_numeric($pieces[2])) return false;

	$state->msgStatus = "!Invalid date4";
	if (($logdate = date_create($_POST["date"])) === FALSE) return false;

	if ($logdate < $state->from_date) {
		$state->msgStatus = "!The date must not be before the 'From' date";
		return false;
	}
	if ($logdate > $state->to_date) {
		$state->msgStatus = "!The date must not be after the 'To' date";
		return false;
	}
	if ($logdate <= $state->close_date) {
		$state->msgStatus = "!The date must be after the 'Close' date";
		return false;
	}
	if ($logdate >= $state->inactive_date) {
		$state->msgStatus = "!The date must be before any 'Inactive' date";
		return false;
	}

	$state->msgStatus = "-"; //tell call_server to reset page
	return true;
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
			VALUES (".$state->event_id.", ".$_SESSION["person_id"].", ".$state->account_id.", ".
			$_POST["sessions"].", ".$_POST["attendance"].", :logdate, :comments);";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':logdate', $_POST["date"], db_connect::PARAM_DATE);
	$stmt->bindValue(':comments',COM_input_edit("comments"),PDO::PARAM_STR);
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
	$stmt->bindValue(':comments',COM_input_edit("comments"),PDO::PARAM_STR);
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

	$state->recID = 0;
	if ($state->row > 0) { //0 is add row
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
	$logdate = clone $state->from_date;
	if ($state->recID == 0) { //adding
		if (!audit_date($state, $logdate)) return false;
		add_log($state);
		return;
	}

	if (($state->records[$state->recID]["event_id"] != $_POST["event"]) ||
		($state->records[$state->recID]["account_id"] != $_POST["account"])) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid record ".$recID,true);
	}

	if ($_POST["sessions"] == 0) {
		delete_log($state);
	} else {
		update_log($state);
	}

}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->title_singular = EVENT_HEAD;
	$_STATE->close_date = COM_NOW();
	$_STATE->inactive_date = COM_NOW();
	$_STATE->project_id = 0;
	$_STATE->accounting_id = 0;
	$_STATE->accounting = "";
//	$_STATE->task_id = 0;
	$_STATE->event_id = 0;
	$_STATE->account_id = 0;
	$_STATE->closedCols = 0; //in tabular form => cols prior to close
	require_once "project_select.php";
	$projects = new PROJECT_SELECT($_SESSION["person_id"]);
	$_STATE->project_select = serialize(clone($projects));
	if ($projects->selected) {
		$_STATE->status = SELECTED_PROJECT;
		break 1; //re-switch to SELECTED_PROJECT
	}
	$_STATE->msgGreet = "Select the project";
	$_STATE->status = SELECT_PROJECT;
	break 2;
case SELECT_PROJECT: //select the project
	require_once "project_select.php"; //catches $_GET list refresh
	$projects = unserialize($_STATE->project_select);
	$projects->set_state();
	$_STATE->project_select = serialize($projects);
	$_STATE->status = SELECTED_PROJECT; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_PROJECT:
	require_once "project_select.php"; //in case of goback
	$projects = unserialize($_STATE->project_select);
	$_STATE->project_name = $projects->selected_name();
	require_once "date_select.php";
	$dates = new DATE_SELECT("wmp"); //show within week(w), month(m), period(p)
	$_STATE->date_select = serialize(clone($dates));
	require_once "calendar.php";
	$calendar = new CALENDAR(2, "FT"); //2 pages
	$_STATE->calendar = serialize(clone($calendar));
	$_STATE->msgGreet = $_STATE->project_name."<br>Select the date range";
	$_STATE->status = SELECT_SPECS;
	break 2;
case SELECT_SPECS: //set the from and to dates
	require_once "calendar.php"; //catches $_GET refresh
	require_once "date_select.php";
	$dates = unserialize($_STATE->date_select);
	if (!$dates->POST()) {
		$calendar = unserialize($_STATE->calendar);
		$_STATE->msgGreet = $_STATE->project_name."<br>Select the date range";
		break 2;
	}
	set_state($dates);
	$_STATE->inactive_date = clone $_STATE->to_date;
	$_STATE->inactive_date->add(new DateInterval('P1D'));
	$_STATE->status = SELECTED_SPECS; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_SPECS:
	log_list($_STATE);
	$_STATE->msgGreet = "Add or change info: click on the lefthand column";
	$_STATE->scion_start("SHEET"); //create the child state stack
	$_STATE->status = SHEET_DISP;
	break 2;
case SHEET_DISP: //fill cells (if edit, starts with Hours)
	if (isset($_GET["reset"])) {
		$_STATE = $_STATE->goback(1); //go back to log_list
		break 1;
	}
	if (isset($_GET["getdesc"])) { //asking for the description of a cell
		cell_desc($_STATE);;
		break 2;
	}
	if (isset($_POST["btnPut"])) { //asking for a download
		log_put();
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
			$SCION->status = ACCOUNT_DISP;
		} else {
			$SCION->status = SESSIONS_DISP;
		}
		$HTML .= "document.getElementById('BN_".$SCION->row."')";
		$HTML .= ".innerHTML = \"<button type='button' name='btnReset' onclick='Reset()'>Cancel</button>\";\n";
		break 1; //go back thru switch
	case ACCOUNT_DISP:
		if (account_send($SCION, $HTML) == 1) {
			account_select( $SCION, $HTML, $SCION->account_id);
			$SCION->status = EVENT_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = ACCOUNT_PICK;
		echo $HTML;
		break 2;
	case ACCOUNT_PICK:
		account_select($SCION, $HTML);
		$SCION->status = EVENT_DISP;
//		break 1;
	case EVENT_DISP:
		if (event_send($SCION, $HTML) == 1) {
			event_select($SCION, $HTML, $SCION->event_id);
			$SCION->status = DATE_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = EVENT_PICK;
		echo $HTML;
		break 2;
	case EVENT_PICK:
		event_select($SCION, $HTML);
		$SCION->status = DATE_DISP;
//		break 1; //no need to break, just fall through
	case DATE_DISP:
		date_send($SCION, $HTML);
		$SCION->status = DATE_PICK;
		echo $HTML;
		break 2;
	case DATE_PICK:
		if ($SCION->row > 0) { //0 is add row
			$SCION->status = SESSIONS_DISP;
			break 1; //go back around to skip comments
		}
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
			cell_desc($SCION);;
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
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): Invalid state=".$_STATE->status);
} } //while & switch

$redirect = $_SESSION["_SITE_CONF"]["_REDIRECT"];

//EX_pageStart must be here to intercept any server_call:
EX_pageStart(); //standard HTML page start stuff - insert scripts here

echo "<script type='text/javascript' src='".$redirect."/scripts/call_server.js'></script>\n";

if ($_STATE->status == SELECT_SPECS) {
	echo "<script type='text/javascript' src='".$redirect."/scripts/calendar.js'></script>\n";

} else if ($_STATE->status > SELECT_SPECS) {
//note: the "intval" removes the leading zero which JS interprets as octal number
//note: the "-1" needed because JS expects month rel to 0 (but not day); the "-1" does the same as "intval")
	$JSclose=$_STATE->close_date->format("Y").",".($_STATE->close_date->format("m")-1).
				",".intval($_STATE->close_date->format("d"));
	$JSfrom=$_STATE->from_date->format("Y").",".($_STATE->from_date->format("m")-1).
				",".intval($_STATE->from_date->format("d"));
	$JSto=$_STATE->to_date->format("Y").",".($_STATE->to_date->format("m")-1).
				",".intval($_STATE->to_date->format("d"));
	$JSinactive=$_STATE->inactive_date->format("Y").",".($_STATE->inactive_date->format("m")-1).
				",".intval($_STATE->inactive_date->format("d"));
?>
<script language="JavaScript">
var closeDate = new Date(<?php echo $JSclose; ?>); //JS expects month rel to 0
var fromDate = new Date(<?php echo $JSfrom; ?>);
var toDate = new Date(<?php echo $JSto; ?>);
var inactiveDate = new Date(<?php echo $JSinactive; ?>);
</script>
<?php
echo "<script type='text/javascript' src='".$redirect."/scripts/eventlog.js'></script>\n";
}

EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case SELECT_PROJECT:

	echo $projects->set_list();

	break; //end SELECT_PROJECT status ----END STATE: EXITING FROM PROCESS----
case SELECT_SPECS:
?>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<table cellpadding="3" border="0" align="center">
  <tr><td colspan="3">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>
<?php
	echo $dates->HTML();
?>
  <tr><td>&nbsp</td><td colspan="3">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>

  <tr><td colspan='3'>
<?php
	echo $calendar->create();
?>
  </td></tr>

  <tr>
    <td>&nbsp</td>
    <td colspan="2" style="text-align:left">
      <button name="btnDates" type="button" value="<?php echo $_SESSION["person_id"]; ?>" onclick="this.form.submit()">Continue</button>
    </td>
  </tr>
</table>
</form>
<div id="msgStatus_ID"><?php echo $_STATE->msgStatus ?></div>

<?php //end SELECT_SPECS status ----END STATE: EXITING FROM PROCESS----
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
    <th width='140'><?php echo $_STATE->accounting; ?></th>
    <th width='140'><?php echo $_STATE->title_singular; ?></th>
    <th width='74'>Date</th>
    <th width='30'>Sessions</th>
    <th width='30'>Attendance</th>
    <th width='140'>Comments</th>
  </tr>
  <tr id="add">
    <td id="BN_0" data-recid="0" onclick="begin(this)"
      title="Click to add new <?php echo $_STATE->title_singular; ?> counts">
      <img src="<?php echo $redirect; ?>/images/add.png"></td>
    <td id="AC_0" data-recid="0"></td>
    <td id="EV_0" data-recid="0"></td>
    <td id='DT_0' data-recid='0' class='date'></td>
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
	$open = "    <td id='BN_".$row."' data-recid='".$row."' onclick=\"begin(this)\" class=seq";
	if ($record["closed"]) {
		echo "  <tr style='background-color:pink'>\n";
		if (!$_PERMITS->can_pass("edit_logs")) {
			echo "    <td title='closed to new input'";
		} else {
			echo $open." title='PROJECT IS CLOSED; edit with care!'";
		}
	} else {
		echo "  <tr>\n";
		echo $open;
	}
	echo ">".$row."</td>\n";
	echo "    <td id='AC_".$row."' data-recid='".$record["account_id"]."'>".$record["account"]."</td>\n";
	echo "    <td id='EV_".$row."' data-recid='".$record["event_id"]."'>".$record["event"]."</td>\n";
	echo "    <td id='DT_".$row."' class='date'>".$record["logdate"]->format("Y-m-d")."</td>\n";
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
    <td colspan="3"></td>
    <td>Totals:</td><td class='number'><?php echo $totals[0]; ?></td>
    <td class='number'><?php echo $totals[1]; ?></td>
    <td></td>
  </tr>
</table>

<?php
if ($_PERMITS->can_pass("project_logs")) { ?>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<br>You can
<button name="btnPut" type="submit" value="<?php echo $_SESSION["person_id"]; ?>" title="click here to download">Download</button>
this data for import into the timesheet template<br>(check your browser preferences for where the downloaded file will go)
</form>
<?php
} ?>

<div id="msgStatus_ID" class="status"><?php echo $_STATE->msgStatus ?></div>
<?php //end select ($_STATE->status) ----END STATE: EXITING FROM PROCESS----
}

EX_pageEnd(); //standard end of page stuff
?>


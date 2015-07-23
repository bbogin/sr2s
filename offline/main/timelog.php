<?php //copyright 2010,2014-2015 C.D.Price

require_once "field_edit.php";
define ('SELECT_PERSON', STATE::SELECT);
define ('SELECTED_PERSON', STATE::SELECTED);
define ('SELECT_PROJECT', STATE::SELECT + 1);
define ('SELECTED_PROJECT', STATE::SELECTED + 1);
define ('SELECT_SPECS', STATE::SELECT + 2);
define ('SELECTED_SPECS', STATE::SELECTED + 2);
define ('SHEET_DISP', STATE::SELECT + 3);

define ('TASK_DISP', STATE::SELECT);
define ('TASK_PICK', STATE::SELECTED);
define ('SUBTASK_DISP', STATE::SELECT + 1);
define ('SUBTASK_PICK', STATE::SELECTED + 1);
define ('ACCOUNT_DISP', STATE::SELECT + 2);
define ('ACCOUNT_PICK', STATE::SELECTED + 2);
define ('ACTIVITY_DISP', STATE::SELECT + 3);
define ('ACTIVITY_PICK', STATE::SELECTED + 3);
define ('HOURS_DISP', STATE::SELECT + 4);
define ('DATE_DISP', STATE::SELECT + 5);
define ('BUTTON_DISP', STATE::SELECT + 6);

$version = "v1.0"; //goes with the downloaded timesheet file for client verification

function set_state(&$dates) {
	global $_STATE;

	$_STATE->from_date = clone($dates->from);
	$_STATE->to_date = clone($dates->to);

	if ($_POST["radStyle"] == "s") {
		$_STATE->columns = -1;
	} else {
		$_STATE->columns = date_diff($_STATE->from_date, $_STATE->to_date)->days + 1;
		if ($_STATE->columns > 60) {
			$_STATE->from_date = clone $_STATE->to_date;
			$_STATE->from_date->modify("-59 day");
			$_STATE->msgStatus .= "Max 60 days allowed; From Date modified accordingly";
			$_STATE->columns = 60;
		}
	}
	$_STATE->max_column = abs($_STATE->columns) - 1; //0 rel

	switch ($dates->checked) {
	case "w":
		$_STATE->heading .= ": for the week of ".$_STATE->from_date->format('Y-m-d')." to ".$_STATE->to_date->format('Y-m-d');
		break;
	case "m":
		$_STATE->heading .= "<br>for the month of ".$_STATE->from_date->format("M-Y");
		break;
	default:
		$_STATE->heading .= "<br>for dates from ".$_STATE->from_date->format('Y-m-d')." to ".$_STATE->to_date->format('Y-m-d');
	}
	return true;
}

function set_closedCols() {
	global $_STATE;
	if ($_STATE->from_date > $_STATE->close_date) {
		$_STATE->closedCols = 0;
	} elseif ($_STATE->to_date < $_STATE->close_date) {
		$_STATE->closedCols = $_STATE->columns;
	} else {
		$_STATE->closedCols = date_diff($_STATE->from_date, $_STATE->close_date)->days + 1;
	}
}

function active_rates($project_id) { //does user have an hourly rate set for each day in the period
	global $_DB, $_STATE;

	$rates = array();

	$sql = "SELECT * FROM ".$_DB->prefix."c02_rate
			WHERE person_idref=".$_STATE->person_id."
			AND project_idref=".$project_id."
			ORDER BY effective_asof;";
	$stmt = $_DB->query($sql);
	$one_day= new DateInterval('P1D'); //P=period, 1=number, D=days
	$next = clone $_STATE->from_date; //a DateTime object
	$row["expire_after"] = clone $next;
	$row["expire_after"]->sub($one_day); //force a stmt->fetch 1st time thru
	for ($ndx=0; $next <= $_STATE->to_date; $ndx++) {
		while ($next > $row["expire_after"]) { //find an unexpired one
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$row["effective_asof"] = new DateTime($row["effective_asof"]);
				if (is_null($row["expire_after"])) {
					$row["expire_after"] = clone $_STATE->to_date;
				} else {
					$row["expire_after"] = new DateTime($row["expire_after"]);
				}
			} else {
				$row["effective_asof"] = clone $_STATE->to_date;
				$row["effective_asof"]->add($one_day);
				$row["expire_after"] = clone $row["effective_asof"];
			}
		}
		if ($next < $row["effective_asof"]) {
			$rates[$ndx] = 0;
		} else {
			$rates[$ndx] = $row["rate_id"];
		}
		$next->add($one_day);
	}

	return $rates;
}

function total_hours(&$state) { //for all selected projects (won't work in list mode)
	global $_DB;

	if ($state->columns < 0) return; //list style

	$totals = array();
	for ($ndx=0; $ndx<$state->columns; $ndx++) $totals[] = 0;
	$sql = "SELECT logdate, hours, project_id FROM ".$_DB->prefix."v10_logreport
			WHERE (person_id=".$state->person_id.") AND (project_id IN (".
			implode($state->project_ids,",").
			")) AND (logdate BETWEEN :fromdate AND :todate)
			ORDER BY logdate;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':fromdate', $state->from_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->bindValue(':todate', $state->to_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->execute();
	$one_day= new DateInterval('P1D'); //P=period, 1=number, D=days
	$next = clone $state->from_date; //a DateTime object
	$ndx = 0;
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		while (new DateTime($row["logdate"]) > $next) {
			++$ndx;
			$next->add($one_day);
		}
		$totals[$ndx] += $row["hours"];
	}

	$state->totals = $totals;
}

function log_put() {
	global $_DB, $_STATE;
	global $version;

	$sql = "SELECT name, description FROM ".$_DB->prefix."a00_organization
			WHERE organization_id=".$_SESSION["organization_id"].";";
	$row = $_DB->query($sql)->fetchObject();
	$orgname = $row->name;
	$orgdesc = $row->description;

	$from = $_STATE->from_date->format('Y-m-d');
	$to = $_STATE->to_date->format('Y-m-d');

	$sql = "SELECT lastname, firstname FROM ".$_DB->prefix."c00_person
			WHERE person_id=".$_STATE->person_id.";";
	$row = $_DB->query($sql)->fetchObject();
	$lastname = $row->lastname;
	$firstname = $row->firstname;

	$sql = "SELECT * FROM ".$_DB->prefix."v10_logreport
			WHERE (person_id=".$_STATE->person_id.") AND (project_id IN (".
			implode($_STATE->project_ids,",").
			")) AND (logdate BETWEEN :fromdate AND :todate)
			ORDER BY logdate;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':fromdate', $from, db_connect::PARAM_DATE);
	$stmt->bindValue(':todate', $to, db_connect::PARAM_DATE);
	$stmt->execute();
	if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
		$_STATE->msgStatus = "No logs were downloaded";
		return;
	}

	$filename = "timesheet_".$orgname."_".$lastname."_".$firstname."_".$from."_to_".$to.".csv"; //for file_put...
	require_once "file_put.php";

	$out = fopen('php://output', 'w');

	$outline = array();
	$outline[] = "timesheet";
	$outline[] = $orgname;
	$outline[] = $orgdesc;
	$outline[] = $lastname;
	$outline[] = $firstname;
	$outline[] = $from;
	$outline[] = $to;
	$outline[] = $version;
	fputcsv($out, $outline); //ID row
	$outline = array();
	$fields = "";
	foreach ($row as $name=>$value) { //headings
		if (($name == "project_id") || ($name == "extension")) continue; //don't send these fields
		$outline[] = $name;
		$fields .= ",".$name;
	}
	fputcsv($out, $outline);
	$stmt->closeCursor();

	foreach ($_STATE->project_ids as $ID) {
		$sql = "SELECT name, description FROM ".$_DB->prefix."a10_project
				WHERE project_id=".$ID.";";
		$row = $_DB->query($sql)->fetchObject();
		$outline = array();
		$outline[] = "project";
		$outline[] = $row->name;
		$outline[] = $row->description;
		fputcsv($out, $outline); //project row

		$sql = "SELECT ".substr($fields,1)." FROM ".$_DB->prefix."v10_logreport
				WHERE (person_id=".$_STATE->person_id.") AND (project_id=".$ID.")
				AND (logdate BETWEEN :fromdate AND :todate)
				ORDER BY logdate;";
		$stmt = $_DB->prepare($sql);
		$stmt->bindValue(':fromdate', $from, db_connect::PARAM_DATE);
		$stmt->bindValue(':todate', $to, db_connect::PARAM_DATE);
		$stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			fputcsv($out, $row);
		}
		$stmt->closeCursor();
	}

	$outline = array();
	$outline[] = "project";
	$outline[] = "<end>";
	fputcsv($out, $outline); //project row
	fclose($out);

	FP_end();
	$_STATE->msgStatus = "Logs successfully downloaded";
}

function log_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "";
	if ($state->columns > 0) {
		$sql = "activity_id, task_id, subtask_id, account_id, logdate";
	} else {
		$sql = "logdate, activity_id, task_id, subtask_id, account_id";
	}
	$sql = "SELECT * FROM ".$_DB->prefix."v00_timelog
			WHERE (person_id=".$state->person_id.") AND (project_id=".$state->project_id.")
			AND (logdate BETWEEN :fromdate AND :todate)
			ORDER BY ".$sql.";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':fromdate', $state->from_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->bindValue(':todate', $state->to_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->execute();
	if (!($row = $stmt->fetchObject())) {
		$stmt->closeCursor();
		return;
	}
	$row_sav = clone($row);
	$row_sav->logdate = new DateTime('2000-01-01'); //first rec is not a dup
	$row_sav->activity_id = 0; //force an initial row increment in tabular mode
	$row_count = 0; //will start at 1
	do {
		$samerow = (($row_sav->activity_id == $row->activity_id) && ($row_sav->account_id == $row->account_id) &&
					($row_sav->subtask_id == $row->subtask_id) && ($row_sav->task_id == $row->task_id));
		if ($state->columns > 0) {
				if (!$samerow) ++$row_count;
		} else {
			++$row_count; //in list style, every record is a new row
		}
		$row->logdate = new DateTime($row->logdate);
		$record = array(
			"closed" =>		false,		//true if logdate prior to close
			"row" =>		$row_count, //1 rel - 0 indicates add row
			"column" =>		0,			//dates column in tabular style (0 rel)
			"max_column" =>	$state->max_column, //0 rel
			"ID" =>			$row->timelog_id,
			"account" =>	substr($row->account.": ".$row->account_desc,0,25),
			"account_id" =>	$row->account_id,
			"task" =>		substr($row->task.": ".$row->task_desc,0,25),
			"task_id" =>	$row->task_id,
			"subtask" =>	substr($row->subtask.": ".$row->subtask_desc,0,25),
			"subtask_id" =>	$row->subtask_id,
			"extension" =>	$row->extension,
			"activity" =>	substr($row->activity,0,25),
			"activity_id" => $row->activity_id,
			"logdate" =>	$row->logdate,
			"hours" =>		$row->hours
		);

		if ($record["logdate"] <= $state->close_date) $record["closed"] = true;
		if ($state->columns > 0) //only for tabular style
			$record["column"] = date_diff($state->from_date, $record["logdate"])->days;
		if ($row->account == "*") $record["account"] = "";
		if ($row->task == "*") $record["task"] = "";
		if ($row->subtask == "*") $record["subtask"] = "";
		if ($samerow && ($row_sav->logdate == $row->logdate))
			$record["hours"] = -$record["hours"]; //it's a duplicate
		foreach (array("account","task","subtask") as $name) {
			$item = $name."_inactive_asof";
			if (!is_null($row->{$item})) {
				$inact = new DateTime($row->{$item});
				if ($inact <= $state->to_date) {
					$record[$name] .= "<br>inactive as of (".$row->{$item}.")";
					$maxcol = date_diff($state->from_date, $inact)->days - 1; //0 rel
					if ($maxcol < $state->max_column) $record["max_column"] = $maxcol;
				}
			}
		}
		$state->records[strval($row->timelog_id)] = $record;
		$row_sav = clone($row);
	} while ($row = $stmt->fetchObject());
	$stmt->closeCursor();
}

function cell_desc() {
	global $_DB;

	$HTML = "alert(myCell.title);\n";
	switch ($_GET["getdesc"]) {
	case "TK":
		$table = $_DB->prefix."a12_task";
		$id = "task_id";
		break;
	case "ST":
		$table = $_DB->prefix."a14_subtask";
		$id = "subtask_id";
		break;
	case "AC":
		$table = $_DB->prefix."a21_account";
		$id = "account_id";
		break;
	case "AT":
		$HTML = "got_activity();\n";
		$table = $_DB->prefix."b02_activity";
		$id = "activity_id";
		break;
	default:
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid cell ID ".$_GET["getdesc"], true);
	}
	$key = $_GET["ID"];
	$sql = "SELECT description FROM ".$table." WHERE ".$id."=:key;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(":key", $key, PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchObject();
	$HTML = "@myCell.title = '".$row->description."';\n".$HTML;
	echo $HTML;
}

function task_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT * FROM ".$_DB->prefix."a12_task
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
		$state->records[strval($row->task_id)] = $element;
	}
	$stmt->closeCursor();
}

function task_send(&$state, &$HTML) {

	task_list($state);

	$HTML .= "//Tasks...\n";
	if (count($state->records) == 1) {
		reset($state->records);
		$solo = each($state->records);
		$state->task_id = intval($solo["key"]); //task_select wants to see this

	} else {
    	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Select the task';\n";
		$HTML .= "fill = \"<select name='selTask' id='selTask' size='1' onchange='proceed(this.parentNode,this.options[this.selectedIndex].value)'>\";\n";
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
		$HTML .= "cell = document.getElementById('TK_0');\n";
		$HTML .= "cell.innerHTML = fill;\n";
		$HTML .= "document.getElementById('selTask').selectedIndex=-1;\n";
	}

	return count($state->records);
}

function task_select(&$state, &$HTML, $rec=-1) {

	if ($rec < 0) { //checking returned
		if (!isset($_GET["row"])) return;
		$rec = strval($_GET["row"]);
	}

	task_list($state); //restore the record list
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid task id ".$rec,true);
	}
	$record = $state->records[$rec];
	if ($record[1] != "") {
		$inactive = new DateTime($record[1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
			$state->max_column = date_diff($state->from_date, $inactive)->days - 1; //0 rel
		}
		$record[0] .= "<br>(inactive as of ".$record[1].")";
	}
	$state->task_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('TK_0');\n";
	$HTML .= "cell.innerHTML = '".$record[0]."';\n";
}

function subtask_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT * FROM ".$_DB->prefix."a14_subtask
			WHERE task_idref=".$state->task_id." ORDER BY name;";
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
		$state->records[strval($row->subtask_id)] = $element;
	}
	$stmt->closeCursor();
}

function subtask_send(&$state, &$HTML) {

	subtask_list($state);

	$HTML .= "//Subtasks...\n";
	if (count($state->records) == 1) {
		reset($state->records);
		$solo = each($state->records); //get first available "key","value" pair
		$state->subtask_id = intval($solo["key"]); //subtask_select wants to see this

	} else {
    	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Select the subtask';\n";
		$HTML .= "fill = \"<select name='selSubtask' id='selSubtask' size='1' onchange='proceed(this.parentNode,this.options[this.selectedIndex].value)'>\";\n";
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
		$HTML .= "cell = document.getElementById('ST_0');\n";
		$HTML .= "cell.innerHTML = fill;\n";
		$HTML .= "document.getElementById('selSubtask').selectedIndex=-1;\n";
	}

	return count($state->records);
}

function subtask_select(&$state, &$HTML, $rec=-1) {

	if ($rec < 0) { //checking returned
		if (!isset($_GET["row"])) return;
		$rec = strval($_GET["row"]);
	}

	subtask_list($state); //restore the record list
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid subtask id ".$rec,true);
	}
	$record = $state->records[$rec];
	if ($record[1] != "") {
		$inactive = new DateTime($record[1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
			$state->max_column = date_diff($state->from_date, $inactive)->days - 1; //0 rel
		}
		$record[0] .= "<br>(inactive as of ".$record[1].")";
	}
	$state->subtask_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('ST_0');\n";
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
	if (!array_key_exists($rec, $state->records)) {
		throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid accounting id ".$rec,true);
	}
	$record = $state->records[$rec];
	if ($record[1] != "") {
		$inactive = new DateTime($record[1]);
		if ($inactive < $state->inactive_date) {
			$state->inactive_date = $inactive;
			$state->max_column = date_diff($state->from_date, $inactive)->days - 1; //0 rel
		}
		$record[0] .= "<br>(inactive as of ".$record[1].")";
	}
	$state->account_id = $rec;
	$state->msgStatus = "";
	$HTML .= "cell = document.getElementById('AC_0');\n";
	$HTML .= "cell.innerHTML = '".$record[0]."';\n";
}

function activity_list(&$state) {
	global $_DB;

	$state->records = array();

	$sql = "SELECT activity_id, activity FROM ".$_DB->prefix."v00_timelog
		WHERE person_id=".$state->person_id." AND subtask_id=".$state->subtask_id." AND
		logdate BETWEEN '".$state->from_date->format("Y-m-d")."' AND '".$state->to_date->format("Y-m-d")."'
		ORDER BY logdate;";
	$stmt = $_DB->query($sql);
	while ($row = $stmt->fetchObject()) {
		$state->records[strval($row->activity_id)] = $row->activity;
	}
	$stmt->closeCursor();
}

function activity_send(&$state, &$HTML) {

	activity_list($state);

	$HTML .= "//Activities...\n";
   	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Select the activity';\n";
	$HTML .= "fill = \"<select name='selactivity' id='selactivity' size='1' onchange='select_activity(this)'>\";\n";
	$HTML .= "fill += \"<option value='AT0' selected>...new activity...\";\n"; //select_activity() recognizes the 'AT0'...
	foreach($state->records as $value => $name) {
			$HTML .= "fill += \"<option value='".$value."'>".$name."\";\n";
	}
	$HTML .= "fill += \"</select>\";\n";
	$HTML .= "cell = document.getElementById('AT_0');\n";
	$HTML .= "cell.innerHTML = fill;\n";
	if (count($state->records) == 0) {
		$HTML .= "document.getElementById('selactivity').selectedIndex=0;\n";
		$HTML .= "select_activity(document.getElementById('selactivity'));\n";
	} else {
		$HTML .= "document.getElementById('selactivity').selectedIndex=-1;\n";
	}

	return count($state->records);
}

function activity_select(&$state, &$HTML) {

	if (!isset($_GET["row"])) return;
	$rec = strval($_GET["row"]);

	$state->activity_id = $rec;
	$state->msgStatus = "";
	if ($rec != 0) {
		activity_list($state); //restore the record list
		if (!array_key_exists($rec, $state->records)) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid activity ".$rec,true);
		}
		$HTML .= "cell = document.getElementById('AT_0');\n";
		$HTML .= "cell.innerHTML = '".$state->records[$rec]."';\n";
	}
}

function date_send(&$state, &$HTML) {

	$HTML .= "//Date...\n";
	$HTML .= "cell = document.getElementById('DT_".$state->row."');\n";
	if ($state->row == 0) { //0 is add row
		$HTML .= "var date = ['".$state->to_date->format("Y")."','".$state->to_date->format("m")."','".$state->to_date->format("d")."'];\n";
	} else {
		$HTML .= "var date = cell.innerHTML.split('-');\n";
	}
	$HTML .= "fill = \"<table onmouseout='audit_date(this)'><tr><td>yyyy</td><td>mm</td><td>dd</td></tr><tr>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtYYYY' id='txtYYYY_ID' size='4' maxlength='4'></td>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtMM' id='txtMM_ID' size='2' maxlength='2'></td>\";\n";
	$HTML .= "fill += \"<td><input type='text' name='txtDD' id='txtDD_ID' size='2' maxlength='2'></td>\";\n";
	$HTML .= "fill += \"</tr></table>\";\n";
	$HTML .= "cell.innerHTML = fill;\n";
	$HTML .= "document.getElementById('txtYYYY_ID').value = date[0];\n";
	$HTML .= "document.getElementById('txtMM_ID').value = date[1];\n";
	$HTML .= "document.getElementById('txtDD_ID').value = date[2];\n";
}

function hours_send(&$state, &$HTML) {

	if ($state->row == 0) { //0 is add row
		$state->extension = "";
		$maxcol = $state->max_column;
	} else {
		log_list($state); //get row specific stuff
		foreach ($state->records as $recID=>$record) {
			if ($record["row"] == $state->row) break;
			array_shift($state->records);
		}
		$state->extension = $record["extension"];
		$maxcol = $record["max_column"];
	}
	$rates = active_rates($state->project_id);

	$offset = 0;
	if ($state->columns > 0) $offset = $state->closedCols; //tabular style
	for ($offset=$offset; $offset<=$maxcol; ++$offset) {
		$cellID = "HR_".$state->row."_".$offset;
		$HTML .= "//next Hours ".$offset."...\n";
		$HTML .= "cell = document.getElementById('".$cellID."');\n";
		$HTML .= "cellValue = cell.innerHTML;\n";
		if ($rates[$offset] == 0) { //no hourly rate so no input
			$HTML .= "fill = \"<div name='txtHours".$offset."' id='txtHours".$offset."_ID'>";
			$HTML .= "Rate not available</div>\";\n";
		} else {
			$HTML .= "fill = \"<input type='text' name='txtHours".$offset."' id='txtHours".$offset."_ID' size='3'";
			$HTML .= " maxlength='6' class='number' onblur='audit_hours(this)' value='\"+cellValue+\"'>\";\n";
			if ($state->extension != "") {
				$HTML .= "if (cellValue != 0) {\n";
				$HTML .= "  fill += \"<br><img src='".$_SESSION["_SITE_CONF"]["_REDIRECT"]."/images/extension.png'";
				$conf = parse_ini_file($_SESSION["_SITE_CONF"]["_EXTENSIONS"].$state->extension."_conf.php");
				$HTML .= "   title='click for ".$conf["title"]."'";
				$HTML .= "   onclick = 'return extension(get_cell_recid(\\\"".$cellID."\\\"),".$conf["width"].",".$conf["height"].")'>\";\n";
				$HTML .= "}\n";
				}
		}
    	$HTML .= "if (cell.getAttribute('data-recid') >= 0) {\n";
		$HTML .= "  cell.innerHTML = fill;\n";
		$HTML .= "}\n";
	}
}

function button_send(&$state, &$HTML) {
	$HTML .= "//Buttons...\n";
	$HTML .= "cellID = 'BN_".$state->row."';\n";
	$HTML .= "cell = document.getElementById(cellID);\n";
	$HTML .= "cell.title = '';\n";
   	$HTML .= "document.getElementById('msgGreet_ID').innerHTML = 'Enter your hours';\n";
	//onclick=onmousedown + onmouseup; if audit_count() caused by onblur of numbers issues confirm(),
	//onmouseup will not happen; in that case, mouseDown() will assure new_info() gets executed:
	$HTML .= "fill = \"<button type='button' onmousedown='mouseDown(".$state->row.")' onclick='new_hours(".$state->row.")'>";
	if ($state->row == 0) {
		$HTML .= "Submit your hours</button>";
	} else {
		$HTML .= "Submit your changes</button>";
	}
	$HTML .= "<br><button type='button' name='btnReset' onclick='Reset()'>Cancel</button>";
	$HTML .= "\";\n";
	$HTML .= "cell.innerHTML = fill;\n";
}

function audit_date(&$state, &$logdate) {

	$state->msgStatus = "!Invalid date";
	if (!isset($_POST["date"])) return false;
	$pieces =  explode("-",$_POST["date"]);
	if (count($pieces) != 3) return false;
	if (!is_numeric($pieces[0]) || !is_numeric($pieces[1]) || !is_numeric($pieces[2])) return false;

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

function audit_hour(&$state, $recID, $hours, $day) {
	global $_DB;

		if (!is_numeric($hours)) return false;
		if ($hours == 0) return true;
		if (($hours > 24) || ($hours < 0)) return false;

		$sql = "SELECT hours FROM ".$_DB->prefix."v00_timelog
				WHERE (person_id=".$state->person_id.") AND (project_id=".$state->project_id.")
				AND logdate = '".$day."' AND timelog_id <> ".$recID.";";
		$stmt = $_DB->query($sql);
		while ($row = $stmt->fetchObject()) {
			$hours += $row->hours;
		}
		if ($hours > 24) {
			$state->msgStatus = "!Total hours logged for ".$day." add up to more than 24";
			return false;
		}
	return true;
}

function audit_hours(&$state, &$logdate, &$status) {
	global $_DB;

	$ID = 0;
	if ($state->row > 0) { //0 is add row
		log_list($state);
		foreach ($state->records as $ID=>$record) { //find this row's records
			if ($record["row"] == $state->row) break;
			array_shift($state->records);
		}
		if ($ID == 0)
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST 1",true);
	}

	$day = clone $logdate;
	for ($ndx=0; $ndx < abs($state->columns); $ndx++, $day->add(new DateInterval('P1D'))) {
		if (!isset($_POST["hours".$ndx]) || ($ndx < $state->closedCols)
		|| ($_POST["hours".$ndx] == "")) {
			$status[] = ''; //no change to this record
			continue;
		}
		if (!isset($_POST["rec".$ndx]))
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST 2",true);
		$hours = $_POST["hours".$ndx];
		$recID = $_POST["rec".$ndx]; //from data-recid attribute
		$state->msgStatus = "!Please enter valid hours (".$ndx.")";

		if (!audit_hour($state, $recID, $hours, $day->format("Y-m-d"))) return false;

		if ($recID == 0) { //if adding hours, we're done
			if ($hours == 0) {
				$status[] = '';
			} else {
				$status[] = 'a';
			}
			continue;
		}

		foreach ($state->records as $ID=>$record) { //find our record
			if ($record["row"] != $state->row)
				throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST 3",true);
			if ($record["column"] == $ndx) break;
			array_shift($state->records);
		}
		if ($record["ID"] != $recID)
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST 4",true);

		if ($hours == 0) {
			$status[] = 'd';
		} elseif ($hours == $record["hours"]) {
			$status[] = '';
		} else {
			$status[] = 'u';
		}
	}

	if ($state->row > 0) { //0 is add row
		$state->task_id = intval($_POST["task"]);
		$state->subtask_id = intval($_POST["subtask"]);
		$state->account_id = intval($_POST["account"]);
		$state->activity_id = intval($_POST["activity"]);
		$sql = "SELECT COUNT(*) AS count FROM ".$_DB->prefix."v00_timelog
				WHERE (person_id=".$state->person_id.") AND (project_id=".$state->project_id.")
				AND (logdate BETWEEN '".$state->from_date->format('Y-m-d')."' AND '".$state->to_date->format('Y-m-d')."')
				AND (task_id=".$state->task_id.") AND (subtask_id=".$state->subtask_id.")
				AND (account_id=".$state->account_id.") AND (activity_id=".$state->activity_id.");";
		$stmt = $_DB->query($sql);
		if ($stmt->fetchObject()->count == 0) {
			throw_the_bum_out(NULL,"Evicted(".__LINE__."): invalid POST 5",true);
		}
	}

	$state->msgStatus = "-"; //tell server_call to reset page
	return true;
}

function add_log(&$state, &$logdate, $offset) {
	global $_DB;

	$sql = "INSERT INTO ".$_DB->prefix."b00_timelog
			(activity_idref, person_idref, subtask_idref, account_idref, logdate, hours)
			VALUES (".$state->activity_id.", ".$state->person_id.", ".$state->subtask_id.", ".$state->account_id.",
			'".$logdate->format('Y-m-d')."', ".$_POST["hours".$offset].");";
	$_DB->exec($sql);

	$state->msgStatus = "-"; //tell server_call to reset page
}

function update_log(&$state, &$logdate, $offset) {
	global $_DB;

	$sql = "UPDATE ".$_DB->prefix."b00_timelog
			SET hours=".$_POST["hours".$offset]." WHERE timelog_id=".$_POST["rec".$offset].";";
	$_DB->exec($sql);

	$state->msgStatus = "-"; //tell server_call to reset page
}

function delete_log(&$state, &$logdate, $offset) {
	global $_DB;

	$sql = "SELECT activity_idref FROM ".$_DB->prefix."b00_timelog WHERE timelog_id=".$_POST["rec".$offset].";";
	$stmt = $_DB->query($sql);
	$activity = $stmt->fetchObject()->activity_idref;
	$stmt->closeCursor();

	$sql = "DELETE FROM ".$_DB->prefix."b00_timelog WHERE timelog_id=".$_POST["rec".$offset].";";
	$_DB->exec($sql);

	$sql = "SELECT COUNT(*) AS count FROM ".$_DB->prefix."b00_timelog WHERE activity_idref=".$activity."";
	$stmt = $_DB->query($sql);
	if ($stmt->fetchObject()->count == 0) {
		$sql = "DELETE FROM ".$_DB->prefix."b02_activity WHERE activity_id=".$activity."";
		$_DB->exec($sql);
	}
	$stmt->closeCursor();

	$state->msgStatus = "-"; //tell server_call to reset page
}

function add_activity(&$state) {
	global $_DB;

	$activity = COM_input_edit("act");

	$hash = md5($activity);
	$sql = "INSERT INTO ".$_DB->prefix."b02_activity (description) VALUES (:hash);";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();

	$sql = "SELECT activity_id FROM ".$_DB->prefix."b02_activity WHERE description=:hash;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':hash',$hash,PDO::PARAM_STR);
	$stmt->execute();
	$state->activity_id = $stmt->fetchObject()->activity_id;
	$stmt->closeCursor();

	$sql = "UPDATE ".$_DB->prefix."b02_activity SET description=:desc WHERE activity_id=".$state->activity_id.";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':desc',$activity,PDO::PARAM_STR);
	$stmt->execute();
}

function update_activity(&$state) {
	global $_DB;

	$activity = COM_input_edit("act");
	$sql = "UPDATE ".$_DB->prefix."b02_activity SET description=:desc
			WHERE activity_id=".$_POST["actupd"].";";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':desc',$activity,PDO::PARAM_STR);
	$stmt->execute();

	$state->msgStatus = "."; //tell server_call we're done
	return true;
}

function new_hours(&$state) {

	$logdate = clone $state->from_date;
	if (($state->columns < 0) && ($state->row == 0)) { //add a rec in list style
		if (!audit_date($state, $logdate)) return;
	}

	$status = array();
	if (!audit_hours($state, $logdate, $status)) return;
	//adding a row but didn't select existing activity:
	if (($state->row == 0) && ($state->activity_id == 0)) add_activity($state);

	for ($ndx=0; $ndx < abs($state->columns); $ndx++, $logdate->add(new DateInterval('P1D'))) {
		switch ($status[$ndx]) {
		case 'a': //add
			add_log($state, $logdate, $ndx);
			break;
		case 'u': //update
			update_log($state, $logdate, $ndx);
			break;
		case 'd': //delete
			delete_log($state, $logdate, $ndx);
			break;
		}
	}
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->person_id = 0;
	$_STATE->inactive_date = COM_NOW();
	$_STATE->project_id = 0;
	$_STATE->close_date = COM_NOW();
	$_STATE->accounting_id = 0;
	$_STATE->accounting = "";
	$_STATE->task_id = 0;
	$_STATE->subtask_id = 0;
	$_STATE->account_id = 0;
	$_STATE->activity_id = 0;
	$_STATE->columns = -1;
	$_STATE->max_column = 0;
	$_STATE->closedCols = 0; //in tabular form => cols prior to close date
	unset($_SESSION["_EXTENSION"]);
	require_once "person_select.php";
	$persons = new PERSON_SELECT(true); //true: user can edit their own stuff
	if (!$_EDIT) { //set by executive.php
		$persons->set_state($_SESSION["person_id"]);
		$_STATE->person_select = serialize($persons);
		$_STATE->status = SELECTED_PERSON;
		break 1; //re-switch to SELECTED_PERSON
	}
	if (!$_PERMITS->can_pass("edit_logs")) throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");
	$_STATE->person_select = serialize(clone($persons));
	if ($persons->selected) {
		$_STATE->status = SELECTED_PERSON;
		break 1; //re-switch to SELECTED_PERSON
	}
	$_STATE->msgGreet = "Select the person whose logs are to be editted";
	$_STATE->status = SELECT_PERSON;
	break 2;
case SELECT_PERSON: //select the person whose logs are to be editted (person_id=0 is superduperuser)
	require_once "person_select.php"; //catches $_GET list refresh
	$persons = unserialize($_STATE->person_select);
	$persons->set_state();
	$_STATE->status = SELECTED_PERSON; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_PERSON:
	require_once "project_select.php";
	$projects = new PROJECT_SELECT($_STATE->person_id, true);
	$_STATE->project_select = serialize(clone($projects));
	if ($projects->selected) {
		$_STATE->status = SELECTED_PROJECT;
		break 1; //re-switch to SELECTED_PROJECT
	}
	$_STATE->msgGreet = "Select the project";
	$_STATE->status = SELECT_PROJECT;
	break 2;
case SELECT_PROJECT: //select the project
	require_once "project_select.php"; //catches $_GET list refresh (assumes break 2)
	$projects = unserialize($_STATE->project_select);
	$projects->set_state();
	$_STATE->project_select = serialize(clone($projects));
	$_STATE->status = SELECTED_PROJECT; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_PROJECT:
	require_once "project_select.php"; //in case of goback
	$projects = unserialize($_STATE->project_select);
	$_STATE->project_name = $projects->selected_name();
	require_once "date_select.php";
	$dates = new DATE_SELECT("wmp","p"); //show within week(w), month(m), period(p)(default)
	$_STATE->date_select = serialize(clone($dates));
	require_once "calendar.php";
	$calendar = new CALENDAR(2, "FT"); //2 pages
	$_STATE->calendar = serialize(clone($calendar));
	$_STATE->msgGreet = $_STATE->project_name."<br>Select the list style and date range";
	$_STATE->status = SELECT_SPECS;
	break 2;
case SELECT_SPECS: //set the from and to dates
	require_once "calendar.php"; //catches $_GET refresh
	require_once "date_select.php";
	$dates = unserialize($_STATE->date_select);
	if (!$dates->POST()) {
		$calendar = unserialize($_STATE->calendar);
		$_STATE->msgGreet = $_STATE->project_name."<br>Select the list style and date range";
		break 2;
	}
	set_state($dates);
	$_STATE->inactive_date = clone $_STATE->to_date;
	$_STATE->inactive_date->add(new DateInterval('P1D'));
	total_hours($_STATE); //for all projects
	$_STATE->status = SELECTED_SPECS; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_SPECS:
	log_list($_STATE);
	$_STATE->msgGreet = "Log entry for ".$_STATE->person_name.
						"<br>To add or change hours: click on the lefthand column";
	$_STATE->extension = "";
	set_closedCols();
	$_STATE->scion_start("SHEET"); //create the child state stack
	$_STATE->status = SHEET_DISP;
	break 2;
case SHEET_DISP:
	if (isset($_GET["sheet"])) { //change displayed sheet
		$_STATE = $_STATE->goback(1); //go back to log_list (BEFORE this project change)
		require_once "project_select.php";
		$projects = unserialize($_STATE->project_select);
		$projects->set_state($_GET["sheet"]);
		$_STATE->project_select = serialize($projects);
		set_closedCols();
		$_STATE->replace();
		break 1;
	}
	if (isset($_GET["reset"])) {
		$_STATE = $_STATE->goback(1); //go back to log_list
		break 1;
	}
	if (isset($_GET["getdesc"])) { //asking for the description of a cell
		cell_desc();;
		break 2;
	}
	if (isset($_POST["actupd"])) { //update an activity
		update_activity($_STATE);
		echo $_STATE->msgStatus;
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
			$SCION->status = TASK_DISP;
		} else {
			if ($SCION->columns > 0) { //tabular style
				$SCION->status = HOURS_DISP;
			} else {
				$SCION->status = DATE_DISP;
			}
		}
		$HTML .= "document.getElementById('BN_".$SCION->row."')";
		$HTML .= ".innerHTML = \"<button type='button' name='btnReset' onclick='Reset()'>Cancel</button>\";\n";
		break 1; //go back thru switch
	case TASK_DISP:
		if (task_send($SCION, $HTML) == 1) {
			task_select($SCION, $HTML, $SCION->task_id);
			$SCION->status = SUBTASK_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = TASK_PICK;
		echo $HTML;
		break 2;
	case TASK_PICK:
		task_select($SCION, $HTML);
		$SCION->status = SUBTASK_DISP;
//		break 1; //no need to break, just fall through
	case SUBTASK_DISP:
		if (subtask_send($SCION, $HTML) == 1) {
			subtask_select($SCION, $HTML, $SCION->subtask_id);
			$SCION->status = ACCOUNT_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = SUBTASK_PICK;
		echo $HTML;
		break 2;
	case SUBTASK_PICK:
		subtask_select($SCION, $HTML);
		$SCION->status = ACCOUNT_DISP;
//		break 1;
	case ACCOUNT_DISP:
		if (account_send($SCION, $HTML) == 1) {
			account_select($SCION, $HTML, $SCION->account_id);
			$SCION->status = ACTIVITY_DISP;
			break 1; //don't return yet - go back around
		}
		$SCION->status = ACCOUNT_PICK;
		echo $HTML;
		break 2;
	case ACCOUNT_PICK:
		account_select($SCION, $HTML);
		$SCION->status = ACTIVITY_DISP;
//		break 1;
	case ACTIVITY_DISP:
		activity_send($SCION, $HTML);
		$SCION->status = ACTIVITY_PICK;
		echo $HTML;
		break 2;
	case ACTIVITY_PICK:
		activity_select($SCION, $HTML);
		if ($SCION->columns > 0) { //tabular style
			$SCION->status = HOURS_DISP;
			break 1; //go back around
		}
		$SCION->status = DATE_DISP;
//		break 1;
	case DATE_DISP:
		date_send($SCION, $HTML);
		$SCION->status = HOURS_DISP;
//		break 1;
	case HOURS_DISP:
		hours_send($SCION, $HTML);
		$SCION->status = BUTTON_DISP;
//		break 1;
	case BUTTON_DISP:
		button_send($SCION, $HTML);
		echo $HTML;
		$SCION->status = STATE::CHANGE;
		break 2;
	case STATE::CHANGE:
		if (isset($_GET["reset"])) {
			$_STATE->goback(1); //go back to log_list
			break 3;
		}
		if (isset($_POST["actupd"])) {
			update_activity($SCION);
		} else {
			new_hours($SCION);
			//msgStatus='-' says to "reset", ie. goback(1), so set that state's totals:
			$temp = STATE_pull($_STATE->thread,1);
			total_hours($temp); //re-calculate for all projects
			$temp->replace();
		}
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

EX_pageStart(); //standard HTML page start stuff - insert SCRIPTS here
?>
<script>
var COLs = <?php echo $_STATE->columns; ?>;
var closedCols = <?php echo ($_STATE->columns < 0)?0:$_STATE->closedCols; ?>;
</script>
<?php
echo "<script type='text/javascript' src='".$EX_SCRIPTS."/call_server.js'></script>\n";
if ($_STATE->status == SELECT_SPECS) {
	echo "<script type='text/javascript' src='".$EX_SCRIPTS."/calendar.js'></script>\n";

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
echo "<script type='text/javascript' src='".$EX_SCRIPTS."/timelog.js'></script>\n";
}

EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case SELECT_PERSON:

	echo $persons->set_list();

	break; //end SELECT_PERSON status ----END STATE: EXITING FROM PROCESS----
case SELECT_PROJECT:

	echo $projects->set_list();

	break; //end SELECT_PROJECT status ----END STATE: EXITING FROM PROCESS----
case SELECT_SPECS:
?>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<table cellpadding="3" border="0" align="center">
  <tr>
    <td style="text-align:right"><input type="radio" name="radStyle" value="g" checked></td>
    <td colspan="2" style="text-align:left">Grouped: all hours for a unique activity on one line</td>
  </tr>
  <tr>
    <td style="text-align:right"><input type="radio" name="radStyle" value="s"></td>
    <td colspan="2" style="text-align:left">Single: each hour entry on one line</td>
  </tr>
  <tr><td>&nbsp</td><td colspan="2">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>
<?php
	echo $dates->HTML();
?>
  <tr><td>&nbsp</td><td colspan="2">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>

  <tr><td colspan="3">
<?php
	echo $calendar->create("h"); //horiz
?>
  </td></tr>

  <tr>
    <td>&nbsp</td>
    <td colspan="2" style="text-align:left">
      <button name="btnDates" type="button" value="<?php echo $_STATE->person_id ?>" onclick="this.form.submit()">Continue</button>
    </td>
  </tr>
</table>
</form>
<div id="msgStatus_ID"><?php echo $_STATE->msgStatus ?></div>

<?php //end SELECT_SPECS status ----END STATE: EXITING FROM PROCESS----
	break;
default: //list the hours and allow new entry:
?>
<div id="extension"></div>
<div id="divPopopen_ID" class="popopen">
  Enter the new activity:<br>
  <textarea name="txtActivity" id="txtActivity_ID" rows="2" cols="50"></textarea><br>
  <input type="button" onclick="save_activity(true)" value="OK">
  <input type="button" onclick="save_activity(false)" value="cancel">
</div>
<?php
	require_once "project_select.php";
	$projects = unserialize($_STATE->project_select);
	echo $projects->tabs();
?>
<table align="center" id="tblLog" cellpadding="4" border="2">
<?php //set up header & add rows:
if ($_STATE->columns < 0) {	//list style
	$headrow = "<th width='74'>Date</th><th width='30'>Hours</th>";
	$addrow = "<td id='DT_0' data-recid='0'></td>\n<td id='HR_0_0' data-recid='0'></td>\n";
} else {					//tabular style
	$week = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
	$dayadd = new DateInterval('P1D');
	$headrow = "";
	for ($ndx=0,$day=clone $_STATE->from_date; $ndx<$_STATE->columns; $ndx++,$day->add($dayadd)) {
		$headrow .= "<th";
		if ($ndx < $_STATE->closedCols) $headrow .= " class='closed'";
		$headrow .= " width='50'>";
		$headrow .= $week[$day->format("w")]."<br>".$day->format("M d");
		$headrow .= "</th>";
	}
	$addrow = "";
	for ($ndx=0; $ndx<$_STATE->columns; $ndx++) {
		$addrow .= "<td id='HR_0_".$ndx."' data-recid='0'";
		if ($ndx < $_STATE->closedCols) $addrow .= " class='closed'";
		$addrow .= "></td>\n";
	}
} ?>
  <tr>
    <th width='100'>&nbsp;</th>
    <th width='140'>Task</th>
    <th width='140'>Subtask</th>
    <th width='140'><?php echo $_STATE->accounting; ?></th>
    <?php echo $headrow; ?>
    <th width='140'>Activity</th>
  </tr>
  <tr id="add">
    <td id="BN_0" data-recid="0" onclick="begin(this)" title="Click to add hours for new activity">
      <img src="<?php echo $_SESSION["_SITE_CONF"]["_REDIRECT"]; ?>/images/add.png"></td>
    <td id="TK_0" data-recid="0"></td>
    <td id="ST_0" data-recid="0"></td>
    <td id="AC_0" data-recid="0"></td>
    <?php echo $addrow; ?>
    <td id="AT_0" data-recid="0"></td>
  </tr>
<?php
reset($_STATE->records);
if ($_STATE->columns < 0 ) { //---begin LIST STYLE---
function echo_listrow(&$_STATE, &$record, &$permits) {
	$row = $record["row"];
	$open = "    <td id='BN_".$row."' data-recid='".$row."' onclick=\"begin(this)\" class=seq";
	if ($record["closed"]) {
		echo "  <tr class='closed'>\n";
		if (!$permits->can_pass("edit_logs")) {
			echo "    <td title='closed to new input'";
		} else {
			echo $open." title='PROJECT IS CLOSED; edit with care!'";
		}
	} else {
		echo "  <tr>\n";
		echo $open;
	}
	echo ">".$row."</td>\n";
	echo "    <td id='TK_".$row."' data-recid='".$record["task_id"]."'>".$record["task"]."</td>\n";
	echo "    <td id='ST_".$row."' data-recid='".$record["subtask_id"]."'>".$record["subtask"]."</td>\n";
	echo "    <td id='AC_".$row."' data-recid='".$record["account_id"]."'>".$record["account"]."</td>\n";
	echo "    <td id='DT_".$row."' class='date'>".$record["logdate"]->format("Y-m-d")."</td>\n";
	echo "    <td id='HR_".$row."_0' data-recid='".$record["ID"]."' class='number'";
	if ($record["hours"] < 0) {
		echo " style='background-color:red' title='DUPLICATE!'";
	}
	echo ">".abs($record["hours"])."</td>\n";
	echo "    <td id='AT_".$row."' data-recid='".$record["activity_id"]."'>".$record["activity"]."</td>\n";
	echo "  </tr>\n";
} //end function echo_listrow()
	$total = 0;
	foreach ($_STATE->records AS $ID=>$record) {
		echo_listrow($_STATE, $record, $_PERMITS);
		$total += abs($record["hours"]);
	} ?>
  <tr>
    <td colspan="4"></td><td>Total:</td><td class='number'><?php echo $total; ?></td><td></td>
  </tr>
<?php
} else { //$_STATE->columns >= 0, ie. ---begin TABULAR STYLE---

function echo_tabrow(&$_STATE, &$record, &$logs) {
	$row = $record["row"];
	echo "  <tr>\n";
	echo "    <td";
	if ($_STATE->closedCols < $_STATE->columns)
		echo " id='BN_".$row."' data-recid='".$row."' onclick=\"begin(this)\"";
	echo " class=seq>".$row."</td>\n";
	echo "    <td id='TK_".$row."' data-recid='".$record["task_id"]."'>".$record["task"]."</td>\n";
	echo "    <td id='ST_".$row."' data-recid='".$record["subtask_id"]."'>".$record["subtask"]."</td>\n";
	echo "    <td id='AC_".$row."' data-recid='".$record["account_id"]."'>".$record["account"]."</td>\n";
	echo "    ";
	for ($ndx=0; $ndx<$_STATE->columns; $ndx++) {
		echo "<td id='HR_".$row."_".$ndx."' data-recid='".$logs[$ndx][1]."' class='number'";
		if ($logs[$ndx][0] < 0) {
			echo " style='background-color:red' title='SUM OF DUPLICATES! List mode shows both'";
		} elseif ($ndx < $_STATE->closedCols) {
			echo " class='closed'";
		}
		echo ">".abs($logs[$ndx][0])."</td>\n";
		$logs[$ndx] = array(0,0);
	}
	echo "\n";
	echo "    <td id='AT_".$row."' data-recid='".$record["activity_id"]."'>".$record["activity"]."</td>\n";
	echo "  </tr>\n";
} //end function echo_tabrow()
	$totals = array();
	$logs = array();
	for ($ndx=0; $ndx<$_STATE->columns; $ndx++) { //save one row's worth of data:
		$totals[] = 0;
		$logs[] = array(0,0); //$logs[][0]=>hours, $logs[][1]=>timelog_id
	}
	$row = 1;
	foreach ($_STATE->records AS $ID=>$record) {
		if ($row != $record["row"]) { //starting a new row; write out old one
			echo_tabrow($_STATE, $recsav, $logs);
			$row = $record["row"];
		}
		$totals[$record["column"]] += abs($record["hours"]);
		if ($record["hours"] < 0) { //it's a dup
			$record["hours"] += -$logs[$record["column"]][0]; //sum both hours as a neg number
			$ID = -$ID; //recID also shows as negative (we won't allow edit)
		}
		$logs[$record["column"]][0] = $record["hours"];
		$logs[$record["column"]][1] = $ID;
		$recsav = $record;
	}
	if (count($_STATE->records) > 0) { //get the last row - if there were any
		echo_tabrow($_STATE, $recsav, $logs, $totals);
	}
	echo "<tr>\n";
	echo "<td colspan='3'></td><td style='text-align:right'>project Totals:</td>\n";
	$grand = 0;
	for ($ndx=0; $ndx<$_STATE->columns; $ndx++) {
		$grand += $totals[$ndx];
		echo "<td class='number'>".$totals[$ndx]."</td>";
	}
	echo "<td class='number'>project Grand Total: ".$grand."</td>\n";
	echo "</tr>\n";
	if (count($_STATE->project_ids) > 1) { //more than 1 project: show totals for all
		echo "<tr style='border-top:thin dashed'>\n";
		echo "<td colspan='3'></td><td style='text-align:right'>all projects:</td>\n";
		$grand = 0;
		for ($ndx=0; $ndx<$_STATE->columns; $ndx++) {
			$grand += $_STATE->totals[$ndx];
			echo "<td class='number'>".$_STATE->totals[$ndx]."</td>";
		}
		echo "<td class='number'>all projects: ".$grand."</td>\n";
		echo "</tr>\n";
	}
} // ---end TABULAR STYLE--- ?>
</table>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<br>You can
<button name="btnPut" type="submit" value="<?php echo $_STATE->person_id ?>" title="click here to download">
Download</button>
this data for import into the timesheet template
<br>(check your browser preferences for where the downloaded file will go)
</form>

<div id="msgStatus_ID" class="status"><?php echo $_STATE->msgStatus ?></div>
<?php //end select ($_STATE->status) ----END STATE: EXITING FROM PROCESS----
} ?>

<?php
EX_pageEnd(); //standard end of page stuff
?>


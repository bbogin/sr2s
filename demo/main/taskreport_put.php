<?php //copyright 2010,2014-2015 C.D.Price
if (!$_PERMITS->can_pass("reports")) throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");

require_once "field_edit.php";
define ('SELECT_PROJECT', STATE::SELECT + 1);
define ('SELECTED_PROJECT', STATE::SELECTED + 1);
define ('SELECT_SPECS', STATE::SELECT + 2);
define ('DOWNLOAD_LOG', STATE::CHANGE);

$version = "v1.0"; //downloaded with the file for client verification

function set_state(&$dates) {
	global $_DB, $_STATE;

	$_STATE->from_date = clone($dates->from);
	$_STATE->to_date = clone($dates->to);

	switch ($dates->checked) {
	case "b":
		$_STATE->heading .= "<br>for all prior to ".$_STATE->to_date->format("Y-m-d");
		break;
	case "p":
		$_STATE->heading .= "<br>for dates from ".$_STATE->from_date->format('Y-m-d').
							" to ".$_STATE->to_date->format('Y-m-d');
	}

	$sql = "SELECT name FROM ".$_DB->prefix."a00_organization
			WHERE organization_id=".$_SESSION["organization_id"].";";
	$_STATE->orgname = $_DB->query($sql)->fetchObject()->name;

	$sql = "SELECT name FROM ".$_DB->prefix."a10_project
			WHERE project_id=".$_STATE->project_id.";";
	$row = $_DB->query($sql)->fetchObject();
	$_STATE->projname = $row->name;

	$_STATE->listLog = false;
	if (isset($_POST["chkList"])) $_STATE->listLog = true;

	return true;
}

function set_stmt($fields) {
	global $_DB, $_STATE;

	$sql = "SELECT ".$fields." FROM ".$_DB->prefix."v12_taskreport
			WHERE (project_id = ".$_STATE->project_id.")
			AND ((task_inactive_asof IS NULL) OR (task_inactive_asof > :to))
			ORDER BY task_id;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':to', $_STATE->to_date->format('Y-m-d'), db_connect::PARAM_DATE);
	$stmt->execute();
	return $stmt;
}

function list_log() {
	global $_STATE;

	$stmt = set_stmt("*");
	if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
		$_STATE->msgStatus = "There are no task logs to report for this period";
		return;
	}
	$_STATE->firstID = $row["task_id"];

	$_STATE->headings = array();
	foreach ($row as $name=>$value) { //headings
		if ($name == "project_id") continue; //don't send project_id
		if ($name == "task_id") continue; //don't send task_id
		$_STATE->headings[] = $name;
	}
	$stmt->closeCursor();

	if (!$_STATE->listLog) return;

	echo "<br>\n";
	echo "<table align='center'' cellpadding='4' border='2'>\n";
	echo "  <tr>";
	foreach ($_STATE->headings as $name) {
		echo "<th>".$name."</th>";
	}
	echo "<th>charged</th>";
	echo "</tr>\n";
	get_log();
	echo "</table>\n";
	echo "<br>\n";

}

function put_log() {
	global $_STATE;
	global $version;

	$to = $_STATE->to_date->format('Y-m-d');
	$filename = "taskreport_".$_STATE->orgname."_".$_STATE->projname."_".$to.".csv"; //for file_put...
	require_once "file_put.php"; //start the file put
	$out = fopen('php://output', 'w');

	$outline = array();
	$outline[] = "taskreport";
	$outline[] = $_STATE->orgname;
	$outline[] = $_STATE->projname;
	$outline[] = $to;
	$outline[] = $version;
	fputcsv($out, $outline); //ID row

	$outline = array();
	$fields = "";
	foreach ($_STATE->headings as $name) {
		$outline[] = $name;
	}
	$outline[] = "charged";
	fputcsv($out, $outline); //header row

	get_log($out);

	fclose($out);
	FP_end(); //finish off the file put
}

function get_log($file=null) {
	global $_STATE;

	$fields = "";
	foreach ($_STATE->headings as $name) $fields .= $name.",";

	$stmt = set_stmt($fields." task_id AS charged");
	$from = $_STATE->from_date->format('Y-m-d');
	$to = $_STATE->to_date->format('Y-m-d');
	$task_id = $_STATE->firstID;
	$charged = add_charged($from, $to, $task_id);
	while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
		if (end($row) != $task_id) {
			$task_id = end($row);
			$charged = add_charged($from, $to, $task_id);
		}
		$row[count($row) - 1] = $charged;
		if (is_null($file)) {
			echo "<tr>";
			foreach ($row as $value) {
				echo "<td>".$value."</td>";
			}
			echo "</tr>\n";
		} else {
			fputcsv($file, $row);
		}
	}
	$stmt->closeCursor();

}

function add_charged($fromdate, $todate, $task_id) {
	global $_DB;

	$sql = "SELECT SUM(hours*rate) AS charged FROM ".$_DB->prefix."b00_timelog AS b00
			JOIN ".$_DB->prefix."a14_subtask a14 ON a14.subtask_id = b00.subtask_idref
			JOIN ".$_DB->prefix."a12_task a12 ON a12.task_id = a14.task_idref
			JOIN ".$_DB->prefix."c00_person c00 ON c00.person_id = b00.person_idref
			JOIN ".$_DB->prefix."c02_rate c02
			ON c02.person_idref = b00.person_idref AND c02.project_idref = a12.project_idref
			WHERE logdate >= '".$fromdate."' AND logdate <= '".$todate."' AND logdate >= c02.effective_asof
			AND (c02.expire_after IS NULL OR logdate <= c02.expire_after)
			AND task_id=".$task_id;
	$charged = $_DB->query($sql)->fetchObject()->charged;
	if (is_null($charged)) {
		$charged = 0;
	} else {
		$charged = round($charged,2);
	}
	return $charged;
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->project_id = 0;
	$_STATE->close_date = false; //not used but lib/project_select.php expects it
	require_once "project_select.php";
	$projects = new PROJECT_SELECT($_PERMITS->restrict("reports"));
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
	$_STATE->project_select = serialize(clone($projects));
	$_STATE->status = SELECTED_PROJECT; //for possible goback
	$_STATE->replace();
//	break 1; //re_switch
case SELECTED_PROJECT:
	require_once "project_select.php"; //in case of goback
	$projects = unserialize($_STATE->project_select);
	$_STATE->project_name = $projects->selected_name();
	require_once "date_select.php";
	$dates = new DATE_SELECT("bp"); //show all before(b) and within period(p)
	$_STATE->date_select = serialize(clone($dates));
	require_once "calendar.php";
	$calendar = new CALENDAR(2, "FT"); //2 pages
	$_STATE->calendar = serialize(clone($calendar));
	$_STATE->msgGreet = $_STATE->project_name."<br>Select the data window";
	$_STATE->status = SELECT_SPECS;
	break 2;
case SELECT_SPECS: //set the to date
	require_once "calendar.php"; //catches $_GET refresh
	require_once "date_select.php";
	$dates = unserialize($_STATE->date_select);
	if (!$dates->POST(DATE_SELECT::TO)) { //check only to date for recent
		$calendar = unserialize($_STATE->calendar);
		$_STATE->msgGreet = "Select the data window";
		break 2;
	}
	set_state($dates);
	$_STATE->heading .= "<br>as of ".$_STATE->to_date->format('Y-m-d');
	$_STATE->msgGreet = $_STATE->project_name."<br>Download the report";
	$_STATE->status = DOWNLOAD_LOG;
	break 2;
case DOWNLOAD_LOG:
	put_log();
	$_STATE->msgGreet = "Done!";
	$_STATE->status = STATE::DONE;
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): Invalid state=".$_STATE->status);
} } //while & switch

EX_pageStart(); //standard HTML page start stuff - insert SCRIPTS here
?>
<script language="JavaScript">

<?php	if ($_STATE->status == DOWNLOAD_LOG) { ?>
function download(me) {
  me.style.visibility = "hidden";
  document.getElementById("msgStatus_ID").innerHTML = "Done!";
  me.form.submit();
}
<?php	} ?>
</script>
<?php
echo "<script type='text/javascript' src='".$EX_SCRIPTS."/call_server.js'></script>\n";
if ($_STATE->status == SELECT_SPECS) {
	echo "<script type='text/javascript' src='".$EX_SCRIPTS."/calendar.js'></script>\n";
}
EX_pageHead(); //standard page headings - after any scripts

//forms and display depend on process state; note, however, that the state was probably changed after
//entering the Main State Gate so this switch will see the next state in the process:
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
  <tr><td colspan="3">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>

  <tr><td colspan="3">
<?php
	echo $calendar->create();
?>
  </td></tr>

  <tr>
    <td style="text-align:right"><input type='checkbox' name='chkList'></td>
    <td colspan="2" style="text-align:left">List the report before download</td>
  </tr>
  <tr>
    <td>&nbsp</td>
    <td colspan="2" style="text-align:left">
      <button name="btnDates" type="button" value="dates" onclick="this.form.submit()">Continue</button>
    </td>
  </tr>
</table>
</form>
<?php //end SELECT_SPECS status ----END STATUS PROCESSING----
	break;
case DOWNLOAD_LOG: ?>

<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<?php
	list_log(); ?>
<button name="btnPut" id="btnPut_ID" type="button" value="download" onclick="download(this)">Download</button>
</form>
<br>
(check your browser preferences for where the downloaded file will go)
<?php //end default status ----END STATUS PROCESSING----
}
EX_pageEnd(); //standard end of page stuff
?>


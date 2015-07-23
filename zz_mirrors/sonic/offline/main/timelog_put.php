<?php //copyright 2010,2014-2015 C.D.Price
if (!$_PERMITS->can_pass("project_logs")) throw_the_bum_out(NULL,"Evicted(".__LINE__."): no permit");

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
	case "w":
		$_STATE->heading .= "<br>for the week of ".$_STATE->from_date->format('Y-m-d').
							" to ".$_STATE->to_date->format('Y-m-d');
		break;
	case "m":
		$_STATE->heading .= "<br>for the month of ".$_STATE->from_date->format("M-Y");
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

	return true;
}

function put_log() {
	global $_DB, $_STATE;
	global $version;

	$from = $_STATE->from_date->format('Y-m-d');
	$to = $_STATE->to_date->format('Y-m-d');

	$sql = "SELECT * FROM ".$_DB->prefix."v10_logreport
			WHERE (project_id = ".$_STATE->project_id.") AND (logdate BETWEEN :from AND :to)
			ORDER BY logdate;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':from', $from, db_connect::PARAM_DATE);
	$stmt->bindValue(':to', $to, db_connect::PARAM_DATE);
	$stmt->execute();
	if (!($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
		$_STATE->msgStatus = "No logs were downloaded";
		return;
	}

	$filename = "timelog_".$_STATE->orgname."_".$_STATE->projname."_".$from."_to_".$to.".csv"; //for file_put...
	require_once "file_put.php";

	$out = fopen('php://output', 'w');

	$outline = array();
	$outline[] = "timelog";
	$outline[] = $_STATE->orgname;
	$outline[] = $_STATE->projname;
	$outline[] = $from;
	$outline[] = $to;
	$outline[] = $version;
	fputcsv($out, $outline); //ID row
	$outline = array();
	$idoffset = 0;
	$count = 0;
	foreach ($row as $name=>$value) { //headings
		if ($name == "project_id") { //don't send project_id
			$idoffset = $count;
			continue;
		}
		$outline[] = $name;
		$count++;
	}
	fputcsv($out, $outline);

	do {
		array_splice($row, $idoffset, 1); //remove project_id column
		fputcsv($out, $row);
	} while ($row = $stmt->fetch(PDO::FETCH_NUM));
	$stmt->closeCursor();
	fclose($out);

	FP_end();
//	exit();
}

//Main State Gate: (the while (1==1) allows a loop back through the switch using a 'break 1')
while (1==1) { switch ($_STATE->status) {
case STATE::INIT:
	$_STATE->project_id = 0;
	$_STATE->close_date = COM_NOW();
	require_once "project_select.php";
	$projects = new PROJECT_SELECT($_PERMITS->restrict("project_logs"));
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
	$dates = new DATE_SELECT("wmp","m"); //within week(w), month(m), period(p), default to month
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
	$_STATE->msgGreet = $_STATE->project_name."<br>Download the log";
	$_STATE->status = DOWNLOAD_LOG;
	break 2;
case DOWNLOAD_LOG:
	put_log();
	$_STATE->msgGreet .= "Done!";
	$_STATE->status = STATE::DONE;
	break 2;
default:
	throw_the_bum_out(NULL,"Evicted(".__LINE__."): Invalid state=".$_STATE->status);
} } //while & switch

$redirect = $_SESSION["_SITE_CONF"]["_REDIRECT"];

EX_pageStart(); //standard HTML page start stuff - insert scripts here
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
echo "<script type='text/javascript' src='".$redirect."/scripts/call_server.js'></script>\n";
if ($_STATE->status == SELECT_SPECS) {
	echo "<script type='text/javascript' src='".$redirect."/scripts/calendar.js'></script>\n";
}
EX_pageHead(); //standard page headings - after any scripts
?>

<?php
//forms and display depend on process state; note, however, that the state was probably changed after entering
//the Main State Gate so this switch will see the next state in the process:
switch ($_STATE->status) {
case SELECT_PROJECT:

	echo $projects->set_list();

	break; //end SELECT_PROJECT status ----END STATE: EXITING FROM PROCESS----
case SELECT_SPECS:
?>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<br>
<table cellpadding="3" border="0" align="center">
  <tr><td>&nbsp</td><td colspan="2">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>
<?php
	echo $dates->HTML();
 ?>
  <tr><td>&nbsp</td><td colspan="2">- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </td></tr>

<tr><td colspan="3">
<?php
	echo $calendar->create();
?>
</td></tr>

  <tr>
    <td>&nbsp</td>
    <td colspan="2" style="text-align:left">
      <button name="btnDates" type="button" value="dates" onclick="this.form.submit()">Continue</button>
    </td>
  </tr>
</table>
</form>
<?php //end SELECT_PROJECT status ----END STATUS PROCESSING----
	break;
case DOWNLOAD_LOG:
?>
<form method="post" name="frmAction" id="frmAction_ID" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<button name="btnPut" id="btnPut_ID" type="button" value="download" onclick="download(this)">Download</button><br>
(check your browser preferences for where the downloaded file will go)
</form>
<?php //end DOWNLOAD_LOG status ----END STATUS PROCESSING----
} ?>

<?php
EX_pageEnd(); //standard end of page stuff
?>


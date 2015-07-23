<?php //copyright 2010,2014-2015 C.D.Price
//require_once ("../noparent.php");

$_TEMP_PERMIT = "_LEGAL_"; //a temp permission for the "are you logged in" gate (in prepend)
require_once "prepend.php";
require_once "common.php";
require_once ("db_".$_SESSION['_SITE_CONF']['DBMANAGER'].".php");
$_DB = new db_connect($_SESSION['_SITE_CONF']['DBEDITOR']);

require_once ("state.php");
if (isset($_GET["init"])) {
	$_STATE = new STATE($_GET["init"]); //create a new state object with status=STATE::INIT
	if (isset($_GET["head"])) {
		$_STATE->heading = $_GET["head"];
	}
} else {
	$_STATE = STATE_pull(); //'pull' the working state
}
function entry_audit() {
	global $_DB, $_STATE, $_PERMITS;

	$_STATE->fields["txtName"] = COM_input_edit("txtName",32);
	$_STATE->fields["txtPswd"] = $_POST["txtPswd"];
//	Note: "txtPswd" does not need input_edit since it is never used in SQL nor is it displayed in
//	HTML; and it should NOT be subjected to input_edit since that function limits the chars used.

	$sql = "SELECT c00.*, c10.*, a00.organization_id, a00.timezone
			FROM ".$_DB->prefix."c00_person AS c00
			LEFT OUTER JOIN ".$_DB->prefix."c10_person_organization AS c10
			ON (c00.person_id = c10.person_idref)
			LEFT OUTER JOIN ".$_DB->prefix."a00_organization AS a00
			ON (c10.organization_idref = a00.organization_id)
			WHERE c00.loginname=:user;";
	$stmt = $_DB->prepare($sql);
	$stmt->bindValue(':user', $_STATE->fields["txtName"], PDO::PARAM_STR);
	$stmt->execute();

	$_STATE->msgStatus = "Invalid login";
	if(!($row = $stmt->fetchObject())) {
		$_STATE->msgStatus .= " x";
		return false; //nobody there
	}
	//only super-duper user has no organization (even other superusers must have one)
	if ((is_null($row->person_idref)) && ($row->person_id != 0)) {
		$_STATE->msgStatus .= " 0";
		return false;
	}

	if (PHP_VERSION_ID < 50500) require_once "password.php";

	if (!password_verify($_STATE->fields["txtPswd"], $row->password)) {
		$_STATE->msgStatus .= " -";
		return false;
	}

	$_SESSION["person_id"] = $row->person_id;
	if (is_null($row->organization_idref)) { //should be the super-duper user
		$_SESSION["person_organization_id"] = 0;
		$_SESSION["organization_id"] = 1; //better be a record there
		$stmt->closeCursor();
		$sql = "SELECT timezone FROM ".$_DB->prefix."a00_organization WHERE organization_id=1;";
		$stmt = $_DB->query($sql);
		$row = $stmt->fetchObject();
	} else {
		$today = new DateTime(); //can't do TZO offset until org set - may be a few hours off
		while (1 == 1) {
			if (new DateTime($row->inactive_asof) >= $today) break;
			if(!($row = $stmt->fetchObject())) {
				$_STATE->msgStatus .= " +";
				return false;
			}
		}
		$_SESSION["person_organization_id"] = $row->person_organization_id;
		$_SESSION["organization_id"] = $row->organization_id;
	}
	$_SESSION["org_TZO"] = $row->timezone;
	$_STATE->msgStatus = "";
		
	$stmt->closeCursor();

	$_SESSION["UserPermits"] = $_PERMITS->get_permits($_SESSION["person_id"]); //set the users's permissions
	$_SESSION["UserPermits"]["_LEGAL_"] = TRUE; //can now pass the 'logged in' gate

	error_log("Login: by ".$_STATE->fields["txtName"]."; id=".$_SESSION["person_id"]); //not an error but the best place to put it
	return true;
}

$_STATE->fields["txtName"] = "";

$redirect = $_SESSION["_SITE_CONF"]["_REDIRECT"];
$reload = FALSE;
$_STATE->msgGreet = "Please login:";
$_STATE->msgStatus = "";
switch ($_STATE->status) {
case STATE::INIT:
	$reload = TRUE; //make sure other frames load after me
	$_STATE->status = STATE::ENTRY;
	break;
case STATE::ENTRY:
	if (entry_audit()) {
		$_STATE->msgGreet = "";
		$_STATE->msgStatus = "";
		$reload = TRUE; //reload other frames to get current login info
		$_STATE->status = STATE::DONE;
	} else {
		error_log("Logerr: by ".$_STATE->fields["txtName"]);
	}
	break;
default:
	$_STATE->status = STATE::ERROR;
}

$_STATE->replace(); //with new status, etc.
$_DB = NULL;
?>
<html>
<head>
<title>SR2S Timesheets Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="<?php echo $redirect; ?>/css/main.css" type="text/css">
<script language="JavaScript">
<!--
if (top == self) {
	top.location = "https://<?php echo($_SERVER["HTTP_HOST"].$_SESSION["_SITE_CONF"]["_OFFSET"].'/'); ?>";
}

window.onload = function () {
<?php
if ($reload) {
	echo "  top.reload_head();\n";
	echo "  top.reload_menu();\n";
}
if ($_STATE->status == STATE::DONE) {
	echo "  window.location.assign('".$redirect."/main/main.php');\n";
} else {
	echo "  document.getElementById('txtName_ID').focus();\n";
}
?>
}
//-->
</script>
</head>

<body><h1><?php echo $_STATE->msgGreet; ?></h1>
<?php
if ($_STATE->status == STATE::ENTRY) {
?>
<form method="post" name="frmLogin" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
<p>
Username: <input name="txtName" id="txtName_ID" type="text" class="formInput" value="<?php
	echo COM_output_edit($_STATE->fields['txtName']); ?>" maxlength="32" size="32">
</p>
<p>
Password: <input name="txtPswd" type="password" class="formInput" maxlength="32" size="32">
</p>
<p>
<input type="submit" value="Login">   <?php echo $_STATE->msgStatus; ?>
</p>
</form>
<?php
}
?>
</p>
</body>
</html>

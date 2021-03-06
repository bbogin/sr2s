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

$_STATE->fields["txtName"] = "";

$reload = FALSE;
$_STATE->msgGreet = "Please login:";
$_STATE->msgStatus = "";
switch ($_STATE->status) {
case STATE::INIT:
	$reload = TRUE; //make sure other frames load after me
	$_STATE->status = STATE::ENTRY;
	break;
case STATE::ENTRY:
	require_once "logging.php";
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

$redirect = $_SESSION["_SITE_CONF"]["_REDIRECT"];

?>
<html>
<head>
<title>SR2S Timesheets Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="<?php echo $redirect."/css".$_SESSION["_SITE_CONF"]["CSS"]."/".
	$_SESSION["_SITE_CONF"]["THEME"]; ?>/main.css" type="text/css">
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

<?php //copyright 2010,2014 C.D.Price
//some stuff that has wide applicability...

//Here, we will do entity de-code (the opposite of htmlentities()), allow digits (think addresses),
//trim spaces, and, optionally, truncate to length (see the system doc for "Input Integrity"):
function string_decode($string,$length=-1) { return COM_string_decode($string,$length); }
function COM_string_decode($string,$length=-1) {
	$value = trim(preg_replace('/[^\w\d\.\,\-\& ]/', '', html_entity_decode($string)));
	if ($length > 0) {
		$value = substr($value,0,$length);
	}
	return $value;
}
function input_edit($fldname,$length=-1) { return COM_input_edit($fldname,$length); }
function COM_input_edit($fldname,$length=-1) {
	return COM_string_decode($_POST[$fldname],$length);
}

function output_edit($field,$length=-1) { return COM_output_edit($field,$length); }
function COM_output_edit($field,$length=-1) {
	if ($length > 0) {
		$value = substr($value,0,$length);
	}
	$value = htmlentities($field);
	return $value;
}

function NOW() { return COM_NOW(); }
function COM_NOW() { //adjust server time zone to org's TZO
	$now = new DateTime();
	$offset = $_SESSION["org_TZO"] - $_SESSION["_SITE_CONF"]["TZO"]; //offset from server to organization
	if ($offset == 0) return $now;
	$invert = 0;
	if ($offset < 0) {
		$offset = abs($offset);
		$invert = 1;
	}
	$format = "PT".$offset."H";
	$interval = new DateInterval($format);
	$interval->invert = $invert;
	$now->add($interval);
	return $now;
}

function COM_sleep($who) { //called by obects when sleeping
	$vars = get_object_vars($who);
	$date = array();
	foreach ($vars as $name=>$value) {
		if (is_a($value, "DateTime")) {
			$date[$name] = $value->format("Y-m-d");
			$who->{$name} = $date[$name];
		}
	}
	return array("DateTime"=>$date);
}

function COM_wakeup($who, $where="sleepers") { //called by objects when waking
	foreach ($who->{$where} as $class=>$objects) {
		foreach ($objects as $name=>$value) {
			$who->{$name} = new $class($value);
		}
	}
}

?>

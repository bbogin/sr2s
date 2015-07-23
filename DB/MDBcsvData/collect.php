<?php
//run from a command line: php -f file-name
//  or interactive mode: php -a
//  php > include "Offline.php";

/*
echo("To enter a parameter, type it followed by a newline follwed by ctrl-d\n");

echo("Please enter the directory:\n");
exec ("cat",$location);
$location = $location[0];
*/

$output = fopen("collection.csv","w");

$config = parse_ini_file("collection.ini",FALSE);
foreach($config['req'] as $key=>$value) {
	echo ($value."\n");
	fwrite($output,">>".$value."\n");
	$input = fopen($value.".csv","r");
	while (!feof($input)) {
		$buffer = fgets($input, 1024);
		fwrite($output, $buffer);
	}
	fclose($input);
}
fclose($output);

exit;

$inputname = $location."/CreateTablesTemplate";
if ($prefix == "") {
	$outputname = $location."/CreateTables(no prefix).sql";
} else {
	$outputname = $location."/CreateTables(prefix ".$prefix.").sql";
}

echo($inputname."->".$outputname." OK? ('y' or 'n'):\n");
exec ("cat",$cont);
if (strtolower($cont[0]) != "y") exit;

$input = fopen($inputname,"r");
$output = fopen($outputname,"w");

while (!feof($input)) {
	$buffer = fgets($input, 1024);
	foreach($config as $key => $value) {
		$buffer = str_replace("<PREFIX>", $prefix, $buffer);
		$buffer = str_replace("<".$key.">", $value, $buffer);
		if ($drop) {
			$buffer = str_replace("-- DROP", "DROP", $buffer);
		}
	}
	fwrite($output, $buffer);
}

fclose($input);
fclose($output);

?>


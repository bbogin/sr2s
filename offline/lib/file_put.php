<?php //copyright 2010,2014 C.D.Price

function FP_end() {

	ob_end_flush();
	exit();
}

// from w-shadow.com/blog/2007/08/12/how-to-force-file-download-with-php/...
// required for IE, otherwise Content-Disposition may be ignored
if(ini_get('zlib.output_compression'))
	ini_set('zlib.output_compression', 'Off');

ob_clean();
ob_start();

 /* The three lines below basically make the 
    download non-cacheable */
 header("Cache-control: private");
 header('Pragma: private');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//...end
 
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
//header("Content-Disposition: inline; filename=\"".$filename."\"");
header("Content-Disposition: attachment; filename=\"".$filename."\"");

// DO NOT have a linefeed after this closing tag, else it will appear as the first (blank) line in the downloaded file ?>

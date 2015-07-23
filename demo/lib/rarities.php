<?php //stuff that is rarely used; put here instead of into site_conf or common.php so won't take up _SESSION space:

//$refresh_path = "/home/common/BikeStuff/SR2S/SR2S_Timesheets/DB/PGcsvData/";
//$MDBload_path = "/home/common/BikeStuff/SR2S/SR2S_Timesheets/DB/MDBcsvData/";
$refresh_path = $_SESSION["OUR_ROOT"]."/../DB/csvData/";
$MDBload_path = $_SESSION["OUR_ROOT"]."/../DB/MDBcsvData/";

?>

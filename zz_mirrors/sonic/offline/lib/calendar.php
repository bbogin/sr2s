<?php //copyright 2014 C.D.Price

class CALENDAR {
public $page_count;
public $drops;
public $noSleep = array(); //clear out these user created vars when sleeping (to save memory)
public $page;

function __construct($page_cnt=2, $drop="") {
	$this->page_count = $page_cnt;
	$this->drops = $drop."  "; //want a min of 2 chars
	$page = COM_NOW();
	$page->sub(new DateInterval('P1M'));
	$this->page = serialize($page);
}

function __sleep() { //don't save this stuff - temporary and too long
	foreach ($this->noSleep as $temp) {
		if (is_array($this->{$temp})) {
			$this->{$temp} = array();
		} else {
			$this->{$temp} = false;
		}
	}
   	return array_keys(get_object_vars($this));
}

//adapted from PHP Calendar (version 2.3), written by Keith Devens
public function generate_calendar(&$date, $tblName, $newline="\n"){

	$year = $date->format("Y");
	$month = $date->format("m");
	$today = new DateTime();
	$today = explode("-", $today->format("Y-m-d"));
	
    //remember that mktime will automatically correct if invalid dates are entered
    // for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
    // this provides a built in "rounding" feature to generate_calendar()
    $first_of_month = gmmktime(0,0,0,$month,1,$year);
    list($year, $month_name, $weekday) = explode(',',gmstrftime('%Y,%B,%w',$first_of_month));
	$days_in_month=gmdate('t',$first_of_month);

    $day_names = array("S","M","T","W","T","F","S");

    $title   = ucfirst($month_name).'  '.$year;

    //Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
    $calendar = '<table id="'.$tblName.'" class="calendar" style="align-self:stretch">'.$newline.
        '<caption class="calendar-month">'.$title."</caption>".$newline."<tr>";

    foreach($day_names as $d)
        $calendar .= '<th>'.$d.'</th>';
    $calendar .= "</tr>".$newline."<tr>";

    if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; //initial 'empty' days
    for($day=1; $day<=$days_in_month; $day++,$weekday++){
        if($weekday == 7){
            $weekday   = 0; //start a new week
            $calendar .= "</tr>".$newline."<tr>";
        }
		$calendar .= '<td name="calDay"';
		if (($day == $today[2]) && ($month == $today[1]) && ($year == $today[0])) {
			$calendar .= ' data-id="today"';
		}
 		$calendar .= ">$day</td>";
    }
    if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; //remaining "empty" days

    return $calendar."</tr>".$newline."</table>".$newline;
}

public function send($status) {
	ob_clean();
	$HTML = "@";
	if ($status == "init") {
/*		if ((substr($this->drops,0,1) == "F") || (substr($this->drops,1,1) == "F"))
			$HTML .= "CAL_set_drop('txtFrom_ID','txtFromYYYY_ID','txtFromMM_ID','txtFromDD_ID');\n";
		if ((substr($this->drops,0,1) == "T") || (substr($this->drops,1,1) == "T"))
			$HTML .= "CAL_set_drop('txtTo_ID','txtToYYYY_ID','txtToMM_ID','txtToDD_ID');\n";
		$page = NOW();
		$page->sub(new DateInterval('P1M'));*/
	} else {
		$page = unserialize($this->page);
		if ($status == "next") {
			$page->add(new DateInterval('P1M'));
		} else { //"prev"
			$page->sub(new DateInterval('P1M'));
		}
	}
	$this->page = serialize(clone($page));
	for ($ndx = 1; $ndx <= $this->page_count; $ndx++) {
		$HTML .= "var cal=document.getElementById('calendar_".$ndx."');\n";
		$HTML .= "cal.innerHTML='";
		$HTML .= $this->generate_calendar($page, "month_".$ndx,"\\\n");
		$HTML .= "';\n";
		$HTML .= "CAL_set_month('month_".$ndx."','".$page->format("Y")."','".$page->format("m")."');\n";
		$page->add(new DateInterval('P1M'));
	}

	echo $HTML;
}

public function create() {
	$page = unserialize($this->page);

	$HTML = "";
	$HTML .= "<table align='center' style='width:140px'>\n";
	$HTML .= "  <tr><td onclick='return server_call(\"GET\",\"calendar=prev\", \"\")'>&larr;Previous</td></tr>\n";
	for ($ndx = 1; $ndx <= $this->page_count; $ndx++) {
		$HTML .= "  <tr><td style='background-color:beige;display:flex;justify-content:center'>\n";
		$HTML .= "    <div id='calendar_".$ndx."' style='display:flex;align-self:stretch;width:140px'>\n";
		$HTML .= $this->generate_calendar($page, "month_".$ndx,"\n");
		$HTML .= "    </div>\n";
		$HTML .= "  </td></tr>\n";
		$HTML .= "<script>CAL_set_month('month_".$ndx."','".$page->format("Y")."','".$page->format("m")."');</script>\n";
		$page->add(new DateInterval('P1M'));
	}
	$HTML .= "  <tr><td onclick='return server_call(\"GET\",\"calendar=next\", \"\");'>Next&rarr;</td></tr>\n";
	$HTML .= "</table>\n";

	$HTML .= "<script>\n";
	if ((substr($this->drops,0,1) == "F") || (substr($this->drops,1,1) == "F"))
		$HTML .= "CAL_set_drop('txtFrom_ID','txtFromYYYY_ID','txtFromMM_ID','txtFromDD_ID');\n";
	if ((substr($this->drops,0,1) == "T") || (substr($this->drops,1,1) == "T"))
		$HTML .= "CAL_set_drop('txtTo_ID','txtToYYYY_ID','txtToMM_ID','txtToDD_ID');\n";
	$HTML .= "</script>\n";

	return $HTML;
}

} //end class

//inline code catches request for refresh:
if (isset($_GET["calendar"])) {
	$calendar = unserialize($_STATE->calendar);
	$calendar->send($_GET["calendar"]);
	$_STATE->calendar = serialize($calendar);
	$_STATE->replace();
	exit; //don't go back thru executive, it will add junk to buffer
}

?>

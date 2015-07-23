//copyright 2014 C.D.Price

LoaderS.push('init_cells();');

function init_cells() {
	var rows = document.getElementById('tblLog').rows;
	for (var rndx=2; rndx<rows.length; rndx++) { //skip header and add rows
		var cells = rows[rndx].cells;
		for (var cndx=0; cndx<cells.length; cndx++) {
			var cell = cells[cndx];
			switch (cell.id.substr(0,3)) {
			case "BN_":
				if ((cell.title == '') || (cell.title === null) || (cell.title === undefined)) {
					cell.title = "Click to make changes to hours (enter 0 to delete)";
				}
				break
			case "TK_":
			case "ST_":
			case "AC_":
				cell.title = "Double click to get a full description";
				cell.ondblclick = new Function("get_desc(this)");
				break
			case "AT_":
				cell.title = "\nClick to update"; //show_activity() looks for the NL
				cell.onclick = new Function("show_activity(this)");
				break
			}
		}
	}
	divActiv = document.getElementById("divPopopen_ID");
	txtActiv = document.getElementById("txtActivity_ID");
}

var myCell;

function get_desc(me) {
	myCell = me;
	var content = "getdesc=" + me.id.substr(0,2) + "&ID=" + me.getAttribute("data-recid");
	server_call("GET", content, "");
}

var captions = {loading:'loading, please wait...'};
begun = false; //reset on reload of page
function begin(me) {
	if (begun) return;
	begun = true;
	server_set_dbug(); //clear out the debug area
	proceed(me,me.id.substr(3));
}
function proceed(me,row,interim) {
	if (interim === undefined) {
		me.innerHTML = captions.loading;
	} else {
		me.innerHTML = interim;
	}
	server_call("GET","row="+row,"");
}

var divActiv; //set by init_cells()
var txtActiv;
function select_activity(me) {
	if (me.tagName == "OPTION") {
		me = me.parentNode;
	}
	rec = me.options[me.selectedIndex].value;
	myCell = document.getElementById("AT_0");
	if (rec == "AT0") {
		divActiv.style.visibility = "visible";
		txtActiv.focus();
	} else {
		proceed(myCell,rec);
	}
}
function show_activity(me) {
	myCell = me;
	if (me.title.charAt(0) == "\n") {
		get_desc(me); //will come back to got_activity
	} else {
		got_activity();
	}
	divActiv.style.visibility = "visible";
	txtActiv.focus();
}
function got_activity() {
	txtActiv.value = myCell.title;
}
function save_activity(doit) {
	divActiv.style.visibility = "hidden";
	if (!doit) return;
	myCell.title = txtActiv.value;
	var disp = txtActiv.value.substr(0,25);
	var recid = myCell.getAttribute("data-recid");
	if (recid == 0) { //new log
		proceed(myCell,0,disp);
	} else {
		myCell.innerHTML = disp;
		var content = "actupd="+recid;
		content += "&act="+encodeURIComponent(txtActiv.value);
		server_call("POST", content, "");
	}
}

var submitRow = -1;
function mouseDown(row) {
	submitRow = row;
}
function audit_hours(me) {
	if (me.value == "") return;
	if (isNaN(me.value)) {
		alert("Hours must be numeric");
		me.value = me.parentNode.hours;
		me.focus;
		submitRow = -1;
		return false;
	} else if ((me.value < 0) || (me.value > 24)) {
		alert("Please enter valid hours");
		me.value = me.parentNode.hours;
		me.focus;
		submitRow = -1;
		return false;
	} else if ((me.value == 0) && (me.defaultValue != 0)) {
		if (!confirm("Are you sure you want to delete these hours?")) {
			me.value = me.parentNode.value;
			me.focus;
			submitRow = -1;
			return false;
		}
		if (submitRow > -1) {
			new_hours(submitRow);
		}
	}
	return true;
}

function audit_date(me) {
	YYYY = document.getElementById("txtYYYY_ID"); //this vars are defined in containing doc
	MM = document.getElementById("txtMM_ID");
	DD = document.getElementById("txtDD_ID");
	if (isNaN(YYYY.value) || isNaN(MM.value) || isNaN(DD.value)) {
		alert("Date values must be numeric");
		MM.focus
		return;
	}
	date = new Date(Number(YYYY.value),Number(MM.value)-1,Number(DD.value));
	if (date.getTime() < fromDate.getTime()) {
		alert("The date must not be before the 'From' date");
		MM.focus
		return;
	}
	if (date.getTime() > toDate.getTime()) {
		alert("The date must not be after the 'To' date");
		MM.focus
		return;
	}
	if (date.getTime() <= closeDate.getTime()) {
		alert("The date must be after the 'Close' date");
		MM.focus
		return;
	}
	if (date.getTime() >= inactiveDate.getTime()) {
		alert("The date must be before any 'Inactive' date");
		MM.focus
		return;
	}
}

function get_cell_recid(cellID) {
	cell = document.getElementById(cellID);
	if (cell === undefined) return "0";
	return cell.getAttribute("data-recid");
}

function new_hours(row) {
	var content = "row="+row;
	if (row == 0) {
		content += "&act="+encodeURIComponent(document.getElementById("txtActivity_ID").value);
	} else {
		content += "&task="+get_cell_recid("TK_"+row);
		content += "&subtask="+get_cell_recid("ST_"+row);
		content += "&account="+get_cell_recid("AC_"+row);
		content += "&activity="+get_cell_recid("AT_"+row);
	}
	for (ndx=closedCols; ndx < Math.abs(COLs); ndx++) {
		hours = document.getElementById("txtHours"+ndx+"_ID");
		if (hours === null) continue; //input disallowed for dups, for instance
		if (hours.tagName == "INPUT") {
			content += "&rec"+ndx+"="+hours.parentNode.getAttribute("data-recid");
			content += "&hours"+ndx+"="+hours.value;
		}
	}
	if ((COLs < 0) && (hours.parentNode.rec == 0)) {
		content += "&date=";
		content += document.getElementById("txtYYYY_ID").value;
		content += "-"+document.getElementById("txtMM_ID").value;
		content += "-"+document.getElementById("txtDD_ID").value;
	}

	server_call("POST", content, "");
}

var EXT_up = false;
var EXT_width = 800; //defaults
var EXT_height = 600;
function extension(recID,width,height) {
	if (EXT_up) {
//    alert("Only one popup at a time, please!");
		return;
	}
	var x = document.createElement("IFRAME");
	x.width = width;
	x.height = height;
	x.id = 'extension'; //we find it by id
	x.name = 'extension'; //popup can return only the name
	document.body.insertBefore(x,document.getElementById("extension"));
	x.setAttribute("src", "ext_exec.php?init=EX&recid="+recID);
	EXT_up = true;
}
function remove_ext(done) {
	if (done) {
		document.body.removeChild(document.getElementById('extension'));
		EXT_up = false;
	} else {
		server_call("GET","quit","ext_exec.php");
	}
}

function Reset() {
	window.location = IAm + "?reset";
}


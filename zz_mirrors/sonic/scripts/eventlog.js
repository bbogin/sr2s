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
			case "EV_":
			case "AC_":
				cell.title = "Double click to get a full description";
				cell.ondblclick = new Function("get_desc(this)");
				break
			case "CM_":
				cell.title = "\nClick to update"; //show_comments() looks for the NL
				cell.onclick = new Function("show_comments(this, true)");
				break
			}
		}
	}
	divActiv = document.getElementById("divPopopen_ID");
	txtActiv = document.getElementById("txtComments_ID");
}

var myCell;

function get_desc(me) {
	myCell = me;
	var content = "getdesc=" + me.id.substr(0,2) + "&ID=" + me.getAttribute("data-recid");
	server_call("GET", content, "");
}

var captions = {loading:"loading, please wait..."};
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
function show_comments(me) {
	myCell = me;
	if (me.title.charAt(0) == "\n") {
		get_desc(me);
	} else {
		got_comments();
	}
	divActiv.style.visibility = "visible";
	txtActiv.focus();
}
function got_comments() {
	txtActiv.value = myCell.title;
}
function save_comments(doit) {
	divActiv.style.visibility = "hidden";
	if (!doit) return;
	myCell.title = txtActiv.value;
	myCell.innerHTML = txtActiv.value.substr(0,25);
}

var submitRow = -1;
function mouseDown(row) {
	submitRow = row;
}
function audit_count(me, maxCount) {
	if (me.value == "") return true;
	if (isNaN(me.value)) {
		alert("Counts must be numeric");
		me.value = me.parentNode.value;
		me.focus
		submitRow = -1;
		return false;
	} else if (me.value > maxCount) {
		if (!confirm(me.value+" seems high - are you sure?")) {
			me.value = me.parentNode.value;
			me.focus;
			submitRow = -1;
			return false;
		}
		if (submitRow > -1) {
			new_info(submitRow);
		}
	} else if (me.value < 0) {
		alert("Please enter a valid count");
		me.value = me.parentNode.value;
		me.focus;
		submitRow = -1;
		return false;
	} else if ((me.value == 0) && (me.defaultValue != 0) && (me.id = 'txtSessions_ID')) {
		if (!confirm("Are you sure you want to delete this record?")) {
			me.value = me.parentNode.value;
			me.focus;
			submitRow = -1;
			return false;
		}
		if (submitRow > -1) {
			new_info(submitRow);
		}
	}
	return true;
}

function audit_date(me) {
	var YYYY = document.getElementById("txtYYYY_ID"); //this vars are defined in containing doc
	var MM = document.getElementById("txtMM_ID");
	var DD = document.getElementById("txtDD_ID");
	if (isNaN(YYYY.value) || isNaN(MM.value) || isNaN(DD.value)) {
		alert("Date values must be numeric");
		MM.focus;
		return;
	}
	var date = new Date(Number(YYYY.value),Number(MM.value)-1,Number(DD.value));
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
	date = YYYY.value+"-"+MM.value+"-"+DD.value;
	if (confirm(date + " OK?")) {
		server_call("GET","row=1",""); //open rest of input
	} else {
		MM.focus;
	}
}

function get_cell_recid(cellID) {
	var cell = document.getElementById(cellID);
	if ((cell === undefined) || (cell == null)) return "0";
	return cell.getAttribute("data-recid");
}

function new_info(row) {
	var content = "row="+row;
	var sessions = document.getElementById("txtSessions_ID").value;
	if (row == 0) {
		if (document.getElementById('txtYYYY_ID') != null) {
			YYYY = document.getElementById('txtYYYY_ID').value;
			MM = document.getElementById('txtMM_ID').value;
			DD = document.getElementById('txtDD_ID').value;
			content += "&date="+YYYY+"-"+MM+"-"+DD;
		}
	} else {
		content += "&event="+get_cell_recid("EV_"+row);
		var AC = get_cell_recid("AC_"+row);
		if (AC != 0) content += "&account="+AC;
	}
	content += "&sessions="+sessions;
	content += "&attendance="+document.getElementById("txtAttendance_ID").value;
	content += "&comments="+encodeURIComponent(document.getElementById("CM_"+row).title);

	server_call("POST", content, "");
}

function Reset() {
	window.location = IAm + "?reset";
}


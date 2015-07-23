//copyright 2014 C.D.Price

var ItIs;
//var IAm; //must be set by including code
function server_call(type, content, who) { //type: "POST", "GET"
  var caller = new XMLHttpRequest();
  caller.onreadystatechange=function() {
    if (caller.readyState==4 && caller.status==200) {
      server_answer(caller.responseText);
    }
  }
  if (who == "") {
    ItIs = IAm;
  } else {
    ItIs = who;
  }
  if (type == "GET") {
    caller.open("GET", ItIs + "?servercall=g&" + content, true);
    caller.send(null);
  } else {
    caller.open("POST", ItIs, true);
    caller.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    caller.send("servercall=p&" + content);
  }
}
function server_answer(resp) {
  dbugmsg = document.getElementById('msgDebug');
  if (dbugmsg !== undefined) {
    try {
    dbugmsg.innerText += resp; //IE
    dbugmsg.innerHTML += resp; //Firefox
    } catch(dummy){}
  }
  var code = resp.charAt(0);
  var ID = resp.substr(1,2);
  var msg = resp.substr(3);
  resp = resp.slice(1);
  switch (code) {
  case "!": //display status msg, stay here
    document.getElementById("msgStatus_ID").innerHTML = resp;
    break;
  case "?": //ask for confirmation to continue, or stay here
    if (confirm(msg)) {
      server_call("GET", ID + "=OK", ItIs);
    }
    break;
  case "&": //ask for info, always return
    var info = prompt(msg,"");
    server_call("GET", ID + "=" + encodeURIComponent(info), ItIs);
    break;
  case "@": //HTML
    eval(resp);
    break;
  case "-": //re-draw the page
    window.location = IAm + "?reset";
    break;
  case ".": //the period: we're done with dialog, stay here
    break;
  default:
    alert("Whas up? "+resp.substr(0,200));
  }
}
function server_set_dbug() {
	dbugmsg = document.getElementById('msgDebug');
	if (dbugmsg !== undefined) {
		try {
			dbugmsg.innerText = ''; //IE
			dbugmsg.innerHTML = ''; //Firefox
		} catch(dummy){}
	}
}


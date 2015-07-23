; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?> prevents hacking

_INCLUDE[] = offline/lib
_INCLUDE[] = offline/main

_EXTENSIONS = extension/

DBMANAGER=mysql
DBCONN="host=nweninger-as.db.sonic.net;dbname=nweninger_as"
DBREADER=":nweninger_as-ro:4b208533"
DBEDITOR=":nweninger_as-rw:5ab75b70"

;TimeZoneOffset from Greenwich, in hours, of the server; west is negative
TZO=-8


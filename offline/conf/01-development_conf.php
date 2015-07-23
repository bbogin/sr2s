; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?> prevents hacking
;site.conf files call this file via _MORE; splitting the files allows this one to be outside the doc root

PAGETITLE = "SR2S Timesheets (development)"

;the initial css theme subdirectory
THEME=grays
;THEME=mellow

;versioning for script and css files force re-caching:  insert this string after "/scripts" or "/css"
SCR="/v1"
CSS="/v1"

;DBMANAGER=pgsql
DBMANAGER=mysql
DBCONN="host=localhost;dbname=timesheets" ;NOTE: order of variables is important to mysql
;The first char is the delimiter between user and password:
DBREADER=":ts_reader:ts*reader"
DBEDITOR=":ts_editor:ts*editor"
;DBADMIN=":ts_admin:ts*admin"
;DBPREFIX = "d_"
DBPREFIX = ""

;0=production; 1=development; 2=training:
RUNLEVEL=1


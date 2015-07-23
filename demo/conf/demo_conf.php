; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?> prevents hacking
;site.conf files call this file via _MORE; splitting the files allows this one to be outside the doc root

;The _INCLUDE paths will be added to the include path
; relative (ie. not starting with "/") paths get OUR_ROOT prepended
;Note that these files must be specified after any _REDIRECT
_INCLUDE[] = ../../demo/lib
_INCLUDE[] = ../../demo/main

;directory of subtask extension modules - needs ending backslash
_EXTENSIONS = ../../extension/

;the initial css theme subdirectory
THEME=grays
;THEME=mellow

;versioning for script and css files force re-caching:  insert this string after "/scripts" or "/css"
SCR="/v1"
CSS="/v1"

;TimeZoneOffset from Greenwich, in hours, of the server; west is negative
;test a TZ on US East Coast
TZO=-5

PAGETITLE = "SR2S Timesheets (demo)"

;DBMANAGER=pgsql
DBMANAGER=mysql
DBCONN="host=localhost;dbname=timesheets" ;NOTE: order of variables is important to mysql
;The first char is the delimiter between user and password:
DBREADER=":ts_reader:ts*reader"
DBEDITOR=":ts_editor:ts*editor"
;DBADMIN=":ts_admin:ts*admin"
DBPREFIX = "d_"

;0=production; 1=development; 2=training:
;RUNLEVEL=2
RUNLEVEL=1



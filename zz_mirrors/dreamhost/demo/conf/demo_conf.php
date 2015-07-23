; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?>

_INCLUDE[] = ../../demo/lib
_INCLUDE[] = ../../demo/main

_EXTENSIONS = ../../demo/extension/

THEME=mellow
SCR="/v1"
CSS="/v1"

TZO=-5

PAGETITLE = "SR2S Timesheets (demo)"

DBMANAGER=mysql
DBCONN="host=tsmcbc.sr2smcbc.org;dbname=ts_mcbc_tr"
;The first char is the delimiter between user and password:
DBREADER=":ts_r2edaer:&-ts4326xx^"
DBEDITOR=":ts_r1otide:^-ts2612zz&"
DBPREFIX = "d_"

RUNLEVEL=1


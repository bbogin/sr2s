; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?> prevents hacking

;The site specific configuration file.  prepend.php will look for this file, assume its directory is the root
;(OUR_ROOT) directory for the site (unless _REDIRECTed), and will parse it into an array which is then saved
;as $_SESSION["_SITE_CONF"].

;_REDIRECT alters OUR_ROOT - the location of site_conf.php and the place to start looking for includes, etc.;
;In this case, it affects only the HTML file load location 
;Note that the _MORE files must be specified after this directive and relative to this new path
_REDIRECT = "../demo"
;_OFFSET is the opposite of _REDIRECT, ie. it goes down while _REDIRECT goes up; see below for more info...
_OFFSET = "/demo"

;The _MORE files will be parsed after this file; they can be put somewhere out of danger, ie. not under the Document Root
_MORE[] = ../../demo/conf/demo_conf.php

;more on _REDIRECT and _OFFSET:

;_REDIRECT alters OUR_ROOT by backing up the directory structure for each ".." found then adding the rest to
;  OUR_ROOT and saving the rest as a new _REDIRECT.  This 'new' _REDIRECT is added as the top directory for
;  all file specs in HTML, eg. src='_REDIRECT'/....
;  For example, assume OUR_ROOT="/home/common/BikeStuff/SR2S/SR2S_Timesheets/pages/training".  if _REDIRECT=".."
;    then OUR_ROOT becomes "/home/common/BikeStuff/SR2S/SR2S_Timesheets/pages".  Or if _REDIRECT="../../demo"
;    then OUR_ROOT becomes "/home/common/BikeStuff/SR2S/SR2S_Timesheets/demo" and HTML called files will look
;    like src=/demo/....
;  It can be used to push HTML called files down the directory but not altering OUR_ROOT.  For example, assume
;    OUR_ROOT="/home/common/BikeStuff/SR2S/SR2S_Timesheets/pages/demo" and _REDIRECT="../demo".  The ".." will
;    back the /demo off OUR_ROOT but the "/demo" in _REDIRECT puts it back on while becoming the top directory
;    for HTML called files.

;_OFFSET is the directory offset from DocumentRoot where this thread starts, eg. sr2s01.localhost/training.
;  It is used when restarting to prevent reverting to the DocumentRoot
;  If not specified in the site_conf, it will default to the opposite of _REDIRECT, ie. pick up those directory
;    levels the ".." in _REDIRECT lopped off.


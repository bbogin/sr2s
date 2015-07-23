; <?php ob_clean(); header('HTTP/1.0 404 not found'); exit(); ?> prevents hacking by displaying a 404 page

;The site specific configuration file.  prepend.php will look for this file, assume its directory is the root
;(OUR_ROOT) directory for the site (unless _REDIRECTed), and will parse it into an array which is then saved
;as $_SESSION["_SITE_CONF"].

;Note that the _MORE files must be specified after this directive and relative to this new path
_REDIRECT = "../pages"

;The _MORE files will be parsed after this file; they can be put somewhere out of danger, ie. not under the Document Root
_MORE[] = ../offline/conf/02-training_conf.php
_MORE[] = ../offline/conf/common_conf.php


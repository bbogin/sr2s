; <?php header("Location: https://".$_SERVER["HTTP_HOST"]); ?> prevents hacking
;site.conf files call this file via _MORE; splitting the files allows this one to be outside the doc root

;The _INCLUDE paths will be added to the include path; relative (ie. not starting with "/") paths get OUR_ROOT prepended
;Note that these files must be specified after any _REDIRECT
_INCLUDE[] = ../offline/lib
_INCLUDE[] = ../offline/main

;directory of subtask extension modules - needs ending backslash
_EXTENSIONS = ../extension/

;TimeZoneOffset from Greenwich, in hours, of the server; west is negative
;test a TZ on US East Coast
TZO=-5


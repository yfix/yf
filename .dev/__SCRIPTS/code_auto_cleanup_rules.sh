#!/bin/bash

dirs="
plugins/
classes/
modules/
admin_modules/
priority2/
"
for d in $dirs; do
	echo $d
	php -r '$yf="../../"; require $yf."classes/yf_dir.class.php"; $d = new yf_dir(); print_r($d->replace($yf."'$d'","-f /\.class\.php/", "","/\n[\s\t\r]*\n[\s\t\r]*\n/", "\n\n"));';
done
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
	php -r '$yf="../../"; require $yf."classes/yf_dir.class.php"; $d = new yf_dir(); print_r($d->replace($yf."'$d'","-f /\.class\.php/", "","/[\s\t\r]*\n/", "\n"));';
done

#$yf="../../";
#require $yf."classes/yf_dir.class.php";
#$d = new yf_dir();
#print_r($d->replace($yf."'$d'","-f /\.class\.php/", "","/if\(/", "if ("));

# TODO: find oout how to NOT parse/replace strings regexps for these:

# if(            ->  if (
# }else          -> } else
# else {         -> else {
# foreach(       -> foreach (
# =>$            -> => $
# ([^\s\t]+)=>   -> \1 =>
# ){\n           -> ) {\n
# function ([a-z0-9_])\(    -> function \1 (

# ]=             -> ] =
# =[             -> = [
# )=             -> ) =

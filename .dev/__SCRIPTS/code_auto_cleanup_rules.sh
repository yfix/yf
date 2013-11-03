#!/bin/bash

php -r '$yf="../../"; require $yf."classes/yf_dir.class.php"; $d = new yf_dir(); print_r($d->replace($yf."plugins/","-f /\.class\.php/", "","/\n[\s\t\r]*\n[\s\t\r]*\n/", "\n\n"));'
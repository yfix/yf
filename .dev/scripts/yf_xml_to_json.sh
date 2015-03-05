#!/bin/bash

if [ -t 0 ]; then
	echo "== ERROR: please provide first argument to this script";
	echo "usage: cat in.xml | "$0" > out.json";
	exit;
fi

php -r '
	$xml = simplexml_load_file("php://stdin");
	$json = json_encode($xml);
	echo $json;
' | python -m json.tool | unexpand --tabs=4

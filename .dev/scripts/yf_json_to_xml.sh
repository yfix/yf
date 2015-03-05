#!/bin/bash

# pear install channel://pear.php.net/XML_Serializer-0.20.2

if [ -t 0 ]; then
	echo "== ERROR: please provide first argument to this script";
	echo "usage: cat in.json | "$0" > out.xml";
	exit;
fi

php -r '
	function json_to_xml($json) {
		include_once("XML/Serializer.php");
		$options = array (
			"addDecl"	=> TRUE,
			"encoding"	=> "UTF-8",
			"indent"	=> "    ",
			"rootName"	=> "json",
			"mode"		=> "simplexml"
		);
		$serializer = new XML_Serializer($options);
		$obj = json_decode($json);
		if ($serializer->serialize($obj)) {
			return $serializer->getSerializedData();
		} else {
			return null;
		}
	}
	$json = file_get_contents("php://stdin");
	$xml = json_to_xml($json);
	echo $xml;
'

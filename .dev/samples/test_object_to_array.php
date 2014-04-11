<?php

function object_to_array($d) {
	if (is_object($d)) {
		$d = get_object_vars($d);
	}
	if (is_array($d)) {
		return array_map(__FUNCTION__, $d);
	} else {
		// Return array
		return $d;
	}
}
function obj2arr(&$obj) {
	$obj = (array)$obj;
	foreach ($obj as &$v) {
		if (is_array($v)) {
			obj2arr($v);
		}
	}
	return $obj;
}

$f = './airports_active.json';
$a = json_decode(file_get_contents($f));
$a = current($a);

echo 'memory = '.memory_get_usage().PHP_EOL;

$ts = microtime(true);
$_a = obj2arr($a);
echo 'obj2arr: '.round(microtime(true) - $ts, 4).', count = '.count($_a).PHP_EOL;
unset($_a);

echo 'memory = '.memory_get_usage().PHP_EOL;

$ts = microtime(true);
$_a = object_to_array($a);
echo 'object_to_array: '.round(microtime(true) - $ts, 4).', count = '.count($_a).PHP_EOL;
unset($_a);

echo 'memory = '.memory_get_usage().PHP_EOL;

/* Results for 10MB JSON:
memory = 36668536
obj2arr: 0.202, count = 16114
memory = 36668652
object_to_array: 2.9618, count = 16114
memory = 36668676
*/
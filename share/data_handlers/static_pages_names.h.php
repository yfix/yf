<?php

$data = array();
$Q = db()->query('SELECT id,name FROM '.db('static_pages').' WHERE active="1"');
while ($A = db()->fetch_assoc($Q)) {
	$_name = preg_replace('/[^a-z0-9\_\-]/i', '', _strtolower($A['name']));
	if (strlen($_name)) {
		$data[$_name] = $_name;
	}
}

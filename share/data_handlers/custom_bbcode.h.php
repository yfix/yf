<?php

$data = array();
$q = db()->query('SELECT * FROM '.db('custom_bbcode').' WHERE active="1"');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['tag']] = array(
		'useoption'	=> $a['useoption'],
		'replace'	=> $a['replace']
	);
}
return $data;
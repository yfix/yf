<?php

$a = db()->query('SELECT name FROM '.db('admin_modules').' WHERE active="1"');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['name']] = $a['name'];
}
if (is_array($data)) {
	ksort($data);
}
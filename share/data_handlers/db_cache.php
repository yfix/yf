<?php

$data = array();
$q = db()->query('SELECT * FROM '.db('cache').'');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['key']] = $a;
}
return $data;
<?php

$data = array();
$q = db()->query('SELECT user_id FROM '.db('auto_connected').' WHERE active="1"');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['user_id']] = $a['user_id'];
}
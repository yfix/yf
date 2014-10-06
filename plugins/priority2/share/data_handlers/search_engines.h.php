<?php

$data = array();
$Q = db()->query('SELECT * FROM '.db('search_engines').' WHERE active="1"');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['search_url']] = $A;
}
return $data;
<?php

$data = array();
$Q = db()->query('SELECT * FROM '.db('user').' WHERE poster_id != 0 AND active="1" ORDER BY nick ASC');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['poster_id']][$A['id']] = _display_name($A);
}
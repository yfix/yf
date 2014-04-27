<?php

$data = array();
$Q = db()->query('SELECT * FROM '.db('featured_users').' WHERE active="1"');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['user_id']] = $A;
}
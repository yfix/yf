<?php

$Q = db()->query('SELECT name FROM '.db('user_modules').' WHERE active="1"');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['name']] = $A['name'];
}
if (is_array($data)) {
	ksort($data);
}

<?php

$Q = db()->query('SELECT * FROM '.db('countries').' WHERE f="1" ORDER BY n');
while ($A = db()->fetch_assoc($Q)) {
	$data['f_'.$A['c']] = $A['n'];
}
<?php

$data = array();
$Q = db()->query('SELECT * FROM '.db('locale_langs').' WHERE active="1" ORDER BY locale ASC');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['locale']] = $A['locale'];
}
return $data;
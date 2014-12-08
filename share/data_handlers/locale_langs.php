<?php

$data = array();
$q = db()->query('SELECT * FROM '.db('locale_langs').' WHERE active="1" ORDER BY locale ASC');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['locale']] = $a;
}
return $data;
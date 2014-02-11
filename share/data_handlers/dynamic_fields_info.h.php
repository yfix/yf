<?php

$q = db()->query('SELECT * FROM '.db('dynamic_fields_info').' WHERE active="1" ORDER BY `order`');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['category_id']][$a['id']] = $a;
}
<?php

$data = array();
$q = db()->query('SELECT * FROM '.db('admin_menu').' WHERE `order` > 0 ORDER BY `group` ASC, `order` ASC');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['group']][$a['id']] = $a;
}
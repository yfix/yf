<?php

$data = array();
$q = db()->query('SELECT * FROM '.db('form_attributes').' WHERE active="1"');
while ($a = db()->fetch_assoc($q)) {
	$data[$a['form_id']][$a['field']][$a['attr']] = $a['value'];
}

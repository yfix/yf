<?php

$data = array();
$Q = db()->query('SELECT role FROM '.db('core_servers').' WHERE role != "" GROUP BY role');
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['role']] = $A['role'];
}
return $data;
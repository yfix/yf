<?php

$Q = db()->query('SELECT * FROM '.db('moods').' WHERE active="1" '.($locale ? ' AND locale="'.db()->es($locale).'"' : ''));
while ($A = db()->fetch_assoc($Q)) {
	$data[$A['id']] = $A['name'];
}

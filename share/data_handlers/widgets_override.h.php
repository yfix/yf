<?php

$data = array();
$Q = db()->query('SELECT * FROM '.db('widgets').' ORDER BY object ASC, action ASC');
while ($A = db()->fetch_assoc($Q)) {
	$cur_themes = array();
	foreach (explode(';', $A['theme']) as $v) {
		$v = intval($v);
		if (!empty($v)) {
			$cur_themes[$v] = $v;
		}
	}
	$cols_data = unserialize($A['columns']);
	if (!empty($cur_themes)) {
		foreach ((array)$cur_themes as $_theme_id) {
			$data['__theme__'.$_theme_id.'__'.$A['object'].($A['action'] ? '->'.$A['action'] : '')] = $cols_data;
		}
	} else {
		$data[$A['object'].($A['action'] ? '->'.$A['action'] : '')] = $cols_data;
	}
}

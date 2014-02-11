<?php

$data = array();
foreach ((array)db()->get_all('SELECT * FROM '.db('menu_items').' WHERE active="1" ORDER BY `order` ASC') as $item) {
	$data[$item['menu_id']][$item['id']] = $item + array('have_children' => 0);
}
foreach ((array)$data as $menu_id => $items) {
	foreach ((array)$items as $id => $item) {
		$parent_id = $item['parent_id'];
		if (!$parent_id) {
			continue;
		}
		$data[$menu_id][$parent_id]['have_children']++;
	}
}

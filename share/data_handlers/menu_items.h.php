<?php

$Q = db()->query("SELECT * FROM ".db("menu_items")." WHERE active='1' ORDER BY order ASC");
while ($A = db()->fetch_assoc($Q)) {
	$A["have_children"] = 0;
	$data[$A["menu_id"]][$A["id"]] = $A;
}
$Q = db()->query("SELECT menu_id,parent_id FROM ".db("menu_items")." WHERE parent_id != 0");
while ($A = db()->fetch_assoc($Q)) {
	if (!isset($data[$A["menu_id"]][$A["parent_id"]]["have_children"])) {
		$data[$A["menu_id"]][$A["parent_id"]]["have_children"] = 0;
	}
	$data[$A["menu_id"]][$A["parent_id"]]["have_children"]++;
}
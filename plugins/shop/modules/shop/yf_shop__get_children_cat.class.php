<?php
class yf_shop__get_children_cat{

	function _get_children_cat ($id) {
		$sql1 =	"SELECT id FROM shop_sys_category_items WHERE parent_id = ". $id;
		$cat = db()->query($sql1);
		while ($A = db()->fetch_assoc($cat)) {
			$cat_id .= $A["id"].",";
			$sql2 =	"SELECT id FROM shop_sys_category_items WHERE parent_id = ". $A["id"];
			$res_q = db()->query($sql2);
			if (db()->num_rows($res_q)) {
				module("shop")->_get_children_cat ( $A["id"]);	
			}
		}	
		$cat_id = rtrim($cat_id, ",");
		return $cat_id;
	}
	
}
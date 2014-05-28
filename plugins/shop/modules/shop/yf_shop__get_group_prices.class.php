<?php
class yf_shop__get_group_prices{

	function _get_group_prices ($product_ids = array()) {
		if (is_numeric($product_ids)) {
			$return_single = $product_ids;
			$product_ids = array($product_ids);
		}
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups) || empty($product_ids)) {
			return false;
		}
		$group_prices = array();
		// Get prices per group
		$Q = db()->query(
			"SELECT * FROM ".db('shop_group_options')." 
			WHERE product_id IN (".implode(",", $product_ids).") 
				AND group_id IN (".implode(",", array_keys($user_groups)).")"
		);
		while($A = db()->fetch_assoc($Q)) {
			if (!isset($user_groups[$A["group_id"]])) {
				continue;
			}
			$group_prices[$A["product_id"]][$A["group_id"]] = floatval($A["price"]);
		}
		if ($return_single) {
			return $group_prices[$return_single];
		}
		return $group_prices;
	}
	
}
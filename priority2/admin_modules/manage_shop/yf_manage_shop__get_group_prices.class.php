<?php
class yf_manage_shop__get_group_prices{

	function _get_group_prices ($product_id = 0) {
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups)) {
			return array();
		}
		$group_pricess	= array();
		foreach ((array)$user_groups as $_group_id => $_group_name) {
			if (!$_group_id) {
				continue;
			}
			$group_prices[$_group_id] = 0;
		}
		$product_id = intval($product_id);
		if (!empty($product_id)) {
			// Get prices per group
			$Q = db()->query(
				"SELECT * FROM ".db('shop_group_options')." 
				WHERE product_id=".$product_id." 
					AND group_id IN (".implode(",", array_keys($user_groups)).")"
			);
			while($A = db()->fetch_assoc($Q)) {
				if (!$A["group_id"] || !isset($user_groups[$A["group_id"]])) {
					continue;
				}
				$group_prices[$A["group_id"]] = floatval($A["price"]);
			}
		}
		return $group_prices;
	}
	
}
<?php
class yf_manage_shop__save_group_prices{

	function _save_group_prices ($product_id = 0) {
		if (!$product_id) {
			return false;
		}
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups)) {
			return false;
		}
		// Get prices per group
		$Q = db()->query(
			"SELECT * FROM ".db('shop_group_options')." 
			WHERE product_id=".$product_id." 
				AND group_id IN (".implode(",", array_keys($user_groups)).")"
		);
		while($A = db()->fetch_assoc($Q)) {
			if (!isset($user_groups[$A["group_id"]])) {
				continue;
			}
			$group_prices[$A["group_id"]] = $A["price"];
		}
		foreach ((array)$user_groups as $_group_id => $_group_name) {
			$new_group_price = $_POST["group_prices"][$_group_id];
			$sql = array(
				"product_id"=> intval($product_id),
				"group_id"	=> intval($_group_id),
				"price"		=> floatval($new_group_price),
			);
			if (isset($group_prices[$_group_id])) {
				db()->UPDATE("shop_group_options", $sql, "product_id=".intval($product_id)." AND group_id=".intval($_group_id));
			} else {
				db()->INSERT("shop_group_options", $sql);
			}
		}
	}
	
}
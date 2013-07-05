<?php
class yf_shop__create_order_record{

	/**
	* Create order record (1 db('shop_orders'), multiple db('shop_order_items'))
	*/
	function _create_order_record() {
		$cart = &$_SESSION["SHOP_CART"];
		if (empty($_POST)) {
			return false;
		}
		// Verify products
		if (!common()->_error_exists()) {
			// Get products from db
			$products_ids = array();
			foreach ((array)$cart as $_item_id => $_info) {
				if ($_info["product_id"]) {
					$products_ids[$_info["product_id"]] = $_info["product_id"];
				}
			}
			if (!empty($products_ids)) {
				$products_infos = db()->query_fetch_all("SELECT * FROM ".db('shop_products')." WHERE id IN(".implode(",", $products_ids).") AND active='1'");
				$products_atts	= module('shop')->_get_products_attributes($products_ids);
				$group_prices	= module('shop')->_get_group_prices($products_ids);
			}
			if (empty($products_infos)) {
				return _re("SHOP: Wrong products, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
		}
		// Save into database
		if (!common()->_error_exists()) {
			// Insert order into db
			$order_sql = array(
				"date"		=> time(),
				"user_id"	=> intval(module('shop')->USER_ID),
				"ship_type"	=> intval($_POST["ship_type"]),
				"pay_type"	=> intval($_POST["pay_type"]),
				"card_num"	=> $_POST["card_num"],
				"exp_date"	=> $_POST["exp_date"],
				"status"	=> "", // To ensure consistency later
			);
			foreach ((array)module('shop')->_b_fields as $_field) {
				$order_sql[$_field] = $_POST[$_field];
			}
			/* foreach ((array)module('shop')->_s_fields as $_field) {
				$order_sql[$_field] = $_POST[$_field];
			} */
			db()->INSERT(db('shop_orders'), $order_sql);
			$ORDER_ID = intval(db()->INSERT_ID());
			// Insert items into db
			$total_price = 0;
			foreach ((array)$products_infos as $_info) {
				$_product_id = $_info["id"];
				$_info["_group_price"] = $group_prices[$_product_id][module('shop')->USER_GROUP];
				$quantity = $cart[$_info["id"]]["quantity"];
				$price = module('shop')->_get_product_price($_info);

				$dynamic_atts = array();
				foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
					if ($cart[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
						$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
						$_atts_to_save[$_attr_id] = $_attr_id;
						$price += $_attr_info["price"];
					}
				}
				$total_price += $price * $quantity;
				// Insert order into db
				db()->INSERT(db('shop_order_items'), array(
					"order_id"		=> intval($ORDER_ID),
					"product_id"	=> intval($_info["id"]),
					"user_id"		=> intval(module('shop')->USER_ID),
					"quantity"		=> intval($quantity),
					"sum"			=> floatval($price * $quantity),
					"attributes"	=> _es(serialize($_atts_to_save)),
				));
			}
			$total_price += (float)module('shop')->_ship_types[$_POST["ship_type"]]["price"];
			// Update order
			db()->UPDATE(db('shop_orders'), array(
				"status"		=> "pending",
				"total_sum"	=> floatval($total_price),
				"hash"			=> md5(microtime(true)."#".module('shop')->USER_ID."#".$total_price),
			), "id=".intval($ORDER_ID));
		}
		if (!common()->_error_exists()) {
			return $ORDER_ID;
		}
		return false;
	}
	
}
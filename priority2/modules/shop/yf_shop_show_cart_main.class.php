<?php
class yf_shop_show_cart_main{

	/**
	* show_cart_main
	*/
	function show_cart_main() {
		$cart = &$_SESSION["SHOP_CART"];

		$products_ids = array();
		foreach ((array)$cart as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM ".db('shop_products')." WHERE active='1' AND id IN(".implode(",", $products_ids).")");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
			$group_prices	= module('shop')->_get_group_prices($products_ids);
		}
		$total_price = 0;
		foreach ((array)$products_infos as $_info) {
			$_product_id = $_info["id"];
			$_info["_group_price"] = $group_prices[$_product_id][module('shop')->USER_GROUP];
			$quantity2 = $cart[$_info["id"]]["quantity"];
			$price = module('shop')->_get_product_price($_info);
			$dynamic_atts = array();
			foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
				if ($cart[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}
			$total_price += $price * $quantity2;
			$quantity += intval($quantity2);
		}
		$replace = array(
			"total_price"	=> module('shop')->_format_price($total_price),
			"currency"		=> _prepare_html(module('shop')->CURRENCY),
			"quantity"		=> $quantity,
			"order_link"	=> "./?object=shop&action=cart",
			"cart_link"		=> "./?object=shop&action=cart",
		
		);
		return tpl()->parse("shop/show_cart_main", $replace);
	}
	
}
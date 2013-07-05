<?php
class yf_shop__order_step_start{

	/**
	* Order step
	*/
	function _order_step_start($FORCE_DISPLAY_FORM = false) {
		$basket = &$_SESSION["SHOP_basket"];

		module('shop')->_basket_save();

		$products_ids = array();
		foreach ((array)$basket as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM ".db('shop_products')." WHERE id IN(".implode(",", $products_ids).") AND active='1'");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
			$group_prices	= module('shop')->_get_group_prices($products_ids);
		}
		$total_price = 0;
		foreach ((array)$products_infos as $_info) {
			$_product_id = $_info["id"];
			$_info["_group_price"] = $group_prices[$_product_id][module('shop')->USER_GROUP];
			$quantity = $basket[$_info["id"]]["quantity"];
			$price = module('shop')->_get_product_price($_info);

			$dynamic_atts = array();
			foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
				if ($basket[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}

			$URL_PRODUCT_ID = module('shop')->_product_id_url($_info);

			$products[$_info["id"]] = array(
				"name"			=> _prepare_html($_info["name"]),
				"price"			=> module('shop')->_format_price($price),
				"currency"		=> _prepare_html(module('shop')->CURRENCY),
				"quantity"		=> intval($quantity),
				"details_link"	=> process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"		=> _prepare_html(module('shop')->_shop_cats[$_info["cat_id"]]),
				"cat_url"		=> process_url("./?object=shop&action=products_show&id=".(module('shop')->_shop_cats_all[$_info["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$replace = array(
			"products"		=> $products,
			"total_price"	=> module('shop')->_format_price($total_price),
			"currency"		=> _prepare_html(module('shop')->CURRENCY),
			"back_link"		=> "./?object=shop&action=basket",
			"next_link"		=> "./?object=shop&action=order&id=delivery",
			"cats_block"	=> module('shop')->_show_shop_cats(),
		);
		return tpl()->parse("shop/order_start", $replace);
	}
	
}
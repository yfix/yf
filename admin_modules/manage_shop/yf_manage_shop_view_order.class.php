<?php
class yf_manage_shop_view_order{

	function view_order() {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($order_info)) {
			return _e("No such order");
		}
		if (!empty($_POST["status"])) {
			db()->UPDATE(db('shop_orders'), array(
				"status"	=> _es($_POST["status"]),
				"comment_m"	=> _es($_POST["comment_m"]),
				"comment_c"	=> _es($_POST["comment_c"]),
				"address"	=> _es($_POST["address"]),
				"phone"	=> _es($_POST["phone"]),
			), "id=".intval($_GET["id"]));
			return js_redirect("./?object=manage_shop&action=show_orders");
		}
		$products_ids = array();
		$Q = db()->query("SELECT * FROM ".db('shop_order_items')." WHERE `order`_id=".intval($order_info["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
			$order_items[$_info["product_id"]] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM ".db('shop_products')." WHERE id IN(".implode(",", $products_ids).") AND active='1'");
			$products_atts	= module('manage_shop')->_get_products_attributes($products_ids);
		}
		foreach ((array)$order_items as $_info) {
			$_product = $products_infos[$_info["product_id"]];
			$dynamic_atts = array();
			if (strlen($_info["attributes"]) > 3) {
				foreach ((array)unserialize($_info["attributes"]) as $_attr_id) {
					$_attr_info = $products_atts[$_info["product_id"]][$_attr_id];
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}
			$products[$_info["product_id"]] = array(
				"name"			=> _prepare_html($_product["name"]),
				"price"			=> module('manage_shop')->_format_price($_info["sum"]),
				"currency"	=> _prepare_html(module('manage_shop')->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"details_link"	=> process_url("./?object=manage_shop&action=view&id=".$_product["id"]),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];
		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			"form_action"		=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"order_id"				=> $order_info["id"],
			"total_sum"			=> module('manage_shop')->_format_price($order_info["total_sum"]),
			"user_link"			=> _profile_link($order_info["user_id"]),
			"user_name"		=> _display_name(user($order_info["user_id"])),
			"error_message"	=> _e(),
			"products"			=> (array)$products,
			"total_price"			=> module('manage_shop')->_format_price($total_price),
			"ship_type"			=> module('manage_shop')->_ship_types[$order_info["ship_type"]],
			"pay_type"			=> module('manage_shop')->_pay_types[$order_info["pay_type"]],
			"date"					=> _format_date($order_info["date"], "long"),
			"status_box"			=> module('manage_shop')->_box("status", $order_info["status"]),
			"back_url"				=> "./?object=manage_shop&action=show_orders",
			"print_url"				=> "./?object=manage_shop&action=show_print&id=".$order_info["id"],
			
		));
		return tpl()->parse("manage_shop/order_view", $replace);
	}
	
}
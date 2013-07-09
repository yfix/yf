<?php
class yf_manage_shop_show_print{

	function show_print() {
		if (isset($_GET["page"])) {
			$_GET["id"] = intval($_GET["page"]);
			unset($_GET["page"]);
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"])." AND user_id=".intval(module('manage_shop')->USER_ID));
		}
		if (empty($order_info)) {
			return _e("No such order");
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
				"currency"		=> _prepare_html(module('manage_shop')->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if (main()->USER_ID) {
			$order_info = my_array_merge(module('manage_shop')->_user_info, $order_info);
		} else {
			$order_info["email"] = $order_info["email"];
			$order_info["phone"] = $order_info["phone"];
		}
		$order_info = my_array_merge(module('manage_shop')->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"			=> $_GET["id"],
			"products"		=> $products,
			"ship_cost"		=> module('manage_shop')->_format_price(0),
			"total_cost"	=> module('manage_shop')->_format_price($total_price),
			"password"		=> "", // Security!
		));
		// Prepare email template
		$message = tpl()->parse("shop/invoice_email", $replace2);

		common()->quick_send_mail($order_info["email"], "invoice #".$_GET["id"], $message); 

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"		=> $products,
			"ship_price"	=> module('manage_shop')->_format_price(module('manage_shop')->_ship_types_names[$order_info["ship_type"]]),
			"total_price"	=> module('manage_shop')->_format_price($total_price),
			"order_no"		=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"			=> _prepare_html($order_info["hash"]),
			"back_link"		=> "./?object=manage_shop&action=show_orders",
		));
		return tpl()->parse("manage_shop/order_print_invoice", $replace);
	}
	
}
<?php
class yf_shop__order_step_finish{

	/**
	* Order step
	*/
	function _order_step_finish($FORCE_DISPLAY_FORM = false) {
		module('shop')->_basket_api()->clean();

		if (isset($_GET["page"])) {
			$_GET["id"] = intval($_GET["page"]);
			unset($_GET["page"]);
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"])." AND user_id=".intval(module('shop')->USER_ID));
		}
		if (empty($order_info)) {
			return _e("No such order");
		}
		$products_ids = array();
		$Q = db()->query("SELECT * FROM ".db('shop_order_items')." WHERE `order_id`=".intval($order_info["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
			$order_items[$_info["product_id"]] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM ".db('shop_products')." WHERE id IN(".implode(",", $products_ids).") AND active='1'");
			$products_atts	= module('shop')->_products_get_attributes($products_ids);
		}
		foreach ((array)$order_items as $_info) {
			$_product_id = $_info["product_id"];
			$_product = $products_infos[$_product_id];
			$price = $_info["sum"];

			$dynamic_atts = array();
			if (strlen($_info["attributes"]) > 3) {
				foreach ((array)unserialize($_info["attributes"]) as $_attr_id) {
					$_attr_info = $products_atts[$_info["product_id"]][$_attr_id];
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}

			$URL_PRODUCT_ID = module('shop')->_product_id_url($_product);

			$products[$_info["product_id"]] = array(
				"name"			=> _prepare_html($_product["name"]),
				"price"			=> module('shop')->_format_price($price),
				"sum"			=> module('shop')->_format_price($_info["sum"]),
				"currency"		=> _prepare_html(module('shop')->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"details_link"	=> process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"		=> _prepare_html(module('shop')->_shop_cats[$_product["cat_id"]]),
				"cat_url"		=> process_url("./?object=shop&action=products_show&id=".(module('shop')->_shop_cats_all[$_product["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if(main()->USER_ID) {
			$order_info = my_array_merge(module('shop')->_user_info, $order_info);
		}else {
			$order_info ["email"]= $order_info["email"];
			$order_info ["phone"]= $order_info["phone"];
		}
		$order_info = my_array_merge(module('shop')->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"		=> $_GET["id"],
			"products"	=> $products,
			"ship_cost"	=> module('shop')->_format_price(0),
			"total_cost"=> module('shop')->_format_price($total_price),
			"password"	=> "", // Security!
		));
		// Prepare email template
		$message = tpl()->parse("shop/invoice_email", $replace2);

		common()->quick_send_mail($order_info["email"], "invoice #".$_GET["id"], $message); 

		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"		=> $products,
			"ship_price"	=> module('shop')->_format_price(module('shop')->_ship_types_names[$order_info["ship_type"]]),
			"total_price"	=> module('shop')->_format_price($total_price),
			"order_no"		=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"			=> _prepare_html($order_info["hash"]),
			"back_link"		=> "./?object=shop&action=show",
			"cats_block"	=> module('shop')->_categories_show(),
		));
		return tpl()->parse("shop/order_finish", $replace);
	}
	
}
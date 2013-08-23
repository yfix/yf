<?php
class yf_manage_shop_orders{

	/**
	*/
	function orders_manage() {
		return module("manage_shop")->show_orders();
	}

	/**
	*/
	function show_orders() {
		return table2('SELECT * FROM '.db('shop_orders'))
			->text('id')
			->date('date')
			->user('user_id')
			->text('total_sum')
			->btn_view('', './?object=manage_shop&action=view_order&id=%d')
			;
/*
		$sql = "SELECT * FROM ".db('shop_orders')."";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$orders_info = db()->query_fetch_all($sql.$add_sql);
		if (!empty($orders_info)) {
			foreach ((array)$orders_info as $v){
				$summ = $summ + $v["total_sum"];
				$user_ids[] = $v["user_id"];
			}
			$user_infos = user($user_ids);
		}
		foreach ((array)$orders_info as $v){
			$items[] = array(
				"order_id"	=> $v["id"],
				"date"		=> _format_date($v["date"], "long"),
				"sum"		=> module('manage_shop')->_format_price($v["total_sum"]),
				"user_link"	=> _profile_link($v["user_id"]),
				"user_name"	=> _display_name($user_infos[$v["user_id"]]),
				"status"	=> $v["status"],
				"delete_url"=> "./?object=manage_shop&action=delete_order&id=".$v["id"],
				"view_url"	=> "./?object=manage_shop&action=view_order&id=".$v["id"],
			);
		}
		$replace = array(
			"items"	=> (array)$items,
			"pages"	=> $pages,
			"summ"	=> module('manage_shop')->_format_price($summ),
			"total"	=> intval($total),
			"filter"=> module('manage_shop')->USE_FILTER ? module('manage_shop')->_show_filter() : "",
		);
		return tpl()->parse("manage_shop/order_main", $replace);
*/
	}

	/**
	*/
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

	/**
	*/
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
				"phone"		=> _es($_POST["phone"]),
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
				"currency"		=> _prepare_html(module('manage_shop')->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"details_link"	=> process_url("./?object=manage_shop&action=view&id=".$_product["id"]),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];
		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"order_id"		=> $order_info["id"],
			"total_sum"		=> module('manage_shop')->_format_price($order_info["total_sum"]),
			"user_link"		=> _profile_link($order_info["user_id"]),
			"user_name"		=> _display_name(user($order_info["user_id"])),
			"error_message"	=> _e(),
			"products"		=> (array)$products,
			"total_price"	=> module('manage_shop')->_format_price($total_price),
			"ship_type"		=> module('manage_shop')->_ship_types[$order_info["ship_type"]],
			"pay_type"		=> module('manage_shop')->_pay_types[$order_info["pay_type"]],
			"date"			=> _format_date($order_info["date"], "long"),
			"status_box"	=> module('manage_shop')->_box("status", $order_info["status"]),
			"back_url"		=> "./?object=manage_shop&action=show_orders",
			"print_url"		=> "./?object=manage_shop&action=show_print&id=".$order_info["id"],
		));
		return tpl()->parse("manage_shop/order_view", $replace);
	}

	/**
	*/
	function delete_order() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($order_info["id"])) {
			db()->query("DELETE FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM ".db('shop_order_items')." WHERE `order`_id=".intval($_GET["id"]));
			common()->admin_wall_add(array('shop order deleted: '.$_GET['id'], $_GET['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			$_GET["id"];
		} else {
			return js_redirect("./?object=manage_shop&action=show_orders");
		}
	}
	
}
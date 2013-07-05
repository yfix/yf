<?php

/**
* Shop order methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_shop_order {
	
	function _show_orders() {
		if (!main()->USER_ID) {
			if (!empty($_POST)) {
				module('shop')->validate_order_data();
				// Display next form if we have no errors
				if (!common()->_error_exists()) {
					return module('shop')->view_order(true);
				}
			}
			$items[] = array(
				"order_id"		=> $_POST["order_id"],
				"email"			=> $_POST["email"],
				"form_action"	=> "./?object=shop&action=show_orders",
				"back_link"		=> "./?object=shop",
			);
		} else {
			$sql = "SELECT * FROM `".db('shop_orders')."` WHERE `user_id`=".intval(main()->USER_ID);
			//$filter_sql = $this->PARENT_OBJ->USE_FILTER ? $this->PARENT_OBJ->_create_filter_sql() : "";
			$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `date` DESC ";
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$orders_info = db()->query_fetch_all($sql.$add_sql);
			if (!empty($orders_info)) {
				foreach ((array)$orders_info as $v){
					$user_ids[] = $v["user_id"];
				}
				$user_infos = user($user_ids);
			}
			foreach ((array)$orders_info as $v){
				if ($v["status"] == "pending" or $v["status"] == "pending payment" ){
					$del = "./?object=shop&action=delete_order&id=".$v["id"];
				} else {
					$del = "";
				}
				$items[] = array(
					"order_id"	=> $v["id"],
					"date"		=> _format_date($v["date"], "long"),
					"sum"		=> module('shop')->_format_price($v["total_sum"]),
					"user_link"	=> _profile_link($v["user_id"]),
					"user_name"	=> _display_name($user_infos[$v["user_id"]]),
					"status"	=> $v["status"],
					"delete_url"=> $del,
					"view_url"	=> "./?object=shop&action=view_order&id=".$v["id"],
				);
			}
		}
		$replace = array(
			"error_message"	=> _e(),
			"items"			=> (array)$items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"filter"		=> module('shop')->USE_FILTER ? module('shop')->_show_filter() : "",
		);
		return tpl()->parse("shop/order_show", $replace);
	}

	/**
	* validate order data for view order
	*/
	function _validate_order_data () {
		if (empty($_POST["order_id"] )) {
			_re(t("Order empty"));
		}
		$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_POST["order_id"]));
		if (empty($order_info)) {
			_re("No such order");
		}
		if (empty($_POST["email"] )) {
			_re(t("e-mail empty"));
		} elseif (!common()->email_verify($_POST["email"])) {
			_re(t("email  not  valid."));
		} elseif ($order_info["email"] != $_POST["email"]) {
			_re("The order has been issued on other name");
		}
	}
	
	/**
	* view orders
	*/
	function _view_order() {
		if ($_POST["order_id"]) {
			$_GET["id"] = intval($_POST["order_id"]);
		} else {
			$_GET["id"] = intval($_GET["id"]);
		}
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"]));
		}
		if (empty($order_info)) {
			return _e("No such order");
		}
		if (!empty($_POST["status"])) {
			db()->UPDATE(db('shop_orders'), array(
				"status"	=> _es($_POST["status"]),
			), "`id`=".intval($_GET["id"]));
			return js_redirect("./?object=shop&action=show_orders");
		}
		$products_ids = array();
		$Q = db()->query("SELECT * FROM `".db('shop_order_items')."` WHERE `order_id`=".intval($order_info["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
			$order_items[$_info["product_id"]] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
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
				"price"			=> module('shop')->_format_price($_info["sum"]),
				"currency"		=> _prepare_html(module('shop')->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"details_link"	=> process_url("./?object=shop&action=view&id=".$_product["id"]),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];

		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			"form_action"	=> "./?object=shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"order_id"		=> $order_info["id"],
			"total_sum"		=> module('shop')->_format_price($order_info["total_sum"]),
			"user_link"		=> _profile_link($order_info["user_id"]),
			"user_name"		=> _display_name(user($order_info["user_id"])),
			"error_message"	=> _e(),
			"products"		=> (array)$products,
			"total_price"	=> module('shop')->_format_price($total_price),
			"ship_type"		=> module('shop')->_ship_type[$order_info["ship_type"]],
			"pay_type"		=> module('shop')->_pay_types[$order_info["pay_type"]],
			"date"			=> _format_date($order_info["date"], "long"),
			"status_box"	=> module('shop')->_statuses[$order_info["status"]],
			"back_url"		=> "./?object=shop&action=show_orders",
		));
		return tpl()->parse("shop/order_view", $replace);
	}

	/**
	* Delete order
	*/
	function _delete_order() {
		$_GET["id"] = intval($_GET["id"]);
		// Get current info
		if (!empty($_GET["id"])) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete order
		if (!empty($order_info["id"])) {
			db()->query("DELETE FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM `".db('shop_order_items')."` WHERE `order_id`=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=shop&action=show_orders");
		}
	}

	/**
	* Order step
	*/
	function _order_step_start($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];

		module('shop')->_save_cart_all();

		$products_ids = array();
		foreach ((array)$cart as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
			$group_prices	= module('shop')->_get_group_prices($products_ids);
		}
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
				"cat_url"		=> process_url("./?object=shop&action=show_products&id=".(module('shop')->_shop_cats_all[$_info["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = array(
			"products"		=> $products,
			"total_price"	=> module('shop')->_format_price($total_price),
			"currency"		=> _prepare_html(module('shop')->CURRENCY),
			"back_link"		=> "./?object=shop&action=cart",
			"next_link"		=> "./?object=shop&action=order&id=delivery",
			"cats_block"	=> module('shop')->_show_shop_cats(),
		);
		return tpl()->parse("shop/order_".$SELF_METHOD_ID, $replace);
	}

	/**
	* Order step
	*/
	function _order_step_delivery($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Validate previous form
		if (!empty($_POST) && !$FORCE_DISPLAY_FORM) {
			module('shop')->_order_validate_delivery();
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				return module('shop')->_order_step_select_payment(true);
			}
		}
		if($this->USER_ID) {
			$order_info = module('shop')->_user_info;
		}
		// Fill fields
		foreach ((array)module('shop')->_b_fields as $_field) {
			$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : module('shop')->_user_info[substr($_field, 2)]);
					
		}
		// Fill shipping from billing
	/* 	foreach ((array)module('shop')->_s_fields as $_field) {
			if (module('shop')->_user_info["shipping_same"] && !isset($_POST[$_field])) {
				$s_field = "b_".substr($_field, 2);
				$replace[$_field] = _prepare_html(isset($_POST[$s_field]) ? $_POST[$s_field] : module('shop')->_user_info[$s_field]);
			} else {
				$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : module('shop')->_user_info[$_field]);
			}
		} */
		$force_ship_type = module('shop')->FORCE_GROUP_SHIP[module('shop')->USER_GROUP];

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));

		$replace = my_array_merge((array)$replace, array(
			"form_action"	=> "./?object=shop&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"ship_type_box"	=> module('shop')->_box("ship_type", $force_ship_type ? $force_ship_type : $_POST["ship_type"]),
			"back_link"		=> "./?object=shop&action=order",
			"cats_block"	=> module('shop')->_show_shop_cats(),
		));
		return tpl()->parse("shop/order_delivery2", $replace);
	}

	/**
	* Order validation
	*/
	function _order_validate_delivery() {
		$_POST['exp_date'] = $_POST['exp_date_mm']. $_POST['exp_date_yy'];

		$force_ship_type = module('shop')->FORCE_GROUP_SHIP[module('shop')->USER_GROUP];
		if ($force_ship_type) {
			$_POST["ship_type"] = $force_ship_type;
		}
		if (!strlen($_POST["ship_type"]) || !isset(module('shop')->_ship_types[$_POST["ship_type"]])) {
			_re("Shipping type required");
		}
		foreach ((array)module('shop')->_b_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, module('shop')->_required_fields)) {
				_re(t(str_replace("b_", "Billing ", $_field))." ".t("is required"));
			}
		}
		if ($_POST["email"] != "" && !common()->email_verify($_POST["email"])) {
			_re(t("email  not  valid."));
		}
		/* foreach ((array)module('shop')->_s_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, module('shop')->_required_fields)) {
				_re(t(str_replace("s_", "Shipping ", $_field))." ".t("is required"));
			}
		}
		if (!common()->email_verify($_POST["s_email"])) {
				_re(t("Shipping email  not  valid."));
			} */
	
	}

	/**
	* Order step
	*/
	function _order_step_select_payment($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Show previous form if needed
		if (common()->_error_exists() || empty($_POST)) {
			return module('shop')->_order_step_delivery();
		}
		if (module('shop')->FORCE_PAY_METHOD) {
			$_POST["pay_type"] = module('shop')->FORCE_PAY_METHOD;
			$FORCE_DISPLAY_FORM = false;
		}
		if (!empty($_POST) && !$FORCE_DISPLAY_FORM) {
			module('shop')->_order_validate_select_payment();
			// Verify products
			if (!common()->_error_exists()) {
				$ORDER_ID = module('shop')->_create_order_record();
				$ORDER_ID = intval($ORDER_ID);
			}
			// Order id is required to continue, check it again
			if (empty($ORDER_ID) && !common()->_error_exists()) {
				_re("SHOP: Error while creating order, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				module('shop')->_CUR_ORDER_ID = $ORDER_ID;
				return module('shop')->_order_step_do_payment(true);
			}
		}
		$DATA = $_POST;
		if (!isset($DATA["pay_type"])) {
			$DATA["pay_type"] = key(module('shop')->_pay_types);
		}
		$hidden_fields = "";
		$hidden_fields .= module('shop')->_hidden_field("ship_type", $_POST["ship_type"]);
		foreach ((array)module('shop')->_b_fields as $_field) {
			$hidden_fields .= module('shop')->_hidden_field($_field, $_POST[$_field]);
		}
		/* foreach ((array)module('shop')->_s_fields as $_field) {
			$hidden_fields .= module('shop')->_hidden_field($_field, $_POST[$_field]);
		} */
		$hidden_fields .= module('shop')->_hidden_field('card_num', $_POST['card_num']);
		$hidden_fields .= module('shop')->_hidden_field('exp_date', $_POST['exp_date']);
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = array(
			"form_action"			=> "./?object=shop&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"pay_type_box"		=> module('shop')->_box("pay_type", $DATA["pay_type"]),
			"hidden_fields"		=> $hidden_fields,
			"back_link"				=> "./?object=shop&action=order&id=delivery",
			"cats_block"			=> module('shop')->_show_shop_cats(),
		);
		return tpl()->parse("shop/order_".$SELF_METHOD_ID, $replace);
	}

	/**
	* Order validation
	*/
	function _order_validate_select_payment() {

		module('shop')->_order_validate_delivery();

		if (!$_POST["pay_type"] || !isset(module('shop')->_pay_types[$_POST["pay_type"]])) {
			_re("Wrong payment type");
		}
	}

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
				$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
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
				"date"				=> time(),
				"user_id"			=> intval(module('shop')->USER_ID),
				"ship_type"		=> intval($_POST["ship_type"]),
				"pay_type"		=> intval($_POST["pay_type"]),
				"card_num"	=> $_POST["card_num"],
				"exp_date"		=> $_POST["exp_date"],
				"status"			=> "", // To ensure consistency later
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
					"user_id"			=> intval(module('shop')->USER_ID),
					"quantity"		=> intval($quantity),
					"sum"				=> floatval($price * $quantity),
					"attributes"		=> _es(serialize($_atts_to_save)),
				));
			}
			$total_price += (float)module('shop')->_ship_types[$_POST["ship_type"]]["price"];
			// Update order
			db()->UPDATE(db('shop_orders'), array(
				"status"		=> "pending",
				"total_sum"	=> floatval($total_price),
				"hash"			=> md5(microtime(true)."#".module('shop')->USER_ID."#".$total_price),
			), "`id`=".intval($ORDER_ID));
		}
		if (!common()->_error_exists()) {
			return $ORDER_ID;
		}
		return false;
	}

	/**
	* Order step
	*/
	function _order_step_do_payment($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];

		if (module('shop')->FORCE_PAY_METHOD) {
			$_POST["pay_type"] = module('shop')->FORCE_PAY_METHOD;
		}
		// Show previous form if needed
		if (common()->_error_exists() || empty($_POST)) {
			return module('shop')->_order_step_select_payment();
		}
		$ORDER_ID = intval($_POST["order_id"] ? $_POST["order_id"] : module('shop')->_CUR_ORDER_ID);
		if (empty($ORDER_ID)) {
			_re("Missing order ID");
		}
		// Get order info
		$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($ORDER_ID)." AND `user_id`=".intval(module('shop')->USER_ID)." AND `status`='pending'");
		if (empty($order_info["id"])) {
			_re("Missing order record");
		}
		// Payment by courier, skip next step
		if (!common()->_error_exists() && $_POST["pay_type"] == 1 or $_POST["pay_type"] == 3 or $_POST["pay_type"] == 4) {
			// Do empty shopping cart
			$cart = null;

			return js_redirect("./?object=shop&action=".$_GET["action"]."&id=finish&page=".intval($ORDER_ID));
		}
		// Authorize.net payment type
		if ($_POST["pay_type"] == 2) {
			// Do empty shopping cart
			$cart = null;

			return module('shop')->_order_pay_authorize_net($order_info);
		}

	}

	/**
	* Order validation
	*/
	function _order_validate_do_payment() {

		module('shop')->_order_validate_select_payment();

		if (empty($_POST["order_id"])) {
			_re("Missing order ID");
		}
		if (empty($_POST["total_sum"])) {
			_re("Missing total sum");
		}
	}

	/**
	* Order confirmation
	*/
/* 	function _order_confirmation () {
		$cart = &$_SESSION["SHOP_CART"];
		// Do empty shopping cart
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
				$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
				$products_atts	= module('shop')->_get_products_attributes($products_ids);
				$group_prices	= module('shop')->_get_group_prices($products_ids);
			}
			if (empty($products_infos)) {
				return _re("SHOP: Wrong products, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
		}
		
		
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
			$order_items[$_info["product_id"]] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
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
				"name"				=> _prepare_html($_product["name"]),
				"price"					=> module('shop')->_format_price($price),
				"sum"					=> module('shop')->_format_price($_info["sum"]),
				"currency"			=> _prepare_html(module('shop')->CURRENCY),
				"quantity"			=> intval($_info["quantity"]),
				"details_link"		=> process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"			=> _prepare_html(module('shop')->_shop_cats[$_product["cat_id"]]),
				"cat_url"				=> process_url("./?object=shop&action=show_products&id=".(module('shop')->_shop_cats_all[$_product["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if($this->USER_ID) {
			$order_info = my_array_merge(module('shop')->_user_info, $order_info);
		}else {
			$order_info ["email"]= $order_info["b_email"];
			$order_info ["phone"]= $order_info["b_phone"];
		}
		$order_info = my_array_merge(module('shop')->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"		=> $_GET["id"],
			"products"		=> $products,
			"ship_cost"		=> module('shop')->_format_price(0),
			"total_cost"		=> module('shop')->_format_price($total_price),
			"password"		=> "", // Security!
		));
		
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"				=> $products,
			"ship_price"			=> module('shop')->_format_price(module('shop')->_ship_types_names[$order_info["ship_type"]]),
			"total_price"			=> module('shop')->_format_price($total_price),
			"order_no"				=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"						=> _prepare_html($order_info["hash"]),
			"back_link"				=> "./?object=shop&action=show",
			"cats_block"			=> module('shop')->_show_shop_cats(),
		));
		return tpl()->parse("shop/order_".$SELF_METHOD_ID, $replace);
	}
		
	}
	 */
	
	
	/**
	* Order step
	*/
	function _order_step_finish($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Do empty shopping cart
		$cart = null;
		if (isset($_GET["page"])) {
			$_GET["id"] = intval($_GET["page"]);
			unset($_GET["page"]);
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"])." AND `user_id`=".intval(module('shop')->USER_ID));
		}
		if (empty($order_info)) {
			return _e("No such order");
		}
		$products_ids = array();
		$Q = db()->query("SELECT * FROM `".db('shop_order_items')."` WHERE `order_id`=".intval($order_info["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
			$order_items[$_info["product_id"]] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
			$products_atts	= module('shop')->_get_products_attributes($products_ids);
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
				"cat_url"		=> process_url("./?object=shop&action=show_products&id=".(module('shop')->_shop_cats_all[$_product["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if($this->USER_ID) {
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

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"		=> $products,
			"ship_price"	=> module('shop')->_format_price(module('shop')->_ship_types_names[$order_info["ship_type"]]),
			"total_price"	=> module('shop')->_format_price($total_price),
			"order_no"		=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"			=> _prepare_html($order_info["hash"]),
			"back_link"		=> "./?object=shop&action=show",
			"cats_block"	=> module('shop')->_show_shop_cats(),
		));
		return tpl()->parse("shop/order_".$SELF_METHOD_ID, $replace);
	}
}

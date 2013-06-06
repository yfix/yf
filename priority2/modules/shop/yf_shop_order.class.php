<?php

/**
* Shop order methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_shop_order {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->SHOP_OBJ		= module(SHOP_CLASS_NAME);
		
	}
	
	function _show_orders() {
		if (!$_SESSION ["user_id"]) {
			if (!empty($_POST) ) {
				$this->SHOP_OBJ->validate_order_data();
				// Display next form if we have no errors
				if (!common()->_error_exists()) {
					return $this->SHOP_OBJ->view_order(true);
				}
				
			}
			$items[] = array(
				"order_id"			=> $_POST["order_id"],
				"email"				=> $_POST["email"],
				"form_action"			=> "./?object=".$_GET["object"]."&action=show_orders",
				"back_link"				=> "./?object=".SHOP_CLASS_NAME,
			);
		} else {
			$sql = "SELECT * FROM `".db('shop_orders')."` WHERE `user_id` = ".$_SESSION ["user_id"];
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
					$del = "./?object=".$_GET["object"]."&action=delete_order&id=".$v["id"];
				} else {
					$del = "";
				}
				$items[] = array(
					"order_id"			=> $v["id"],
					"date"					=> _format_date($v["date"], "long"),
					"sum"					=> $this->SHOP_OBJ->_format_price($v["total_sum"]),
					"user_link"			=> _profile_link($v["user_id"]),
					"user_name"		=> _display_name($user_infos[$v["user_id"]]),
					"status"				=> $v["status"],
					"delete_url"		=> $del,
					"view_url"			=> "./?object=".$_GET["object"]."&action=view_order&id=".$v["id"],
				);
			}
		}
		$replace = array(
				"error_message"	=> _e(),
				"items"		=> (array)$items,
				"pages"		=> $pages,
				"total"			=> intval($total),
				"filter"			=> $this->SHOP_OBJ->USE_FILTER ? $this->SHOP_OBJ->_show_filter() : "",
			);
		return tpl()->parse(SHOP_CLASS_NAME."/order_show", $replace);
	}

	/**
	* validate order data for view order
	*/
	function _validate_order_data () {
		if (empty($_POST["order_id"] )) {
				common()->_raise_error(t("Order empty"));
		}
		$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_POST["order_id"]));
		if (empty($order_info)) {
				common()->_raise_error("No such order");
		}
		if (empty($_POST["email"] )) {
				common()->_raise_error(t("e-mail empty"));
		}else if ( !common()->email_verify($_POST["email"])) {
				common()->_raise_error(t("email  not  valid."));
		} else if ($order_info["email"] != $_POST["email"]) {
			common()->_raise_error("The order has been issued on other name");
			
		}
	}
	
	/**
	* view orders
	*/
	function _view_order() {
		if  ($_POST["order_id"]) {
			$_GET["id"] = intval($_POST["order_id"]);
		}else {
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
			return js_redirect("./?object=".$_GET["object"]."&action=show_orders");
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
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
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
				"price"				=> $this->SHOP_OBJ->_format_price($_info["sum"]),
				"currency"		=> _prepare_html($this->SHOP_OBJ->CURRENCY),
				"quantity"		=> intval($_info["quantity"]),
				"details_link"	=> process_url("./?object=".$_GET["object"]."&action=view&id=".$_product["id"]),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];

		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"order_id"				=> $order_info["id"],
			"total_sum"				=> $this->SHOP_OBJ->_format_price($order_info["total_sum"]),
			"user_link"				=> _profile_link($order_info["user_id"]),
			"user_name"			=> _display_name(user($order_info["user_id"])),
			"error_message"	=> _e(),
			"products"				=> (array)$products,
			"total_price"			=> $this->SHOP_OBJ->_format_price($total_price),
			"ship_type"				=> $this->SHOP_OBJ->_ship_type[$order_info["ship_type"]],
			"pay_type"				=> $this->SHOP_OBJ->_pay_types[$order_info["pay_type"]],
			"date"						=> _format_date($order_info["date"], "long"),
			"status_box"			=> $this->SHOP_OBJ->_statuses[$order_info["status"]],
			"back_url"				=> "./?object=".$_GET["object"]."&action=show_orders",
		));
		return tpl()->parse($_GET["object"]."/order_view", $replace);
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
			return js_redirect("./?object=".$_GET["object"]."&action=show_orders");
		}
	}

	/**
	* Order step
	*/
	function _order_step_start($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];

		$this->SHOP_OBJ->_save_cart_all();

		$products_ids = array();
		foreach ((array)$cart as $_item_id => $_info) {
			if ($_info["product_id"]) {
				$products_ids[$_info["product_id"]] = $_info["product_id"];
			}
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all("SELECT * FROM `".db('shop_products')."` WHERE `id` IN(".implode(",", $products_ids).") AND `active`='1'");
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
			$group_prices	= $this->SHOP_OBJ->_get_group_prices($products_ids);
		}
		$total_price = 0;
		foreach ((array)$products_infos as $_info) {
			$_product_id = $_info["id"];
			$_info["_group_price"] = $group_prices[$_product_id][$this->SHOP_OBJ->USER_GROUP];
			$quantity = $cart[$_info["id"]]["quantity"];
			$price = $this->SHOP_OBJ->_get_product_price($_info);

			$dynamic_atts = array();
			foreach ((array)$products_atts[$_product_id] as $_attr_id => $_attr_info) {
				if ($cart[$_product_id]["atts"][$_attr_info["name"]] == $_attr_info["value"]) {
					$dynamic_atts[$_attr_id] = "- ".$_attr_info["name"]." ".$_attr_info["value"];
					$price += $_attr_info["price"];
				}
			}

			$URL_PRODUCT_ID = $this->SHOP_OBJ->_product_id_url($_info);

			$products[$_info["id"]] = array(
				"name"				=> _prepare_html($_info["name"]),
				"price"					=> $this->SHOP_OBJ->_format_price($price),
				"currency"			=> _prepare_html($this->SHOP_OBJ->CURRENCY),
				"quantity"			=> intval($quantity),
				"details_link"		=> process_url("./?object=".$_GET["object"]."&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"			=> _prepare_html($this->SHOP_OBJ->_shop_cats[$_info["cat_id"]]),
				"cat_url"				=> process_url("./?object=".$_GET["object"]."&action=show_products&id=".($this->SHOP_OBJ->_shop_cats_all[$_info["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = array(
			"products"		=> $products,
			"total_price"	=> $this->SHOP_OBJ->_format_price($total_price),
			"currency"		=> _prepare_html($this->SHOP_OBJ->CURRENCY),
			"back_link"		=> "./?object=".SHOP_CLASS_NAME."&action=cart",
			"next_link"		=> "./?object=".SHOP_CLASS_NAME."&action=order&id=delivery",
			"cats_block"	=> $this->SHOP_OBJ->_show_shop_cats(),
		);
		return tpl()->parse(SHOP_CLASS_NAME."/order_".$SELF_METHOD_ID, $replace);
	}

	/**
	* Order step
	*/
	function _order_step_delivery($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Validate previous form
		if (!empty($_POST) && !$FORCE_DISPLAY_FORM) {
			$this->SHOP_OBJ->_order_validate_delivery();
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				return $this->SHOP_OBJ->_order_step_select_payment(true);
			}
		}
		if($this->USER_ID) {
			$order_info = $this->SHOP_OBJ->_user_info;
		}
		// Fill fields
		foreach ((array)$this->SHOP_OBJ->_b_fields as $_field) {
			$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : $this->SHOP_OBJ->_user_info[substr($_field, 2)]);
					
		}
		// Fill shipping from billing
	/* 	foreach ((array)$this->SHOP_OBJ->_s_fields as $_field) {
			if ($this->SHOP_OBJ->_user_info["shipping_same"] && !isset($_POST[$_field])) {
				$s_field = "b_".substr($_field, 2);
				$replace[$_field] = _prepare_html(isset($_POST[$s_field]) ? $_POST[$s_field] : $this->SHOP_OBJ->_user_info[$s_field]);
			} else {
				$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : $this->SHOP_OBJ->_user_info[$_field]);
			}
		} */
		$force_ship_type = $this->SHOP_OBJ->FORCE_GROUP_SHIP[$this->SHOP_OBJ->USER_GROUP];

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));

		$replace = my_array_merge((array)$replace, array(
			"form_action"	=> "./?object=".SHOP_CLASS_NAME."&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"ship_type_box"	=> $this->SHOP_OBJ->_box("ship_type", $force_ship_type ? $force_ship_type : $_POST["ship_type"]),
			"back_link"				=> "./?object=".SHOP_CLASS_NAME."&action=order",
			"cats_block"			=> $this->SHOP_OBJ->_show_shop_cats(),
		));
		return tpl()->parse(SHOP_CLASS_NAME."/order_delivery2", $replace);
	}

	/**
	* Order validation
	*/
	function _order_validate_delivery() {
		$_POST['exp_date'] = $_POST['exp_date_mm']. $_POST['exp_date_yy'];

		$force_ship_type = $this->SHOP_OBJ->FORCE_GROUP_SHIP[$this->SHOP_OBJ->USER_GROUP];
		if ($force_ship_type) {
			$_POST["ship_type"] = $force_ship_type;
		}
		if (!strlen($_POST["ship_type"]) || !isset($this->SHOP_OBJ->_ship_types[$_POST["ship_type"]])) {
			common()->_raise_error("Shipping type required");
		}
		foreach ((array)$this->SHOP_OBJ->_b_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, $this->SHOP_OBJ->_required_fields)) {
				common()->_raise_error(t(str_replace("b_", "Billing ", $_field))." ".t("is required"));
			}
			
		}
		if ($_POST["email"] != "" && !common()->email_verify($_POST["email"])) {
				common()->_raise_error(t("email  not  valid."));
			}
		/* foreach ((array)$this->SHOP_OBJ->_s_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, $this->SHOP_OBJ->_required_fields)) {
				common()->_raise_error(t(str_replace("s_", "Shipping ", $_field))." ".t("is required"));
			}
		}
		if (!common()->email_verify($_POST["s_email"])) {
				common()->_raise_error(t("Shipping email  not  valid."));
			} */
	
	}

	/**
	* Order step
	*/
	function _order_step_select_payment($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Show previous form if needed
		if (common()->_error_exists() || empty($_POST)) {
			return $this->SHOP_OBJ->_order_step_delivery();
		}
		if ($this->SHOP_OBJ->FORCE_PAY_METHOD) {
			$_POST["pay_type"] = $this->SHOP_OBJ->FORCE_PAY_METHOD;
			$FORCE_DISPLAY_FORM = false;
		}
		if (!empty($_POST) && !$FORCE_DISPLAY_FORM) {
			$this->SHOP_OBJ->_order_validate_select_payment();
			// Verify products
			if (!common()->_error_exists()) {
				$ORDER_ID = $this->SHOP_OBJ->_create_order_record();
				$ORDER_ID = intval($ORDER_ID);
			}
			// Order id is required to continue, check it again
			if (empty($ORDER_ID) && !common()->_error_exists()) {
				common()->_raise_error("SHOP: Error while creating order, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				$this->SHOP_OBJ->_CUR_ORDER_ID = $ORDER_ID;
				return $this->SHOP_OBJ->_order_step_do_payment(true);
			}
		}
		$DATA = $_POST;
		if (!isset($DATA["pay_type"])) {
			$DATA["pay_type"] = key($this->SHOP_OBJ->_pay_types);
		}
		$hidden_fields = "";
		$hidden_fields .= $this->SHOP_OBJ->_hidden_field("ship_type", $_POST["ship_type"]);
		foreach ((array)$this->SHOP_OBJ->_b_fields as $_field) {
			$hidden_fields .= $this->SHOP_OBJ->_hidden_field($_field, $_POST[$_field]);
		}
		/* foreach ((array)$this->SHOP_OBJ->_s_fields as $_field) {
			$hidden_fields .= $this->SHOP_OBJ->_hidden_field($_field, $_POST[$_field]);
		} */
		$hidden_fields .= $this->SHOP_OBJ->_hidden_field('card_num', $_POST['card_num']);
		$hidden_fields .= $this->SHOP_OBJ->_hidden_field('exp_date', $_POST['exp_date']);
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = array(
			"form_action"			=> "./?object=".SHOP_CLASS_NAME."&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"pay_type_box"		=> $this->SHOP_OBJ->_box("pay_type", $DATA["pay_type"]),
			"hidden_fields"		=> $hidden_fields,
			"back_link"				=> "./?object=".SHOP_CLASS_NAME."&action=order&id=delivery",
			"cats_block"			=> $this->SHOP_OBJ->_show_shop_cats(),
		);
		return tpl()->parse(SHOP_CLASS_NAME."/order_".$SELF_METHOD_ID, $replace);
	}

	/**
	* Order validation
	*/
	function _order_validate_select_payment() {

		$this->SHOP_OBJ->_order_validate_delivery();

		if (!$_POST["pay_type"] || !isset($this->SHOP_OBJ->_pay_types[$_POST["pay_type"]])) {
			common()->_raise_error("Wrong payment type");
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
				$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
				$group_prices	= $this->SHOP_OBJ->_get_group_prices($products_ids);
			}
			if (empty($products_infos)) {
				return common()->_raise_error("SHOP: Wrong products, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
		}
		// Save into database
		if (!common()->_error_exists()) {
			// Insert order into db
			$order_sql = array(
				"date"				=> time(),
				"user_id"			=> intval($this->SHOP_OBJ->USER_ID),
				"ship_type"		=> intval($_POST["ship_type"]),
				"pay_type"		=> intval($_POST["pay_type"]),
				"card_num"	=> $_POST["card_num"],
				"exp_date"		=> $_POST["exp_date"],
				"status"			=> "", // To ensure consistency later
			);
			foreach ((array)$this->SHOP_OBJ->_b_fields as $_field) {
				$order_sql[$_field] = $_POST[$_field];
			}
			/* foreach ((array)$this->SHOP_OBJ->_s_fields as $_field) {
				$order_sql[$_field] = $_POST[$_field];
			} */
			db()->INSERT(db('shop_orders'), $order_sql);
			$ORDER_ID = intval(db()->INSERT_ID());
			// Insert items into db
			$total_price = 0;
			foreach ((array)$products_infos as $_info) {
				$_product_id = $_info["id"];
				$_info["_group_price"] = $group_prices[$_product_id][$this->SHOP_OBJ->USER_GROUP];
				$quantity = $cart[$_info["id"]]["quantity"];
				$price = $this->SHOP_OBJ->_get_product_price($_info);

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
					"user_id"			=> intval($this->SHOP_OBJ->USER_ID),
					"quantity"		=> intval($quantity),
					"sum"				=> floatval($price * $quantity),
					"attributes"		=> _es(serialize($_atts_to_save)),
				));
			}
			$total_price += (float)$this->SHOP_OBJ->_ship_types[$_POST["ship_type"]]["price"];
			// Update order
			db()->UPDATE(db('shop_orders'), array(
				"status"		=> "pending",
				"total_sum"	=> floatval($total_price),
				"hash"			=> md5(microtime(true)."#".$this->SHOP_OBJ->USER_ID."#".$total_price),
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

		if ($this->SHOP_OBJ->FORCE_PAY_METHOD) {
			$_POST["pay_type"] = $this->SHOP_OBJ->FORCE_PAY_METHOD;
		}
		// Show previous form if needed
		if (common()->_error_exists() || empty($_POST)) {
			return $this->SHOP_OBJ->_order_step_select_payment();
		}
		$ORDER_ID = intval($_POST["order_id"] ? $_POST["order_id"] : $this->SHOP_OBJ->_CUR_ORDER_ID);
		if (empty($ORDER_ID)) {
			common()->_raise_error("Missing order ID");
		}
		// Get order info
		$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($ORDER_ID)." AND `user_id`=".intval($this->SHOP_OBJ->USER_ID)." AND `status`='pending'");
		if (empty($order_info["id"])) {
			common()->_raise_error("Missing order record");
		}
		// Payment by courier, skip next step
		if (!common()->_error_exists() && $_POST["pay_type"] == 1 or $_POST["pay_type"] == 3 or $_POST["pay_type"] == 4) {
			// Do empty shopping cart
			$cart = null;

			return js_redirect("./?object=".SHOP_CLASS_NAME."&action=".$_GET["action"]."&id=finish&page=".intval($ORDER_ID));
		}
		// Authorize.net payment type
		if ($_POST["pay_type"] == 2) {
			// Do empty shopping cart
			$cart = null;

			return $this->SHOP_OBJ->_order_pay_authorize_net($order_info);
		}

	}

	/**
	* Order validation
	*/
	function _order_validate_do_payment() {

		$this->SHOP_OBJ->_order_validate_select_payment();

		if (empty($_POST["order_id"])) {
			common()->_raise_error("Missing order ID");
		}
		if (empty($_POST["total_sum"])) {
			common()->_raise_error("Missing total sum");
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
				$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
				$group_prices	= $this->SHOP_OBJ->_get_group_prices($products_ids);
			}
			if (empty($products_infos)) {
				return common()->_raise_error("SHOP: Wrong products, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
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
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
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

			$URL_PRODUCT_ID = $this->SHOP_OBJ->_product_id_url($_product);

			$products[$_info["product_id"]] = array(
				"name"				=> _prepare_html($_product["name"]),
				"price"					=> $this->SHOP_OBJ->_format_price($price),
				"sum"					=> $this->SHOP_OBJ->_format_price($_info["sum"]),
				"currency"			=> _prepare_html($this->SHOP_OBJ->CURRENCY),
				"quantity"			=> intval($_info["quantity"]),
				"details_link"		=> process_url("./?object=".$_GET["object"]."&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"			=> _prepare_html($this->SHOP_OBJ->_shop_cats[$_product["cat_id"]]),
				"cat_url"				=> process_url("./?object=".$_GET["object"]."&action=show_products&id=".($this->SHOP_OBJ->_shop_cats_all[$_product["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if($this->USER_ID) {
			$order_info = my_array_merge($this->SHOP_OBJ->_user_info, $order_info);
		}else {
			$order_info ["email"]= $order_info["b_email"];
			$order_info ["phone"]= $order_info["b_phone"];
		}
		$order_info = my_array_merge($this->SHOP_OBJ->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"		=> $_GET["id"],
			"products"		=> $products,
			"ship_cost"		=> $this->SHOP_OBJ->_format_price(0),
			"total_cost"		=> $this->SHOP_OBJ->_format_price($total_price),
			"password"		=> "", // Security!
		));
		
		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"				=> $products,
			"ship_price"			=> $this->SHOP_OBJ->_format_price($this->SHOP_OBJ->_ship_types_names[$order_info["ship_type"]]),
			"total_price"			=> $this->SHOP_OBJ->_format_price($total_price),
			"order_no"				=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"						=> _prepare_html($order_info["hash"]),
			"back_link"				=> "./?object=".SHOP_CLASS_NAME."&action=show",
			"cats_block"			=> $this->SHOP_OBJ->_show_shop_cats(),
		));
		return tpl()->parse(SHOP_CLASS_NAME."/order_".$SELF_METHOD_ID, $replace);
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
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"])." AND `user_id`=".intval($this->SHOP_OBJ->USER_ID));
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
			$products_atts	= $this->SHOP_OBJ->_get_products_attributes($products_ids);
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

			$URL_PRODUCT_ID = $this->SHOP_OBJ->_product_id_url($_product);

			$products[$_info["product_id"]] = array(
				"name"				=> _prepare_html($_product["name"]),
				"price"					=> $this->SHOP_OBJ->_format_price($price),
				"sum"					=> $this->SHOP_OBJ->_format_price($_info["sum"]),
				"currency"			=> _prepare_html($this->SHOP_OBJ->CURRENCY),
				"quantity"			=> intval($_info["quantity"]),
				"details_link"		=> process_url("./?object=".$_GET["object"]."&action=product_details&id=".$URL_PRODUCT_ID),
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
				"cat_name"			=> _prepare_html($this->SHOP_OBJ->_shop_cats[$_product["cat_id"]]),
				"cat_url"				=> process_url("./?object=".$_GET["object"]."&action=show_products&id=".($this->SHOP_OBJ->_shop_cats_all[$_product["cat_id"]]['url'])),
			);
			$total_price += $price * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if($this->USER_ID) {
			$order_info = my_array_merge($this->SHOP_OBJ->_user_info, $order_info);
		}else {
			$order_info ["email"]= $order_info["email"];
			$order_info ["phone"]= $order_info["phone"];
		}
		$order_info = my_array_merge($this->SHOP_OBJ->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"		=> $_GET["id"],
			"products"		=> $products,
			"ship_cost"		=> $this->SHOP_OBJ->_format_price(0),
			"total_cost"		=> $this->SHOP_OBJ->_format_price($total_price),
			"password"		=> "", // Security!
		));
		
			// Prepare email template
		$message = tpl()->parse(SHOP_CLASS_NAME."/invoice_email", $replace2);

		common()->quick_send_mail($order_info["email"], "invoice #".$_GET["id"], $message); 

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"				=> $products,
			"ship_price"			=> $this->SHOP_OBJ->_format_price($this->SHOP_OBJ->_ship_types_names[$order_info["ship_type"]]),
			"total_price"			=> $this->SHOP_OBJ->_format_price($total_price),
			"order_no"				=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"						=> _prepare_html($order_info["hash"]),
			"back_link"				=> "./?object=".SHOP_CLASS_NAME."&action=show",
			"cats_block"			=> $this->SHOP_OBJ->_show_shop_cats(),
		));
		return tpl()->parse(SHOP_CLASS_NAME."/order_".$SELF_METHOD_ID, $replace);
	}
}

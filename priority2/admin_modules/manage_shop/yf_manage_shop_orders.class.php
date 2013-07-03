<?php

/**
* Manage shop sub module
*/
class yf_manage_shop_orders {

	/**
	* Constructor
	*/
	function _init () {
		if (module('manage_shop')->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	*/
	function show_orders() {
	
		$sql = "SELECT * FROM `".db('shop_orders')."`";
		$filter_sql = module('manage_shop')->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `date` DESC ";
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
				"order_id"			=> $v["id"],
				"date"				=> _format_date($v["date"], "long"),
				"sum"				=> module('manage_shop')->_format_price($v["total_sum"]),
				"user_link"		=> _profile_link($v["user_id"]),
				"user_name"	=> _display_name($user_infos[$v["user_id"]]),
				"status"			=> $v["status"],
				"delete_url"		=> "./?object=manage_shop&action=delete_order&id=".$v["id"],
				"view_url"			=> "./?object=manage_shop&action=view_order&id=".$v["id"],
			);
		}
		$replace = array(
			"items"		=> (array)$items,
			"pages"	=> $pages,
			"summ"		=> module('manage_shop')->_format_price($summ),
			"total"		=> intval($total),
			"filter"		=> module('manage_shop')->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse("manage_shop/order_main", $replace);
	}

	/**
	*/
	function view_order() {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"]));
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
			), "`id`=".intval($_GET["id"]));
			return js_redirect("./?object=manage_shop&action=show_orders");
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

	/**
	* print invoice
	*/
	function show_print() {
		
		$cart = &$_SESSION["SHOP_CART"];
		// Do empty shopping cart
		$cart = null;
		if (isset($_GET["page"])) {
			$_GET["id"] = intval($_GET["page"]);
			unset($_GET["page"]);
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"])." AND `user_id`=".intval(module('manage_shop')->USER_ID));
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
				"dynamic_atts"	=> !empty($dynamic_atts) ? implode("\n<br />", $dynamic_atts) : "",
			);
			$total_price += $_info["price"] * $quantity;
		}
		$total_price = $order_info["total_sum"];
		if($this->USER_ID) {
			$order_info = my_array_merge(module('manage_shop')->_user_info, $order_info);
		}else {
			$order_info ["email"]= $order_info["email"];
			$order_info ["phone"]= $order_info["phone"];
		}
		$order_info = my_array_merge(module('manage_shop')->COMPANY_INFO, $order_info);
		$replace2 = my_array_merge($order_info ,array(
			"id"		=> $_GET["id"],
			"products"		=> $products,
			"ship_cost"		=> module('manage_shop')->_format_price(0),
			"total_cost"		=> module('manage_shop')->_format_price($total_price),
			"password"		=> "", // Security!
		));
		// Prepare email template
		$message = tpl()->parse(SHOP_CLASS_NAME."/invoice_email", $replace2);

		common()->quick_send_mail($order_info["email"], "invoice #".$_GET["id"], $message); 

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));
		$replace = my_array_merge($replace2, array(
			"error_message"	=> _e(),
			"products"				=> $products,
			"ship_price"			=> module('manage_shop')->_format_price(module('manage_shop')->_ship_types_names[$order_info["ship_type"]]),
			"total_price"			=> module('manage_shop')->_format_price($total_price),
			"order_no"				=> str_pad($order_info["id"], 8, "0", STR_PAD_LEFT),
			"hash"						=> _prepare_html($order_info["hash"]),
			"back_link"				=> "./?object=manage_shop&action=show_orders",
			
		));
		return tpl()->parse("manage_shop/order_print_invoice", $replace);
	}

	/**
	*/
	function delete_order() {
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
			$_GET["id"];
		} else {
			return js_redirect("./?object=manage_shop&action=show_orders");
		}
	}

	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= "show_orders_filter";
		// Array of select boxes to process
		$this->_boxes = array(
			"status"				=> 'select_box("status",		module("manage_shop")->_statuses,	$selected, false, 2, "", false)',
			"sort_by"				=> 'select_box("sort_by",		 $this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"			=> 'select_box("sort_order", $this->_sort_orders,		$selected, 0, 2, "", false)',
			"orders_by_date"	=> 'select_box("orders_by_date", $this->_orders_by_date,		$selected, 0, 2, "", false)',
		);
		// Sort orders
		$this->_sort_orders = array( "" =>"", "DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			""					=> 	"",
			"id"				=> "Order ID",
			"user_id"		=> "User",
			"total_sum" 	=> "SUM",
			"quantity" 		=> "Quantity",
			"date" 			=> "Date",
			"status" 		=> "Status",
		);
		$this->_orders_by_date = array(
			""					=> 	"",
			"0"				=> "Today",
			"86400"		=> "Yesterday",
			"604800"		=> "Week",
			"2592000"	=> "month",
			"31536000" 	=> "Year",
			
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"id",
			"user",
			"sum_max",
			"sum_min",
			"status",
			"sort_by",
			"sort_order",
			"orders_by_date",
			"date_min",
			"date_max",
		);
	}

	/**
	*/
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		if ($SF["sum_min"]){
			$sql .= " AND `total_sum` >= ".intval($SF["sum_min"])." \r\n";
		}
		if ($SF["sum_max"])	{
			$sql .= " AND `total_sum` <= ".intval($SF["sum_max"])." \r\n";
		}
		if (strlen($SF["user"])){
		$sql .= " AND `user` LIKE '"._es($SF["user"])."%' \r\n";
		}
		if($SF["status"] ){
			$sql .= " AND `status` = '".$SF["status"]."' \r\n";
		}  
		$cur_date = time ();
		$cur_date =	date("d F", ($cur_date));
		$cur_date =	strtotime($cur_date);
		if($SF["orders_by_date"] == "0"){
			$SF["orders_by_date"];
			$date = $cur_date - $SF["orders_by_date"] ;
			$sql .= " AND `date` >= ".$date." \r\n";
		}elseif ($SF["orders_by_date"] == "86400"){
			$date = $cur_date - $SF["orders_by_date"] ;
			$sql .= " AND `date` >= ".$date." \r\n";
		}elseif ($SF["orders_by_date"] == "604800"){
			$date = $cur_date - $SF["orders_by_date"] ;
			$sql .= " AND `date` >= ".$date." \r\n";
		}elseif ($SF["orders_by_date"] == "2592000"){
			$date = $cur_date - $SF["orders_by_date"] ;
			$sql .= " AND `date` >= ".$date." \r\n";
		}elseif ($SF["orders_by_date"] == "31536000"){
			$date = $cur_date - $SF["orders_by_date"] ;
			$sql .= " AND `date` >= ".$date." \r\n";
		}
		// Sorting here
		if ($SF["sort_by"])	{
			$sql .= " ORDER BY  `" .$SF["sort_by"]."` \r\n";
		}
		if ($SF["sort_by"] && strlen($SF["sort_order"])) {
			$sql .= " ".$SF["sort_order"]." \r\n";
		}
		return substr($sql, 0, -3);
	}

	// Session - based filter
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=manage_shop&action=save_filter_order"._add_get(),
			"clear_url"		=> "./?object=manage_shop&action=clear_filter_order"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse("manage_shop/filter_order", $replace);
	}

	// Filter save method
	function save_filter ($silent = false) {
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name){
				$_SESSION[$this->_filter_name][$name] = $_POST[$name];
			}
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) {
				unset($_SESSION[$this->_filter_name]);
			}
		}
		if (!$silent) {
			js_redirect("./?object=manage_shop&action=show_orders"._add_get());
		}
	}
	
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}

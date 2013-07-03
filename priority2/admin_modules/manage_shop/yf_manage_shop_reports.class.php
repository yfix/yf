<?php

/**
* Manage shop sub module
*/
class yf_manage_shop_reports {

	/**
	* Constructor
	*/
	function _init () {
		if (module('manage_shop')->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Show orders
	*/
	function show_reports() {
		if ($_GET["id"]=="viewed"){
			$items = $this->show_reports_viewed ();
			$active = "viewed";
		}elseif ($_GET["id"]=="sales"){
			$items = $this->show_reports_sales ();
			$active = "sales";
		}elseif ($_GET["id"]=="purchased"){
				$items = $this->show_reports_purchased ();
				$active = "purchased";
		}
		$replace = array(
			"items"			=>	$items,
			"active"		=>	$active,
			"viewed_url"	=> "./?object=manage_shop&action=show_reports&id=viewed",
			"sales_url"		=> "./?object=manage_shop&action=show_reports&id=sales",
			"purchased_url"	=> "./?object=manage_shop&action=show_reports&id=purchased",
			
		);
		return tpl()->parse("manage_shop/report_main", $replace);
	}
	
	
	function show_reports_viewed () {
		$sql = "SELECT * FROM `".db('shop_products')."`";
		$sql .=   " ORDER BY `viewed` DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", 100);
		$products_info = db()->query_fetch_all($sql.$add_sql);
		$this->_total_prod = $total;
		$query = db()->query_fetch("SELECT SUM(viewed) AS `total` FROM `".db('shop_products')."`");
		foreach ((array)$products_info as $v){
			if ($v['viewed'] ) {
				$percent = round(($v['viewed'] / $query["total"]) * 100, 2) . '%';
			} else {
				$percent = '0%';
			}
			$replace2 = array(
				"name"		=> _prepare_html($v["name"]),
				"model"		=> _prepare_html($v["model"]),
				"viewed"	=> _prepare_html($v["viewed"]),
				"percent"	=> $percent,
			);
			$items .= tpl()->parse("manage_shop/item_reports_viewed", $replace2); 
		}
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"sort_url"	=> "./?object=manage_shop&action=sort",
		);
		return tpl()->parse("manage_shop/reports_viewed", $replace);
	}
	
	function sort() {
		main()->NO_GRAPHICS = true;
		list ($name, $sort_by) = split ('[-]', $_POST["id"]);
		if ($name == "percent"){
			$name = "viewed";
		}
		$sql = "SELECT * FROM `".db('shop_products')."` ORDER BY `".$name."` ".$sort_by." ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", 100);
		$products_info = db()->query_fetch_all($sql.$add_sql);
		$this->_total_prod = $total;
		$query = db()->query_fetch("SELECT SUM(viewed) AS `total` FROM `".db('shop_products')."`");
		foreach ((array)$products_info as $v){
			if ($v['viewed'] ) {
				$percent = round(($v['viewed'] / $query["total"]) * 100, 2) . '%';
			} else {
				$percent = '0%';
			}
			$replace2 = array(
				"name"		=> _prepare_html($v["name"]),
				"model"		=> _prepare_html($v["model"]),
				"viewed"	=> _prepare_html($v["viewed"]),
				"percent"	=> $percent,
			);
			$items .= tpl()->parse("manage_shop/item_reports_viewed", $replace2); 
		}
		echo $items;
		
	}
	
	function show_reports_sales () {
		echo 1;
	}
	
	function show_reports_purchased () {
		
		$sql_item_order = "SELECT * FROM `".db('shop_order_items')."` ORDER BY `sum` DESC";
		$sql_order 	= "SELECT * FROM `".db('shop_orders')."`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql_item_order, "", "", 100);
		$item_order_info = db()->query_fetch_all($sql_item_order.$add_sql);
		foreach ((array)$item_order_info as $v){
			$product[$v['product_id'] ]["quantity"]  += $v['quantity'] ;
			$product[$v['product_id'] ]["sum"]  += $v['sum'] ;
		}
		if (module('manage_shop')->USE_FILTER) {
			$filter = $this->_create_filter() ;
			if ($filter ["order"] == "DESC") {
				arsort($product);
				reset($product);
			}elseif($filter ["order"] == "ASC") {
				asort($product);
				reset($product);
			}
		} 
		foreach ((array)$product as $k => $v){
			$sum 				=  $product[$k]['sum'];
			$quantity			= $product[$k]['quantity'];
			$sql_product 	= "SELECT * FROM `".db('shop_products')."` WHERE `id` = ".$k;
			$item_product 	= db()->query_fetch($sql_product);
			 $replace2 		= array(
				"name"			=> _prepare_html($item_product["name"]),
				"model"			=> _prepare_html($item_product["model"]),
				"viewed"		=> $quantity,
				"percent"		=> module('manage_shop')->_format_price($sum),
			);
			$items .= tpl()->parse("manage_shop/item_reports_viewed", $replace2);  
		}
		$replace = array(
			"items"		=> $items,
			"pages"	=> $pages,
			"total"		=> intval($total),
			"filter"		=> module('manage_shop')->USE_FILTER ? $this->_show_filter() : "",
			"sort_url"	=> "./?object=manage_shop&action=show_reports_purchased",
		);
		return tpl()->parse("manage_shop/reports_product_purchased", $replace);
	}
	
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= "report_purchased_filter";
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
			"name"			=> "Name",
			"model"			=> "Model",
			"quantity" 		=> "Quantity",
			"sum" 			=> "SUM",
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
			
			"name",
			"model",
			//"sum_max",
		//	"sum_min",
			"sort_by",
			"sort_order",
			"orders_by_date",
			
		);
	}

	// Generate filter SQL query
	function _create_filter () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		
		if (strlen($SF["name"])){
			$sql ["name"]= ($SF["name"]);
		}
		if (strlen($SF["model"])){
			$sql ["model"]= ($SF["model"]);
		}
		$cur_date = time ();
		$cur_date =	date("d F", ($cur_date));
		$cur_date =	strtotime($cur_date);
			if($SF["orders_by_date"] == "0"){
				$SF["orders_by_date"];
				$date = $cur_date - $SF["orders_by_date"] ;
				$sql ["date"]= $date;
			}elseif ($SF["orders_by_date"] == "86400"){
				$date = $cur_date - $SF["orders_by_date"] ;
				$sql ["date"]= $date;
			}elseif ($SF["orders_by_date"] == "604800"){
				$date = $cur_date - $SF["orders_by_date"] ;
				$sql ["date"]= $date;
			}elseif ($SF["orders_by_date"] == "2592000"){
				$date = $cur_date - $SF["orders_by_date"] ;
				$sql ["date"]= $date;
			}elseif ($SF["orders_by_date"] == "31536000"){
				$date = $cur_date - $SF["orders_by_date"] ;
				$sql ["date"]= $date;
			}
		// Sorting here
		if ($SF["sort_by"])	{
			$sql ["sort"]= $SF["sort_by"];
		}
		if ($SF["sort_by"] && strlen($SF["sort_order"])) {
			$sql ["order"]= $SF["sort_order"];
		}
		return ($sql);
	}

	// Session - based filter
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=manage_shop&action=save_filter_report"._add_get(),
			"clear_url"		=> "./?object=manage_shop&action=clear_filter_report"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse("manage_shop/filter_report", $replace);
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
			js_redirect("./?object=manage_shop&action=show_reports&id=purchased"._add_get());
		}
	}
	
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}

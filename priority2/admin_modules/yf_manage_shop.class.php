<?php

/**
* Shop managing module
*/
class yf_manage_shop {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var string Folder where product's images store */
	public $PROD_IMG_DIR	= "shop/products/";
	/** @var string fullsize image suffix (underscore at the beginning required)*/
	public $FULL_IMG_SUFFIX	= "_full";
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $THUMB_SUFFIX	= "_small";
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $MEDIUM_SUFFIX	= "_medium";
	/** @var string Image prefix */
	public $IMG_PREFIX		= "product_";
	/** @var int Thumb size X */
	public $THUMB_X			= 100;
	/** @var int Thumb size Y */
	public $THUMB_Y			= 100;
	/** @var string Default currency */
	public $CURRENCY		= "\$";
	/** @var Shipping types */
	public $_ship_types = array(
		1	=> "Free",
		2	=> "Courier",
		3	=> "FedEX",
	);
	/** @var Payment types */
	public $_pay_types = array(
		1	=> "Courier",
		2	=> "Authorize.Net",
	);
	/** @var @conf_skip */
	public $_statuses = array(
		""					=> "",
		"pending"			=> "Pending",
		"pending payment"	=> "Pending payment",
		"proccessed"		=> "Proccessed",
		"delivery"			=> "Delivery",
		"shipped"			=> "Shipped",
	);
	/** @var Company info */
	public $COMPANY_INFO = array(
		"company_name"		=> "Company Name",
		"company_address"	=> "Company Address 1",
		"company_address2"	=> "Company Address 2",
		"company_phone"		=> "Company Phone",
		"company_website"	=> "Company Website",
		"company_email"		=> "Company Email",
	);
	/** @var */
	public $ATTRIBUTES = array();
	/** @var @conf_skip */
	public $ATTRIBUTES_CAT_ID = 1;

	/**
	* Constructor
	*/
	function _init () {
		$this->_cats_for_select	= _class('cats')->_prepare_for_box("shop_cats", 0);
		
		$sql = "SELECT * FROM `".db('shop_manufacturer')."` ORDER BY `name` ASC";
		$this->man = db()->query_fetch_all($sql);
		$this->_man_for_select[0] = "--NONE--";
		foreach ((array)$this->man as $k =>$v) {
			$this->_man_for_select[$v["id"]] = $v["name"];
		}
		$this->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		$this->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		if (!file_exists($this->products_img_dir)) {
			_mkdir_m($this->products_img_dir);
		}
		$this->_boxes = array(
			"status"		=> 'select_box("status",		$this->_statuses,	$selected, false, 2, "", false)',
			"featured"		=> 'radio_box("featured",		$this->_featured,	$selected, false, 2, "", false)',
			"status_prod"	=> 'select_box("status_prod",	$this->_status_prod,$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,	$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order", 	$this->_sort_orders,$selected, 0, 2, "", false)',
		);
		$this->_featured = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		$this->_status_prod = array(
			""		=> "",
			"1"	=> "Active",
			"0"	=> "Inacive",
		);
		$this->_sort_orders = array(""	=> "", "DESC" => "Descending", "ASC" => "Ascending");
		$this->_sort_by = array(
			""			=> "",
			"name"		=> "Name",
			"price" 	=> "Price",
			"quantity" 	=> "Quantity",
			"add_date" 	=> "Date",
			"active" 	=> "Status",
		);
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		// Sync company info with user section
#		$this->COMPANY_INFO = _class("shop", "modules/")->COMPANY_INFO;
	}

	/**
	* Default method
	*/
	function show () {
		return $this->home();
	}

	/**
	*/
	function home () {
		$items = $this->statistic();
		$replace = array(
			"items"				=> $items,
			"products_url"		=> "./?object=manage_shop&action=products_manage",
			"manufacturer_url"	=> "./?object=manage_shop&action=manufacturers_manage",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"attributes_url"	=> "./?object=manage_shop&action=attributes_manage", 
			"orders_url"		=> "./?object=manage_shop&action=show_orders",
			"reports_url"		=> "./?object=manage_shop&action=show_reports&id=viewed",
			"settings_url"		=> "./?object=manage_shop&action=show_settings",
		);
		return tpl()->parse("manage_shop/home", $replace);
	}

	/**
	*/
	function statistic () {
		$total_sum = db()->query_fetch("SELECT SUM(`total_sum`) FROM `".db('shop_orders')."`");
		$total_order = db()->query_fetch("SELECT COUNT(*) FROM `".db('shop_orders')."`");
		$total_prod = db()->query_fetch("SELECT COUNT(*) FROM `".db('shop_products')."`");
		$total_order_pending = db()->query_fetch("SELECT COUNT(*) FROM `".db('shop_orders')."` WHERE `status` = 'pending'");
		$total_sum_shipped = db()->query_fetch("SELECT SUM(`total_sum`) FROM `".db('shop_orders')."` WHERE `status` = 'shipped'");
		$replace = array(
			"summ"					=> $this->_format_price($total_sum["SUM(`total_sum`)"]),
			"total_order"			=> intval($total_order["COUNT(*)"]),
			"total_order_pending"	=> intval($total_order_pending["COUNT(*)"]),
			"total_sum_shipped"		=> $this->_format_price($total_sum_shipped["SUM(`total_sum`)"]),
			"total_prod"			=> intval($total_prod["COUNT(*)"]),
		);
		return tpl()->parse("manage_shop/stat_main", $replace);
	}
	
	/**
	*/
	function products_manage () {
		if (!empty($_GET["name"])) {
			$_POST["name"] = $_GET["name"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		if (!empty($_GET["price_min"])) {
			$_POST["price_min"] = $_GET["price_min"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		if (!empty($_GET["price_max"])) {
			$_POST["price_max"] = $_GET["price_max"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		if (!empty($_GET["quantity_min"])) {
			$_POST["quantity_min"] = $_GET["quantity_min"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		if (!empty($_GET["quantity_max"])) {
			$_POST["quantity_max"] = $_GET["quantity_max"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		 if (!empty($_GET["sort_by"])) {
			$_POST["sort_by"] = $_GET["sort_by"];
			$this->clear_filter(1);
			$this->save_filter(1);
		} 
		 if (!empty($_GET["sort_order"])) {
			$_POST["sort_by"] = $_GET["sort_by"];
			$this->clear_filter(1);
			$this->save_filter(1);
		} 
		if (!empty($_GET["status_prod"])) {
			$_POST["status_prod"] = $_GET["status_prod"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}
		$sql = "SELECT * FROM `".db('shop_products')."`";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `add_date` DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, "", "", 100);
		$products_info = db()->query_fetch_all($sql.$add_sql);
		$this->_total_prod = $total;
		foreach ((array)$products_info as $v){
			$replace2 = array(
				"name"			=> _prepare_html($v["name"]),
				"date"			=> _format_date($v["add_date"], "long"),
				"price"			=> $v["price"],
				"old_price"		=> $v["old_price"],
				"quantity"		=> $v["quantity"],
				"active"		=> $v["active"],
				"edit_url"		=> "./?object=manage_shop&action=product_edit&id=".$v["id"],
				"delete_url"	=> "./?object=manage_shop&action=product_delete&id=".$v["id"],
				"view_url"		=> "./?object=manage_shop&action=product_view&id=".$v["id"],
				"activate_url"	=> "./?object=manage_shop&action=product_activate&id=".$v["id"],
			);
			$items .= tpl()->parse("manage_shop/products_item", $replace2); 
		}
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"filter"			=> $this->USE_FILTER ? $this->_show_filter() : "",
			"add_url"			=> "./?object=manage_shop&action=product_add",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"attributes_url"	=> "./?object=manage_shop&action=attributes_manage",
			"orders_url"		=> "./?object=manage_shop&action=show_orders",
		);
		return tpl()->parse("manage_shop/products_main", $replace);
	}

	/**
	*/
	function product_add () {
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if ($_POST["ext_url"]) {
				if (substr($_POST["ext_url"], 0, 7) !== "http://") {
					$_POST["ext_url"] = "http://".$_POST["ext_url"];
				}
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					"name"				=> _es($_POST["name"]),
					"model"				=> _es($_POST["model"]),
					"url"				=> _es(common()->_propose_url_from_name($_POST["name"])),
					"description"		=> _es($_POST["desc"]),
					"meta_keywords"		=> _es($_POST["meta_keywords"]),
					"meta_desc"			=> _es($_POST["meta_desc"]),
					"external_url"		=> _es($_POST["ext_url"]),
					"quantity"			=> intval($_POST["quantity"]),
					"manufacturer_id"	=> intval($_POST["manufacturer"]),
					"price"				=> floatval(str_replace(",", ".", $_POST["price"])),
					"old_price"			=> floatval(str_replace(",", ".", $_POST["price"])),
					"featured"			=> intval((bool)$_POST["featured"]),
					"currency"			=> "",// TODO
					"add_date"			=> time(),
					"active"			=> 1,
				);
				// Image upload
				if (!empty($_FILES)) {
					$product_id = $_GET["id"];
					$product_name = _es(common()->_propose_url_from_name($_POST["name"]));
					$rez_upload = $this->_image_upload ($product_id, $product_name);
					$sql_array = array(
						"image"	=> 1,
					);
				} 
				db()->INSERT(db('shop_products'), $sql_array);
				foreach ((array)$_POST["category"] as $k => $v){
					$cat_id ["product_id"] = $_GET["id"];
					$cat_id ["category_id"] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				$product_id = db()->INSERT_ID();
				$this->_attributes_save($product_id);
				$this->_save_group_prices($product_id);
			}
			return js_redirect("./?object=manage_shop&action=products_manage");
		}
		// 1-st type of assigning attributes
		$fields = $this->_attributes_html(0);
		// 2-nd type of assigning attributes (select boxes)
		// For case when we need just select custom attributes only one value of each
		$all_atts	= $this->_get_attributes();
		foreach ((array)$all_atts as $_attr_id => $_attr_info) {
			$_name_in_form = "single_attr[".$_attr_id."]";
			$_selected = "";
			$single_atts[$_attr_info["title"]] = array(
				"title"			=> _prepare_html($_attr_info["title"]),
				"name_in_form"	=> _prepare_html($_name_in_form),
				"box"			=> common()->select_box($_name_in_form, $_attr_info["value_list"], $_selected, false, 2, "", false),
			);
		}
		// Group prices here
		$group_prices = array();
		$user_groups = main()->get_data("user_groups");
		foreach ((array)$this->_get_group_prices(0) as $_group_id => $_group_price) {
			$group_prices[$_group_id] = array(
				"group_id"		=> intval($_group_id),
				"group_name"	=> _prepare_html($user_groups[$_group_id]),
				"price"			=> $_group_price ? number_format($_group_price, 2, '.', ' ') : "",
			);
		}
		$replace = array(
			"name"				=> "",
			"model"				=> "",
			"desc"				=> "",
			"meta_keywords"		=> "",
			"meta_desc"			=> "",
			"ext_url"			=> "",
			"price"				=> "",
			"old_price"			=> "",
			"quantity"			=> "",
			"dynamic_fields"	=> $fields,
			"single_atts"		=> $single_atts,
			"manufacturer_box"	=> common()->select_box("manufacturer", $this->_man_for_select, $man_id, false, 2),
			"category_box"		=> common()->multi_select("category", $this->_cats_for_select, $cat_id, false, 2, " size=15 ", false),
			"form_action"		=> "./?object=manage_shop&action=product_add",
			"back_url"			=> "./?object=manage_shop&action=products_manage",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"manufacturers_url"	=> "./?object=manage_shop&action=manufacturers_manage",
			"group_prices"		=> !empty($group_prices) ? $group_prices : "",
		);
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $SF[$item_name]);
		}
		return tpl()->parse("manage_shop/product_edit", $replace);
	}

	/**
	* Edit existing products
	*/
	function product_edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$_def_locale = $this->PROJ_DEFAULT_LOCALE;
		if (!$_def_locale) {
			$_def_locale = "en";
		}
		$product_info = db()->query_fetch("SELECT * FROM `".db('shop_products')."` WHERE `id`=".$_GET["id"]);
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if ($_POST["ext_url"]) {
				if (substr($_POST["ext_url"], 0, 7) !== "http://") {
					$_POST["ext_url"] = "http://".$_POST["ext_url"];
				}
			}
			if (!common()->_error_exists()) {
				// Save data
				$sql_array = array(
					"name"				=> _es($_POST["name"]),
					"model"				=> _es($_POST["model"]),
					"url"				=> _es(common()->_propose_url_from_name($_POST["name"])),
					"description"		=> _es($_POST["desc"]),
					"meta_keywords"		=> _es($_POST["meta_keywords"]),
					"meta_desc"			=> _es($_POST["meta_desc"]),
					"external_url"		=> _es($_POST["ext_url"]),
					"price"				=> floatval(str_replace(",", ".", $_POST["price"])),
					"old_price"			=> floatval(str_replace(",", ".", $_POST["old_price"])),
					"manufacturer_id"	=> intval($_POST["manufacturer"]),
					"quantity"			=> intval($_POST["quantity"]),
					"featured"			=> intval((bool)$_POST["featured"]),
				);
				// Image upload
				if (!empty($_FILES)) {
					$product_id = $_GET["id"];
					$product_name = _es(common()->_propose_url_from_name($_POST["name"]));
					$rez_upload = $this->_image_upload ($product_id, $product_name);
					$sql_array = array(
						"image"	=> 1,
					);
				} 
				db()->UPDATE(db('shop_products'), $sql_array, "`id`=".$_GET["id"]);
				db()->query("DELETE FROM  `".db('shop_product_to_category')."` WHERE `product_id` = ".$_GET["id"]);
				foreach ((array)$_POST["category"] as $k => $v){
					$cat_id["product_id"] = $_GET["id"];
					$cat_id["category_id"] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				
				db()->query("DELETE FROM " . db('shop_product_related') ."  WHERE product_id = '" . (int)$_GET["id"] . "'");

				if (isset($_POST["product_related"])) {
					foreach ((array)$_POST["product_related"] as $related_id) {
						$related["product_id"] = $_GET["id"];
						$related["related_id"] = $related_id;
						db()->INSERT( db('shop_product_related'), $related);
					}
				}
				$this->_attributes_save($_GET["id"]);
				$this->_save_group_prices($_GET["id"]);
			}
			return js_redirect("./?object=manage_shop&action=products_manage");
		}
		if ($product_info["image"] == 0) {
			$thumb_path = "";
		} else {
			$dirs = sprintf("%06s",$product_info["id"]);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1."/".$dir2."/";
			$image_files = _class('dir')->scan_dir($this->products_img_dir.$mpath, true, "/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/");
			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
			foreach((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult["content"];
				$image_delete_url ="./?object=manage_shop&action=image_delete&id=".$product_info["id"]."&name=".$product_info["url"]."&key=".$i;
				$thumb_path_temp = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->THUMB_SUFFIX.".jpg";
				$img_path = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->FULL_IMG_SUFFIX.".jpg";
				$replace2 = array(
					"img_path" 		=> $img_path,
					"thumb_path"	=> $thumb_path_temp,
					"del_url" 		=> $image_delete_url,
					"name"			=> $product_info["url"],
				);
				$items .= tpl()->parse("manage_shop/image_items", $replace2);
			}
		}	
		// 1-st type of assigning attributes
		$fields = $this->_attributes_html($_GET["id"]);
		// 2-nd type of assigning attributes (select boxes)
		// For case when we need just select custom attributes only one value of each
		$all_atts	= $this->_get_attributes();
		$saved_attrs	= $this->_get_products_attributes($_GET["id"]);
		foreach ((array)$all_atts as $_attr_id => $_attr_info) {
			$_name_in_form = "single_attr[".$_attr_id."]";
			$_selected = "";
			// Try to get selected value
			$_cur_item_prefix = $_attr_id."_";
			foreach ((array)$saved_attrs as $_item_id => $_item_info) {
				if (substr($_item_id, 0, strlen($_cur_item_prefix)) == $_cur_item_prefix) {
					$_selected = substr($_item_id, strlen($_cur_item_prefix));
					break;
				}
			}
			$single_atts[$_attr_info["title"]] = array(
				"title"			=> _prepare_html($_attr_info["title"]),
				"name_in_form"	=> _prepare_html($_name_in_form),
				"box"			=> common()->select_box($_name_in_form, $_attr_info["value_list"], $_selected, false, 2, "", false),
			);
		}
		// Group prices here
		$group_prices = array();
		$user_groups = main()->get_data("user_groups");
		foreach ((array)$this->_get_group_prices($_GET["id"]) as $_group_id => $_group_price) {
			$group_prices[$_group_id] = array(
				"group_id"	=> intval($_group_id),
				"group_name"=> _prepare_html($user_groups[$_group_id]),
				"price"		=> $_group_price ? number_format($_group_price, 2, '.', ' ') : "",
			);
		}
		$sql1 = "SELECT `category_id` FROM `".db('shop_product_to_category')."` WHERE `product_id` = ". $_GET["id"];
		$products = db()->query($sql1);
		while ($A = db()->fetch_assoc($products)) {
			$cat_id[$A["category_id"]] .= $A["category_id"];
		}	
		$replace = array(
			"name"					=> $product_info["name"],
			"model"					=> $product_info["model"],
			"desc"					=> $product_info["description"],
			"meta_keywords"			=> $product_info["meta_keywords"],
			"meta_desc"				=> $product_info["meta_desc"],
			"use_editor_code"		=> intval($this->_EDITOR_EXISTS && !empty($_body)),
			"price"					=> $product_info["price"],
			"old_price"				=> $product_info["old_price"],
			"quantity"				=> $product_info["quantity"],
			"dynamic_fields"		=> $fields,
			"single_atts"			=> $single_atts,
			"ext_url"				=> $product_info["external_url"],
			"manufacturer_box"		=> common()->select_box("manufacturer", $this->_man_for_select, $product_info["manufacturer_id"], false, 2),
			"category_box"			=> common()->multi_select("category", $this->_cats_for_select, $cat_id, false, 2, " size=15 class=small_for_select ", false),
			"category_select_box"	=> common()->select_box("category_select", $this->_cats_for_select, $cat_id, false, 2),
			"featured_box"			=> $this->_box("featured", $product_info["featured"]),
			"form_action"			=> "./?object=manage_shop&action=product_edit&id=".$product_info["id"],
			"back_url"				=> "./?object=manage_shop&action=products_manage",
			"image"					=> $items,
			"categories_url"		=> "./?object=category_editor&action=show_items&id=shop_cats",
			"manufacturers_url"		=> "./?object=manage_shop&action=manufacturers_manage",
			"manage_attrs_url"		=> "./?object=manage_shop&action=attributes_manage",
			"group_prices"			=> !empty($group_prices) ? $group_prices : "",
			"link_get_product"		=>  process_url("./?object=manage_shop&action=show_product_by_category&cat_id="),
			"product_related"		=>  $this->get_product_related($product_info["id"]),
		);
		return tpl()->parse("manage_shop/product_edit", $replace);
	}

	/**
	*/
	function product_view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$product_info = db()->query_fetch("SELECT * FROM `".db('shop_products')."` WHERE `id`=".$_GET["id"]);
		if ($product_info["image"] == 0) {
			$thumb_path = "";
		} else {
			$dirs = sprintf("%06s",$product_info["id"]);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1."/".$dir2."/";
			$image_files = _class('dir')->scan_dir($this->products_img_dir.$mpath, true, "/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/");
			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
			foreach((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult["content"];
				$thumb_path_temp = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->THUMB_SUFFIX.".jpg";
				$img_path = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->FULL_IMG_SUFFIX.".jpg";
				$replace2 = array(
					"thumb_path"=> $thumb_path_temp,
					"img_path" 	=> $img_path,
					"name"		=> $product_info["url"],
				);
				$items .= tpl()->parse("manage_shop/image_items", $replace2);
			}
		}	
		$dyn_fields = $this->_attributes_view($_GET["id"]);
		$sql1 = "SELECT `category_id` FROM `".db('shop_product_to_category')."` WHERE `product_id` = ". $_GET["id"];
		$products = db()->query($sql1);
		while ($A = db()->fetch_assoc($products)) {
			$cat_id[$A["category_id"]] .= $A["category_id"];
		}	
		$replace = array(
			"name"				=> _prepare_html($product_info["name"]),
			"model"				=> _prepare_html($product_info["model"]),
			"desc"				=> _prepare_html($product_info["description"]),
			"meta_keywords"		=> _prepare_html($product_info["meta_keywords"]),
			"meta_desc"			=> _prepare_html($product_info["meta_desc"]),
			"ext_url"			=> _prepare_html($product_info["external_url"]),
			"price"				=> $product_info["price"],
			"dynamic_fields"	=> $dyn_fields,
			"manufacturer"		=> $this->_man_for_select[$product_info["manufacturer_id"]],
			"category"			=> common()->multi_select("category", $this->_cats_for_select, $cat_id, false, 2, " size=15 class=small_for_select ", false, "", true),
			"back_url"			=> "./?object=manage_shop&action=products_manage",
			"image"				=> $items,
			"thumb_path"		=> $thumb_path,
			"product_related"	=>  $this->get_product_related($product_info["id"]),
		);
		return tpl()->parse("manage_shop/product_view", $replace);
	}

	/**
	*/
	function product_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$this->_image_delete($_GET["id"]);
		db()->query("DELETE FROM `".db('dynamic_fields_values')."` WHERE `object_id`=".$_GET["id"]);
		db()->query("DELETE FROM `".db('shop_group_options')."` WHERE `product_id`=".$_GET["id"]);		
		db()->query("DELETE FROM `".db('shop_products')."` WHERE `id`=".$_GET["id"]);
		return js_redirect("./?object=manage_shopaction=products_manage");
	}

	/**
	*/
	function product_activate () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM `".db('shop_products')."` WHERE `id`=".intval($_GET["id"]));
			if ($A["active"] == 1) {
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_products'), array("active" => $active), "`id`='".intval($_GET["id"])."'");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=manage_shop");
		}
	}

	/**
	*/
	function image_upload () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$this->_image_upload($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Upload image
	*/
	function _image_upload ($product_id, $product_name) {
		$i = 1;
		$dirs = sprintf("%06s",$product_id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		foreach ((array)$_FILES['image'] ['tmp_name'] as $k => $v ) {
			$img_properties = getimagesize($v);
			if (empty($img_properties) || !$product_id) {
				return false;
			}
			$img_path = $this->products_img_dir . $mpath.$product_name."_".$product_id."_".$i.$this->FULL_IMG_SUFFIX.".jpg";		
			$i = $this->_check_filed ($img_path, $product_id, $product_name, $i);
			$img_path = $this->products_img_dir . $mpath.$product_name."_".$product_id."_".$i.$this->FULL_IMG_SUFFIX.".jpg";
			$img_path_thumb = $this->products_img_dir . $mpath.$product_name."_".$product_id."_".$i.$this->THUMB_SUFFIX.".jpg";
			$upload_result = common()->multi_upload_image($img_path, $k);
			if ($upload_result) {
				$resize_result = common()->make_thumb($img_path, $img_path_thumb, $this->THUMB_X, $this->THUMB_Y);
			}
		} 
		return $i;
	}

	/**
	*/
	function _check_filed ($path, $product_id, $product_name, $i) {
		if (file_exists($path)) {
			$i = $i +1;
			$img_path = $this->products_img_dir.$product_name."_".$product_id."_".$i.$this->FULL_IMG_SUFFIX.".jpg";
			$this->_check_filed ($img_path, $product_id, $product_name, $i);
		} 
		return $i;
	}

	/**
	*/
	function _image_delete ($id, $name, $k) {
		$dirs = sprintf("%06s",$id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		$image_files = _class('dir')->scan_dir($this->products_img_dir.$mpath, true, "/".$name."_".$id."_".$k.".+?jpg"."/");
		foreach((array)$image_files as $filepath) {
			unlink($filepath);
		}
		$image_files = _class('dir')->scan_dir($this->products_img_dir.$mpath, true, "/".$name."_".$id.".+?.jpg"."/");
		if (!$image_files ){
			$sql_array = array(
				"image"	=> 0,
			);
			db()->UPDATE(db('shop_products'), $sql_array, "`id`=".$_GET["id"]); 
		}
		return true;
	}

	/**
	* Delete image
	*/
	function image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$this->_image_delete($_GET["id"], $_GET["name"], $_GET["key"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
	
	/**
	*/
	function show_product_by_category ($cat = "") {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET["cat_id"];
		$sql1 = "SELECT `product_id` FROM `".db('shop_product_to_category')."` WHERE `category_id` =". $cat_id ;
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A["product_id"].",";
			}	
			$product_info = rtrim($product_info, ",");
			
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1'  AND `id` IN (".$product_info .")  ORDER BY `name`";
		$product = db()->query_fetch_all($sql);
		$products = array();
		foreach ((array)$product as $v) {
			$products []  = array (
				"product_id"	=> $v["id"],
				"name"			=> $v["name"],
			);
		}
		echo  json_encode($products);
	}	
	
	/**
	*/
	function get_product_related ($id = "") {
		$product_related_data = array();
		$sql = "SELECT * FROM `".db('shop_product_related') . "` WHERE `product_id` = ". $id;
		$product = db()->query($sql);
		while ($A = db()->fetch_assoc($product)){
			$product_related_id .= $A['related_id'].",";
		}
		$product_related_id = rtrim($product_related_id, ",");
		if ($product_related_id != "") {
			$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1'  AND `id` IN (".$product_related_id .")  ORDER BY `name`";
			$product = db()->query_fetch_all($sql);
			$products = array();
			foreach ((array)$product as $v) {
				$product_related_data[] = array(
					"related_id"=> $v["id"],
					"name"		=> $v["name"],
				);
			}
		}
		return $product_related_data;
		
	}	

	/**
	*/
	function show_settings() {
		return _class('manage_shop_settings', 'admin_modules/manage_shop/')->show_settings();
	}
	
	/**
	*/
	function orders_manage() {
		return $this->show_orders();
	}
	
	/**
	*/
	function show_reports() {
		return _class('manage_shop_reports', 'admin_modules/manage_shop/')->show_reports();
	}
	
	/**
	*/
	function show_reports_viewed() {
		return _class('manage_shop_reports', 'admin_modules/manage_shop/')->show_reports_viewed();
	}
	
	/**
	*/
	function sort() {
		return _class('manage_shop_reports', 'admin_modules/manage_shop/')->sort();
	}

	/**
	*/
	function show_orders() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->show_orders();
	}

	/**
	*/
	function show_print() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->show_print();
	}
	
	/**
	*/
	function view_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->view_order();
	}

	/**
	*/
	function save_filter_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->save_filter();
	}
	
	/**
	*/
	function clear_filter_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->clear_filter();
	}
	
	/**
	*/
	function save_filter_report() {
		return _class('manage_shop_reports', 'admin_modules/manage_shop/')->save_filter();
	}
	
	/**
	*/
	function clear_filter_report() {
		return _class('manage_shop_reports', 'admin_modules/manage_shop/')->clear_filter();
	}

	/**
	*/
	function delete_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->delete_order();
	}
	
	/**
	*/
	function manufacturers_manage () {
		return _class('manage_shop_manufacturer', 'admin_modules/manage_shop/')->manufacturers_manage();
	}	
	
	/**
	*/
	function manufacturer_edit () {
		return _class('manage_shop_manufacturer', 'admin_modules/manage_shop/')->manufacturer_edit();
	}	
	
	/**
	*/
	function manufacturer_add () {
		return _class('manage_shop_manufacturer', 'admin_modules/manage_shop/')->manufacturer_add();
	}	
	
	/**
	*/
	function manufacturer_view () {
		return _class('manage_shop_manufacturer', 'admin_modules/manage_shop/')->manufacturer_view();
	}	
	
	/**
	*/
	function attributes_manage () {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->attributes_manage();
	}	

	/**
	*/
	function attribute_add () {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->attribute_add();
	}

	/**
	*/
	function attribute_edit () {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->attribute_edit();
	}

	/**
	*/
	function attribute_delete () {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->attribute_delete();
	}

	/**
	*/
	function _attributes_view ($object_id = 0) {
		return $this->_attributes_html($object_id, true);
	}

	/**
	*/
	function _attributes_html ($object_id = 0, $only_selected = false) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->_attributes_html($object_id, $only_selected);
	}

	/**
	*/
	function _attributes_save ($object_id = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->_attributes_save($object_id);
	}

	/**
	*/
	function _get_attributes ($category_id = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->_get_attributes($category_id);
	}

	/**
	*/
	function _get_products_attributes($products_ids = array()) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->_get_products_attributes($products_ids);
	}

	/**
	*/
	function _get_attributes_values ($category_id = 0, $object_id = 0, $fields_ids = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->_get_attributes_values($category_id, $object_id, $fields_ids);
	}

	/**
	*/
	function _format_price($price = 0) {
		if ($this->CURRENCY == "\$") {
			return $this->CURRENCY."&nbsp;".$price;
		} else {
			return $price."&nbsp;".$this->CURRENCY;
		}
	}

	/**
	*/
	function _get_group_prices ($product_id = 0) {
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups)) {
			return array();
		}
		$group_pricess	= array();
		foreach ((array)$user_groups as $_group_id => $_group_name) {
			if (!$_group_id) {
				continue;
			}
			$group_prices[$_group_id] = 0;
		}
		$product_id = intval($product_id);
		if (!empty($product_id)) {
			// Get prices per group
			$Q = db()->query(
				"SELECT * FROM `".db('shop_group_options')."` 
				WHERE `product_id`=".$product_id." 
					AND `group_id` IN (".implode(",", array_keys($user_groups)).")"
			);
			while($A = db()->fetch_assoc($Q)) {
				if (!$A["group_id"] || !isset($user_groups[$A["group_id"]])) {
					continue;
				}
				$group_prices[$A["group_id"]] = floatval($A["price"]);
			}
		}
		return $group_prices;
	}

	/**
	* Save prices
	*/
	function _save_group_prices ($product_id = 0) {
		if (!$product_id) {
			return false;
		}
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups)) {
			return false;
		}
		// Get prices per group
		$Q = db()->query(
			"SELECT * FROM `".db('shop_group_options')."` 
			WHERE `product_id`=".$product_id." 
				AND `group_id` IN (".implode(",", array_keys($user_groups)).")"
		);
		while($A = db()->fetch_assoc($Q)) {
			if (!isset($user_groups[$A["group_id"]])) {
				continue;
			}
			$group_prices[$A["group_id"]] = $A["price"];
		}
		foreach ((array)$user_groups as $_group_id => $_group_name) {
			$new_group_price = $_POST["group_prices"][$_group_id];
			$sql = array(
				"product_id"=> intval($product_id),
				"group_id"	=> intval($_group_id),
				"price"		=> floatval($new_group_price),
			);
			if (isset($group_prices[$_group_id])) {
				db()->UPDATE("shop_group_options", $sql, "`product_id`=".intval($product_id)." AND `group_id`=".intval($_group_id));
			} else {
				db()->INSERT("shop_group_options", $sql);
			}
		}
	}

	/**
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage products",
				"url"	=> "./?object=manage_shop&action=show",
			),
			array(
				"name"	=> "Manage orders",
				"url"	=> "./?object=manage_shop&action=orders_manage",
			),
			array(
				"name"	=> "Manage attributes",
				"url"	=> "./?object=manage_shop&action=attributes_manage",
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Shop");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"		=> "Products",
			"add"		=> "Add product",
		);			  		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
	
	/**
	*/
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= "products_manage_filter";
		// Fields in the filter
		$this->_fields_in_filter = array(
			"name",
			"price_max",
			"price_min",
			"quantity_min",
			"quantity_max",
			"status_prod",
			"sort_by",
			"sort_order",
		);
	}

	/**
	*/
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		if ($SF["price_min"]){
			$sql .= " AND `price` >= ".intval($SF["price_min"])." \r\n";
		}
		if ($SF["price_max"])	{
			$sql .= " AND `price` <= ".intval($SF["price_max"])." \r\n";
		}
		if ($SF["quantity_min"]){
			$sql .= " AND `quantity` >= ".intval($SF["quantity_min"])." \r\n";
		}
		if ($SF["quantity_max"])	{
			$sql .= " AND `quantity` <= ".intval($SF["quantity_max"])." \r\n";
		}
		if (strlen($SF["name"])){
			$sql .= " AND `name` LIKE '"._es($SF["name"])."%' \r\n";
		}
		 if($SF["status_prod"] == '0'){
			$sql .= " AND `active` = '".intval($SF["status_prod"])."' \r\n";
		}elseif($SF["status_prod"] == '1'){
			$sql .= " AND `active` = '".intval($SF["status_prod"])."' \r\n";
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

	/**
	*/
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=manage_shop&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=manage_shop&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse("manage_shop/filter", $replace);
	}

	/**
	*/
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

	/**
	*/
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) {
				unset($_SESSION[$this->_filter_name]);
			}
		}
		if (!$silent) {
			js_redirect("./?object=manage_shop&action=products_manage"._add_get());
		}
	}
}

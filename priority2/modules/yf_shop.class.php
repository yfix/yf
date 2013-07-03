<?php

/**
* Shop module
*/
class yf_shop extends yf_module {

	/* Test CC numbers:

	370000000000002 American Express Test Card
	6011000000000012 Discover Test Card
	5424000000000015 MasterCard Test Card
	4007000000027 Visa Test Card
	4012888818888 second Visa Test Card
	3088000000000017 JCB 
	38000000000006 Diners Club/ Carte Blanche
	*/

	/** @var string Folder where product's images store */
	public $PROD_IMG_DIR		= "shop/products/";
	/** @var string fullsize image suffix (underscore at the beginning required)*/
	public $FULL_IMG_SUFFIX	= "_full";
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $THUMB_SUFFIX		= "_small";
	/** @var string Image prefix */
	public $IMG_PREFIX			= "product_";
	/** @var string Default currency */
	public $CURRENCY			= "\$";
	/** @var bool SHOW_SIMILAR_PRICE or not */
	public $SHOW_SIMILAR_PRICE		= true;
	/** @var bool THIS_ITEM_OFTEN_BUY or not */
	public $THIS_ITEM_OFTEN_BUY		= true;
	 /** @var array forum settings (default values) */
	public $COMPANY_INFO = array(
		"company_name"		=> "Shop.com ", //
		"company_address"	=> "Company Address 1", //
		"company_address2"	=> "Company Address 2", //
		"company_phone"		=> "Company Phone", //
		"company_website"	=> "Company Website", //
		"company_email"		=> "Company Email", //
		"company_title"		=> "Shop.com ", //
	);
	/** @var Billing info */
	public $_b_fields = array(
		"name",
		"email",
		"phone",
		"address",
		"comment_c",
	);
	/** @var Shipping info */
	public $_s_fields = array(
		"s_first_name",
		"s_last_name",
		"s_email",
		"s_phone",
		"s_address",
		"s_address2",
		"s_zip_code",
		"s_city",
		"s_state",
		"s_country",
		"s_company",
	
	);
	/** @var Required shipping and billing fields */
	public $_required_fields = array(
		"name",
		"phone",
		);
	/** @var @conf_skip */
	public $_statuses = array(
		"pending"			=> "pending",
		"pending payment"	=> "pending payment",
		"proccessed"		=> "proccessed",
		"delivery"			=> "delivery",
		"shipped"			=> "shipped",
	);
	public $_ship_type = array(
		1 => "Free",
		2 => "Courier",
		3 => "FedEX",
		4 =>  "Post",
	);
	/** @var Shipping types */
	public $_ship_types = array(
		1	=> array(
			"name"	=> "Free",
			"price"		=> 0,
		),
		2	=> array(
			"name"	=> "Courier",
			"price"		=> 1,
		),
		3	=> array(
			"name"	=> "FedEX",
			"price"		=> 5,
		),
		4	=> array(
			"name"	=> "Post",
			"price"		=> 1,
		),
	);
	/** @var Shipping types names (creating automatically inside "_init") @conf_skip */
	public $_ship_types_name = array();
	/** @var Payment types */
	public $_pay_types = array(
		1 => "Cash On Delivery",
		2 => "Authorize.Net",
		3 => "Bank Transfer",
		4 => "Cheque / Money Order",
	);
	/** @var Payment methods params */
	public $_pay_method_params = array(
		2	=> array( // Authorize.Net
			"LOGIN_ID"			=> "7wYB5c6R",
			"TRANSACTION_KEY"	=> "4px54kx6ZZ7489Gq",
			"TEST_MODE"			=> 1,
			"IN_PRODUCTION"		=> 0,
			"DESCRIPTION"		=> "Shop Description Here",
		),
	);

	/** @var Force payment method (Set to 0 to disable) */
	public $FORCE_PAY_METHOD	= 0;
	/** @var Inline registration */
	public $INLINE_REGISTER	= true;
	/** @var */
	public $ATTRIBUTES_CAT_ID	= 1;
	/** @var Force ship method for user group (user_group => ship_type) */
	public $FORCE_GROUP_SHIP	= array(
		//3	=> 3,
	);
	/** @var Force payment method for user group (user_group => pay_type) */
	public $FORCE_GROUP_PAY	= array(
		//3	=> 1,
	);
	var  $_comments_params = array(
		"return_action"		=> "product_details",
		"object_name"		=> "shop",
		"allow_guests_posts"=> '1',
	);

	/**
	* Constructor
	*/
	function _init () {
		define("SHOP_CLASS_NAME", "shop");

		$this->_shop_cats				= _class('cats')->_get_items_names("shop_cats");
		$this->_shop_cats_all			= _class('cats')->_get_items_array("shop_cats");
		$this->_shop_cats_for_select	= _class('cats')->_prepare_for_box("shop_cats");
		// Get manufacturer
		$sql_man = "SELECT * FROM `".db('shop_manufacturer')."` ORDER BY `name` ASC";
		$this->_manufacturer = db()->query_fetch_all($sql_man);
		// manufacturer for the select box
		$this->_man_for_select["none"] = "--NONE--";
		foreach ((array)$this->_manufacturer as $k =>$v) {
			$this->_man_for_select[$v["url"]] = $v["name"];
		}
		$this->_man_id = "none";
		$this->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		$this->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		if (!file_exists($this->products_img_dir)) {
			_mkdir_m($this->products_img_dir);
		}
		// Array of select boxes to process
		$this->_boxes = array(
			"ship_type"	=> 'select_box("ship_type", $this->_ship_types_names, $selected, false, 2, "", false)',
			"pay_type"	=> 'radio_box("pay_type", $this->_pay_types, $selected, 1, 2, "", false)',
		);
		// Prepare shipping methods names
		$this->_ship_types_names = array();
		foreach ((array)$this->_ship_types as $_id => $_info) {
			$_price_text = " (".($_info["price"] < 0 ? "-" : "+"). $this->_format_price(abs($_info["price"])).")";
			$this->_ship_types_names[$_id] = $_info["name"]. ($_info["price"] ? $_price_text : "");
		}
		// Override pay type for group
		$force_group_pay_type = $this->FORCE_GROUP_PAY[$this->USER_GROUP];
		if ($force_group_pay_type/* && isset($this->_pay_types[$force_group_pay_type])*/) {
			$this->FORCE_PAY_METHOD = $force_group_pay_type;
		}
	}

	/**
	* Default method
	*/
	function show() {
		return $this->show_products();
	}

	/**
	* View products page (with categories)
	*/
	function show_products($search = "", $str_search = "") {
		foreach ((array)$this->_shop_cats as $_cat_id => $_cat_name) {
			if ($_GET['id'] == $this->_shop_cats_all[$_cat_id]['url'] && $_GET['id'] != "" ) {
				$_GET['id'] = $_cat_id;
				$_show_by_cat = 1;
				$cat_name = $_cat_name;
			}
		}
		foreach ((array)$this->_manufacturer as $_man_id => $_man_name) {
			if ($_GET['id'] == "none") {
				$_GET['id'] = "";
				$_SESSION['man_id'] =   "none";
			}else if ($_GET['id'] == $_man_name['url']) {
				$_GET['id'] = $_man_id;
				$_show_by_man = 1;
				$cat_name = $_man_name['name'];
				$_SESSION['man_id'] =  $_man_name['url'];
			}
		}
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) { 
			$cat_child = $_GET["id"].",";
			$cat_child .= $this->_get_children_cat ( $_GET["id"]);
			$cat_child = rtrim($cat_child, ",");
			$sql1 = "SELECT `product_id` FROM `".db('shop_product_to_category')."` WHERE `category_id` IN ( ". $cat_child. ")";
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A["product_id"].",";
			}	
			$product_info = rtrim($product_info, ",");
		}
		if ($product_info == "") {
			$product_info = 0;
		}
		if ($search == "" && $str_search =="") {
			if ($_show_by_cat == 1){
				$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' ".($_GET["id"] ? " AND `id` IN (".$product_info .")" : " AND `featured`='1'")." ORDER BY `add_date`";
			}else if ($_show_by_man == 1) {
				$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' ".($_GET["id"] ? " AND `manufacturer_id` = " . $_GET["id"]  : " AND `featured`='1'")." ORDER BY `add_date`";
			}
		} elseif ($search == "" && $str_search !="") {
			
		} else {
			$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND `id` IN (".$search .")  ORDER BY `add_date`";
		}
		if ($sql != ""){
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$product_info = db()->query_fetch_all($sql.$add_sql);
		}
		if (!empty($product_info)) {
			$group_prices = $this->_get_group_prices(array_keys($product_info));
		}
		$items = array();
		$counter = 1;
		foreach ((array)$product_info as $v) {
			$dirs = sprintf("%06s",$v["id"]);
			$dir2 = substr($dirs,-3,3);
			$dir1 = substr($dirs,-6,3);
			$mpath = $dir1."/".$dir2."/";
			$thumb_path = $v["url"]."_".$v["id"]."_1".$this->THUMB_SUFFIX.".jpg";
			$img_path = $v["url"]."_".$v["id"]."_1".$this->FULL_IMG_SUFFIX.".jpg";
			$v["_group_price"] = $group_prices[$v["id"]][$this->USER_GROUP];
			$URL_PRODUCT_ID = $this->_product_id_url($v);
			$items[$v["id"]] = array(
				"name"				=> _prepare_html($v["name"]),
				"desc"				=> _prepare_html($v["description"]),
				"date"				=> _format_date($v["add_date"], "long"),
				"price"				=> $this->_format_price($this->_get_product_price($v)),
				"currency"			=> _prepare_html($this->CURRENCY),
				"thumb_path"		=> file_exists($this->products_img_dir.$mpath. $thumb_path)? $this->products_img_webdir. $mpath.$thumb_path : "",
				"img_path"			=> file_exists($this->products_img_dir. $mpath.$img_path)	? $this->products_img_webdir.$mpath. $img_path : "",
				"add_to_cart_url"	=> ($v["external_url"]) ? $v["external_url"] : process_url("./?object=shop&action=add_to_cart&id=".$URL_PRODUCT_ID),
				"external_url"		=> intval((bool)$v["external_url"]),
				"details_url"		=> ($v["external_url"]) ? $v["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"counter"			=> $counter,
			);
			if ($counter == 4) {
				$counter = 1;
			} else {
				++ $counter;
			}
		}
		if (empty($items)) {
			$items = "";
		}
		$replace = array(
			"search_string"	=> $str_search,
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> $total,
			"currency"		=> _prepare_html($this->CURRENCY),
			"show_cart_url"	=> process_url("./?object=shop&action=cart"),
			"cur_cat_id"	=> intval($_GET["id"]),
			"cur_cat_name"	=> _prepare_html($cat_name),
			"cats_block"	=> $this->_show_shop_cats(),
		);
		return tpl()->parse("shop/main", $replace);
	}

	/**
	* View product details
	*/
	function product_details() {
		if (!$_GET["id"]) {
			return is_redirect("./?object=shop");
		}
		// Get products from database
		if (is_numeric($_GET["id"] )) {
			$add_sql = "`id`= '".intval($_GET["id"]);
		} else {
			$add_sql = "`url`='"._es($_GET['id']);
		}
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		
		// Required for comments
		$this->_comments_params["object_id"] = $product_info["id"];
		$this->_comments_params["objects_ids"] = $product_info["id"];
		$N = $this-> _get_num_comments();
		$N = $N[$product_info["id"]];
		if ($N =="") {
			$N = 0;
		}
		$dirs = sprintf("%06s",$product_info["id"]);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		$group_prices = $this->_get_group_prices($product_info["id"]);
		$product_info["_group_price"] = $group_prices[$this->USER_GROUP];
		$this->_product_info = $product_info;
		$atts = $this->_get_products_attributes($product_info["id"]);
		$thumb_path = $product_info["url"]."_".$product_info["id"]."_".$product_info["image"].$this->THUMB_SUFFIX.".jpg";
		$img_path = $product_info["url"]."_".$product_info["id"]."_".$product_info["image"].$this->FULL_IMG_SUFFIX.".jpg";
		if ($product_info["image"] == 0) {
			$image = "";
		} else {
			$image_files = _class('dir')->scan_dir($this->products_img_dir.$mpath, true, "/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/");
			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
			foreach ((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult["content"];
				if ($i != $product_info["image"]) {
					$thumb_temp = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->THUMB_SUFFIX.".jpg";
					$img_temp = $this->products_img_webdir.$mpath.$product_info["url"]."_".$product_info["id"]."_".$i.$this->FULL_IMG_SUFFIX.".jpg";
					$replace2 = array(
						"thumb_path"=> $thumb_temp,
						"img_path" 	=> $img_temp,
						"name"		=> $product_info["url"],
					);
					$image .= tpl()->parse("shop/image_items", $replace2);
				}
			}
		}	
		$URL_PRODUCT_ID = $this->_product_id_url($product_info);
		$sql_man = "SELECT * FROM `".db('shop_manufacturer')."` WHERE `id` = ".$product_info["manufacturer_id"];
		$manufacturer = db()->query_fetch($sql_man);
		if ($this->SHOW_SIMILAR_PRICE == true){
			$similar_price = $this->similar_price ( $product_info["price"],  $product_info["id"] );
		}
		if ($this->THIS_ITEM_OFTEN_BUY == true){
			$this_item_often_buy = $this->this_item_often_buy ( $product_info["id"] );
		}
		$replace = array(
			"name"					=> _prepare_html($product_info["name"]),
			"model"					=> _prepare_html($product_info["model"]),
			"desc"					=> $product_info["description"],
			"manufacturer"			=>	_prepare_html($this->_manufacturer[$product_info["manufacturer_id"]]["name"]),
			"url_manufacturer"		=> process_url("./?object=shop&action=show_products&id=".$this->_manufacturer[$product_info["manufacturer_id"]]["url"]),
			"date"					=> _format_date($product_info["add_date"], "long"),
			"price"					=> $this->_format_price($this->_get_product_price($product_info)),
			"currency"				=> _prepare_html($this->CURRENCY),
			"thumb_path"			=> file_exists($this->products_img_dir.$mpath. $img_path)	? $this->products_img_webdir. $mpath.$img_path : "",
			"img_path"				=> file_exists($this->products_img_dir. $mpath.$img_path)	? $this->products_img_webdir. $mpath.$img_path : "",
			"image"					=> $image,
			"add_to_cart_url"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=add_to_cart&id=".$URL_PRODUCT_ID),
			"external_url"			=> intval((bool)$product_info["external_url"]),
			"back_url"				=> process_url("./?object=shop"),
			"show_cart_url"			=> process_url("./?object=shop&action=cart"),
			"dynamic_atts"			=> $this->_get_select_attributes($atts),
			"cats_block"			=> $this->_show_shop_cats(),
			"cat_name"				=> _prepare_html($this->_shop_cats[$product_info["cat_id"]]),
			"cat_url"				=> process_url("./?object=shop&action=".__FUNCTION__."&id=".($this->_shop_cats_all[$product_info["cat_id"]]['url'])),
			'comments'				=> $this->_view_comments(),
			"N"						=> $N,
			"similar_price"			=> $similar_price,
			"this_item_often_buy"	=> $this_item_often_buy,
			"product_related"		=> $this->get_product_related($product_info["id"]),
		);
		db()->query("UPDATE `".db('shop_products')."` SET `viewed` = `viewed`+1 , `last_viewed_date` = ".time()."  WHERE ".$add_sql."'");
		return tpl()->parse("shop/details", $replace);
	}

	/**
	*/
	function _get_children_cat ($id) {
		$sql1 =	"SELECT `id` FROM `shop_sys_category_items` WHERE `parent_id` = ". $id;
		$cat = db()->query($sql1);
		while ($A = db()->fetch_assoc($cat)) {
			$cat_id .= $A["id"].",";
			$sql2 =	"SELECT `id` FROM `shop_sys_category_items` WHERE `parent_id` = ". $A["id"];
			$res_q = db()->query($sql2);
			if (db()->num_rows($res_q)) {
				$this->_get_children_cat ( $A["id"]);	
			}
		}	
		$cat_id = rtrim($cat_id, ",");
		return $cat_id;
	}
	
	/**
	*get_product_related
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
			$product_related = $this->show_products($product_related_id); 
		}
		return $product_related;
		
	}	

	/**
	*similar_price
	*/
	function similar_price ($price, $id) {
		$price_min =  floor($price - ($price  *10/100));
		$price_max =  ceil($price +($price *10/100));
		$sql1 = "SELECT `category_id` FROM `".db('shop_product_to_category')."` WHERE `product_id` =  ".$id. "";
		$cat_id = db()->query($sql1);
		while ($A = db()->fetch_assoc($cat_id)) {
			$cats_id .= $A["category_id"].",";
		}	
		$cats_id = rtrim($cats_id, ",");
		$sql2 = "SELECT `product_id` FROM `".db('shop_product_to_category')."` WHERE `category_id` IN ( ".$cats_id. ")";
		$prod = db()->query($sql2);
		while ($A = db()->fetch_assoc($prod)) {
			$prods .= $A["product_id"].",";
		}	
		$prods = rtrim($prods, ",");
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `price`  >". $price_min ." AND  `price` < ".$price_max ." AND `id` != ". $id. " AND `id` in ( ".$prods.")";
		$product = db()->query_fetch_all($sql);
		foreach ((array)$product as $k => $product_info){
			$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".$this->THUMB_SUFFIX.".jpg";
			$URL_PRODUCT_ID = $this->_product_id_url($product_info);
			$items[$product_info["id"]] = array(
				"name"		=> _prepare_html($product_info["name"]),
				"price"		=> $this->_format_price($this->_get_product_price($product_info)),
				"currency"	=> _prepare_html($this->CURRENCY),
				"image"		=> file_exists($this->products_img_dir. $thumb_path)? $this->products_img_webdir. $thumb_path : "",
				"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"special" 	=>  "",			
			);
		}
		$replace = array(
			"items"	=> $items,
			"title"	=> "Similar price",
		);
		return  tpl()->parse("shop/similar_price", $replace);
		
	}
	
	function this_item_often_buy ($id) {
		$sql_order_id = "SELECT `order_id` FROM `".db('shop_order_items')."` WHERE `product_id` =  ".$id;
		$orders = db()->query($sql_order_id);
		while ($A = db()->fetch_assoc($orders))
			{
				$order_id .= $A["order_id"].",";
			}	
			$order_id = rtrim($order_id, ",");
			if (!empty($order_id)) {
				$sql_product_id = "SELECT `product_id` FROM `".db('shop_order_items')."` WHERE  `order_id` IN (  ".$order_id.") AND `product_id` != ". $id;
				$products = db()->query($sql_product_id);
				while ($A = db()->fetch_assoc($products))
				{
					$product_id .= $A["product_id"].",";
				}	
				$product_id = rtrim($product_id, ","); 
			}
			if (!empty($product_id)) {
			$sql = "SELECT * FROM `".db('shop_products')."` WHERE  `id` in ( ".$product_id.")";
			$product = db()->query_fetch_all($sql);
			foreach ((array)$product as $k => $product_info){
				$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".$this->THUMB_SUFFIX.".jpg";
				$URL_PRODUCT_ID = $this->_product_id_url($product_info);
					$items[$product_info["id"]] = array(
						"name"		=> _prepare_html($product_info["name"]),
						"price"		=> $this->_format_price($this->_get_product_price($product_info)),
						"currency"	=> _prepare_html($this->CURRENCY),
						"image"		=> file_exists($this->products_img_dir. $thumb_path)? $this->products_img_webdir. $thumb_path : "",
						"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
						"special" 	=>  "",			
					);
				}
		}
		$replace = array(
			"items"	=> $items,
			"title"	=> "Those who purchased this product also buy",
		);
		return  tpl()->parse("shop/similar_price", $replace);
	}
	
	/**
	* Clean cart contents
	*/
	function add_to_cart() {
		return $this->_call_sub_method("shop_cart", __FUNCTION__);
	}

	/**
	* Display cart contents (save changes also here)
	*/
	function cart($params = array()) {
		return $this->_call_sub_method("shop_cart", __FUNCTION__, $params);
	}
	
	/**
	* show_cart_main
	*/
	function show_cart_main($params = array()) {
		return $this->_call_sub_method("shop_cart", __FUNCTION__, $params);
	}

	/**
	* Display cart contents (usually for side block)
	*/
	function _cart_side() {
		return $this->_call_sub_method("shop_cart", __FUNCTION__);
	}

	/**
	* Save cart
	*/
	function _save_cart_all() {
		return $this->_call_sub_method("shop_cart", __FUNCTION__);
	}

	/**
	* Clean cart contents
	*/
	function clean_cart() {
		return $this->_call_sub_method("shop_cart", __FUNCTION__);
	}

	/**
	* Order products from cart
	*/
	function order() {
		if (!$this->USER_ID) {
// TODO
//			if (!$this->INLINE_REGISTER) {
//			} else {
//				return _error_need_login("./?object=shop&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""). ($_GET["page"] ? "&page=".$_GET["page"] : ""));
//			}
		}
		$_avail_steps = array(
			"start",
			"delivery",
			"select_payment",
			"do_payment",
			"finish",
		);
		// Switch between checkout steps
		$step = $_GET["id"];
		if (!$step || !in_array($step, $_avail_steps)) {
			$step = "start";
		}
		// Prevent ordering with empty shopping cart
		$cart = &$_SESSION["SHOP_CART"];
		if (empty($cart) && in_array($step, array("start", "delivery", "select_payment"))) {
			return js_redirect("./?object=shop");
		}
		$func = "_order_step_". $step;
		return $this->$func();
	}

	/**
	* show Orders
	*/
	function show_orders($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", "_show_orders", $FORCE_DISPLAY_FORM);
	}
	
	/**
	* view Order
	*/
	function validate_order_data($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", "_validate_order_data", $FORCE_DISPLAY_FORM);
	}
	/**
	* view Order
	*/
	function view_order($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", "_view_order", $FORCE_DISPLAY_FORM);
	}
	
	/**
	* delete Order
	*/
	function delete_order($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", "_delete_order", $FORCE_DISPLAY_FORM);
	}
	
	function _order_step_start($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", __FUNCTION__, $FORCE_DISPLAY_FORM);
	}

	/**
	* Order step
	*/
	function _order_step_delivery($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", __FUNCTION__, $FORCE_DISPLAY_FORM);
	}

	/**
	* Order validation
	*/
	function _order_validate_delivery() {
		return $this->_call_sub_method("shop_order", __FUNCTION__);
	}

	/**
	* Order step
	*/
	function _order_step_select_payment($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", __FUNCTION__, $FORCE_DISPLAY_FORM);
	}

	/**
	* Order validation
	*/
	function _order_validate_select_payment() {
		return $this->_call_sub_method("shop_order", __FUNCTION__);
	}

	/**
	* Create order record (1 db('shop_orders'), multiple db('shop_order_items'))
	*/
	function _create_order_record() {
		return $this->_call_sub_method("shop_order", __FUNCTION__);
	}

	/**
	* Order step
	*/
	function _order_step_do_payment($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", __FUNCTION__, $FORCE_DISPLAY_FORM);
	}

	/**
	* Order validation
	*/
	function _order_validate_do_payment() {
		return $this->_call_sub_method("shop_order", __FUNCTION__);
	}

	/**
	* Order step
	*/
	function _order_step_finish($FORCE_DISPLAY_FORM = false) {
		return $this->_call_sub_method("shop_order", __FUNCTION__, $FORCE_DISPLAY_FORM);
	}

	/**
	* Order payment method by authorize.net
	*/
	function _order_pay_authorize_net($order_info = array(), $params = array()) {
		return $this->_call_sub_method("shop_payment", __FUNCTION__, array(
			"order_info"	=> $order_info,
			"params"			=> $params,
		));
	}

	/**
	* Payment callback method
	*/
	function payment_callback() {
//		main()->NO_GRAPHICS = true;
		_debug_log(print_r($_POST, 1));
	}

	/**
	* View products page (with categories)
	*/
	function _format_price($price = 0) {
		$price = number_format($price, 2, '.', ' ');
		if ($this->CURRENCY == "\$") {
			return $this->CURRENCY."&nbsp;".$price;
		} else {
			return $price."&nbsp;".$this->CURRENCY;
		}
	}

	/**
	* Display hidden field
	*/
	function _hidden_field($name = "", $value = "") {
		if (is_array($name)) {
			$result = "";
			$func_name = __FUNCTION__;
			foreach ((array)$name as $k => $v) {
				$result .= $this->$func_name($k, $v);
			}
			return $result;
		}
		if (empty($name)) {
			return "";
		}
		return "<input type=\"hidden\" name=\""._prepare_html($name)."\" value=\""._prepare_html($value)."\" />\n";
	}

	/**
	* Abstraction layer
	*/
	function _product_id_url($product_info = array()) {
		return strlen($product_info["url"]) ? $product_info["url"] : $product_info["id"];
	}

	/**
	* Get price for product (allowing to inherit this method separately)
	*/
	function _get_product_price ($product_info = array()) {
		return $product_info["_group_price"] ? $product_info["_group_price"] : $product_info["price"];
	}

	/**
	* Get prices special for group
	*/
	function _get_group_prices ($product_ids = array()) {
		if (is_numeric($product_ids)) {
			$return_single = $product_ids;
			$product_ids = array($product_ids);
		}
		// Get user groups (id > 2 - skip guest and member)
		$user_groups = main()->get_data("user_groups");
		if (isset($user_groups[1])) {
			unset($user_groups[1]);
		}
		if (isset($user_groups[2])) {
			unset($user_groups[2]);
		}
		if (empty($user_groups) || empty($product_ids)) {
			return false;
		}
		$group_prices = array();
		// Get prices per group
		$Q = db()->query(
			"SELECT * FROM `".db('shop_group_options')."` 
			WHERE `product_id` IN (".implode(",", $product_ids).") 
				AND `group_id` IN (".implode(",", array_keys($user_groups)).")"
		);
		while($A = db()->fetch_assoc($Q)) {
			if (!isset($user_groups[$A["group_id"]])) {
				continue;
			}
			$group_prices[$A["product_id"]][$A["group_id"]] = floatval($A["price"]);
		}
		if ($return_single) {
			return $group_prices[$return_single];
		}
		return $group_prices;
	}

	/**
	* show short search form
	*/
	function _short_search_form() {
		return $this->_call_sub_method("shop_search", __FUNCTION__);
	}
	
	/**
	* fast search 
	*/
	function search() {
		return $this->_call_sub_method("shop_search", __FUNCTION__);
	}

	/**
	* Show shop categories block
	*/
	function _show_shop_cats() {
		// Prepare categories
		$shop_cats = array();
		foreach ((array)$this->_shop_cats_for_select as $_cat_id => $_cat_name) {
			if (!$_cat_name) {
				continue;
			}
			$shop_cats[_prepare_html($_cat_name)] = process_url("./?object=shop&action=show&id=".($this->_shop_cats_all[$_cat_id]['url']));
		}
		if (empty($shop_cats)) {
			$shop_cats = "";
		}
		return tpl()->parse("shop/cats_block", array("shop_cats" => $shop_cats));
	}

	/**
	*/
	function _show_shop_manufacturer () {
		// Prepare manufacturer
		$replace = array(
			"brand" 			=>	$this->_manufacturer,
			"manufacturer_box"	=> common()->select_box("manufacturer", $this->_man_for_select, $_SESSION['man_id'] , false, 2),
			"url_manufacturer"	=> process_url("./?object=shop&action=show_products"),
		);
		unset($_SESSION["man_id"]);
		return tpl()->parse("shop/manufacturer", $replace);
	}
	
	/**
	* show shop best sales
	*/
	function _show_shop_best_sales () {
		// Prepare categories
		$sql_prod_id = "SELECT `product_id`,  COUNT(quantity)  FROM `". db('shop_order_items') ."`  GROUP BY  `product_id` ORDER BY COUNT(quantity) DESC LIMIT 0 , 5";	
		$item_prod_id = db()->query_fetch_all($sql_prod_id);
		$items = array();
		foreach ((array)$item_prod_id as $k => $v){
			$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND  `id` = ".$v["product_id"];
			$product_info = db()->query_fetch($sql);
			$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".$this->THUMB_SUFFIX.".jpg";
			$URL_PRODUCT_ID = $this->_product_id_url($product_info);
			$items[$product_info["id"]] = array(
				"name"		=> _prepare_html($product_info["name"]),
				"price"		=> $this->_format_price($this->_get_product_price($product_info)),
				"currency"	=> _prepare_html($this->CURRENCY),
				"image"		=> file_exists($this->products_img_dir. $thumb_path)? $this->products_img_webdir. $thumb_path : "",
				"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"special" 	=>  "",			
			);
		}
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse("shop/best_sales", $replace);
	}
	
	/**
	* show shop last viewed
	*/
	function _show_shop_last_viewed () {
		$sql_prod_id = "SELECT * FROM  `". db('shop_products') ."`  ORDER BY `last_viewed_date`  DESC LIMIT 5";	
		$item_prod_id = db()->query_fetch_all($sql_prod_id);
		$items = array();
		foreach ((array)$item_prod_id as $k => $product_info){
			$thumb_path = $product_info["url"]."_".$product_info["id"]."_1".$this->THUMB_SUFFIX.".jpg";
			$URL_PRODUCT_ID = $this->_product_id_url($product_info);
			$items[$product_info["id"]] = array(
				"name"		=> _prepare_html($product_info["name"]),
				"price"		=> $this->_format_price($this->_get_product_price($product_info)),
				"currency"	=> _prepare_html($this->CURRENCY),
				"image"		=> file_exists($this->products_img_dir. $thumb_path)? $this->products_img_webdir. $thumb_path : "",
				"link"		=> ($product_info["external_url"]) ? $product_info["external_url"] : process_url("./?object=shop&action=product_details&id=".$URL_PRODUCT_ID),
				"special" 	=>  "",			
			);
		}
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse("shop/last_viewed", $replace);
	}
	
	/**
	* Get products attributes
	*/
	function _get_products_attributes($products_ids = array()) {
		if (is_numeric($products_ids)) {
			$return_single_id = $products_ids;
			$products_ids = array($products_ids);
		}
		if (empty($products_ids)) {
			return array();
		}

		$fields_info = main()->get_data("dynamic_fields_info");

		$Q = db()->query("SELECT * FROM `".db('dynamic_fields_values')."` WHERE `category_id`=1 AND `object_id` IN (".implode(",", $products_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$_product_id = $A["object_id"];

			$A["value"]		= strlen($A["value"]) ? unserialize($A["value"]) : array();
			$A["add_value"] = strlen($A["add_value"]) ? unserialize($A["add_value"]) : array();
			foreach ((array)$A["value"] as $_attr_id => $_dummy) {
				$_price = $A["add_value"][$_attr_id];
				$_item_id = $A["field_id"]."_".$_attr_id;
				$_field_info = $fields_info[$this->ATTRIBUTES_CAT_ID][$A["field_id"]];
				$_field_info["value_list"] = strlen($_field_info["value_list"]) ? unserialize($_field_info["value_list"]) : array();
				$data[$_product_id][$_item_id] = array(
					"id" 			=> $_item_id,
					"price"			=> $_price,
					"name"			=> $_field_info["name"],
					"value"			=> $_field_info["value_list"][$_attr_id],
					"product_id"	=> $_product_id,
				);
			}
		}
		if ($return_single_id) {
			return $data[$return_single_id];
		}
		return $data;
	}

	/**
	* Prepare products attributes for selection
	*/
	function _get_select_attributes($atts = array()) {
		if (empty($atts)) {
			return array();
		}
		// Group by attribute name
		$_atts_by_name = array();
		foreach ((array)$atts as $_info) {
			$_atts_products_ids[$_info["name"]] = $_info["product_id"];
			$_price_text = " (".($_info["price"] < 0 ? "-" : "+"). $this->_format_price(abs($_info["price"])).")";
			$_atts_by_name[$_info["name"]][$_info["value"]] = $_info["value"]. ($_info["price"] ? $_price_text : "");
		}
		$result = array();
		foreach ((array)$_atts_by_name as $_name => $_info) {
			$_product_id = $_atts_products_ids[$_name];
			$_box = "";
			$_box_name = "atts[".intval($_product_id)."][".$_name."]";
			if (count($_info) > 1) {
				$_box = common()->select_box($_box_name, $_info, $selected, false, 2, "", false);
			} else {
				$_box = current($_info)."\n<input type=\"hidden\" name=\"".$_box_name."\" value=\""._prepare_html(current($_info))."\" />";
			}
			$result[$_name] = array(
				"name"	=> _prepare_html($_name),
				"box"	=> $_box,
			);
		}
		return $result;
	}

	/**
	* Call sub_module method
	*/
	function _call_sub_method ($sub_module = "", $method_name = "", $params = array()) {
		$OBJ = main()->init_class($sub_module, USER_MODULES_DIR. "shop/");
		if (!is_object($OBJ)) {
			trigger_error("SHOP: Cant load sub_module \"".$sub_module."\"", E_USER_WARNING);
			return false;
		}
		return is_object($OBJ) ? $OBJ->$method_name($params) : "";
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Shopping cart",
				"url" 		=> "./?object=shop&action=cart",
			),
		);
		return $menu;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"		=> "",
			"cart"		=> t("Shopping Cart"),
			"order"		=> t("Checkout"),
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : t(_ucwords(SHOP_CLASS_NAME)),
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}

	/**
	* Title hook
	*/
	function _site_title($title) {
		$title = $this->COMPANY_INFO["company_title"] ? $this->COMPANY_INFO["company_title"] : $this->COMPANY_INFO["company_name"];
		$subtitle = "";
		if (in_array($_GET["action"], array("show","show_products")) && $_GET["id"]) {
			$subtitle .= $this->_shop_cats[$_GET["id"]];
		} elseif (in_array($_GET["action"], array("product_details")) /* && $_GET["id"] */) {
			$man = $this->_manufacturer[$this->_product_info["manufacturer_id"]]["name"] ;
			$subtitle .= $this->_product_info["name"]." - ". $man;
		}
		if ($subtitle) {
			$title = $subtitle ." - ". $title;
		}
		return $title;
	}
	
	/**
	* meta_tags hook
	*/
	function _hook_meta_tags ($meta) {
		if (in_array($_GET["action"], array("show","show_products")) && $_GET["id"]) {
			$subtitle .= $this->_shop_cats[$_GET["id"]];
		} elseif (in_array($_GET["action"], array("product_details")) /* && $_GET["id"] */) {
			$meta["keywords"] = $this->_product_info["meta_keywords"];
			$meta["description"] = $this->_product_info["meta_desc"];
		}
		return $meta;	
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($SITE_MAP_OBJ = false) {
// TODO
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
// TODO
	}
}

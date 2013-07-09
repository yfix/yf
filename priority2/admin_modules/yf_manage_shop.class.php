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

	/** @var string Folder where product's images store */
#	public $MAN_IMG_DIR		= "shop/manufacturer/";
	/** @var string fullsize image suffix (underscore at the beginning required)*/
#	public $FULL_IMG_SUFFIX	= "_full";
	/** @var string Thumb image suffix (underscore at the beginning required)*/
#	public $THUMB_SUFFIX		= "_small";
	/** @var string Image prefix */
//	public $IMG_PREFIX			= "product_";
	/** @var int Thumb size X */
#	public $THUMB_X			= 100;
	/** @var int Thumb size Y */
#	public $THUMB_Y			= 100;

	/**
	* Constructor
	*/
	function _init() {
		module("manage_shop")->_cats_for_select	= _class('cats')->_prepare_for_box("shop_cats", 0);
		
		module("manage_shop")->man = db()->query_fetch_all("SELECT * FROM ".db('shop_manufacturer')." ORDER BY name ASC");
		module("manage_shop")->_man_for_select[0] = "--NONE--";
		foreach ((array)module("manage_shop")->man as $k =>$v) {
			module("manage_shop")->_man_for_select[$v["id"]] = $v["name"];
		}
		module("manage_shop")->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. module("manage_shop")->PROD_IMG_DIR;
		module("manage_shop")->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. module("manage_shop")->PROD_IMG_DIR;
		if (!file_exists(module("manage_shop")->products_img_dir)) {
			_mkdir_m(module("manage_shop")->products_img_dir);
		}
		module("manage_shop")->_boxes = array(
			"status"		=> 'select_box("status",		module("manage_shop")->_statuses,	$selected, false, 2, "", false)',
			"featured"		=> 'radio_box("featured",		module("manage_shop")->_featured,	$selected, false, 2, "", false)',
			"status_prod"	=> 'select_box("status_prod",	module("manage_shop")->_status_prod,$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		module("manage_shop")->_sort_by,	$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order", 	module("manage_shop")->_sort_orders,$selected, 0, 2, "", false)',
		);
		module("manage_shop")->_featured = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		module("manage_shop")->_status_prod = array(
			""		=> "",
			"1"	=> "Active",
			"0"	=> "Inacive",
		);
		module("manage_shop")->_sort_orders = array(""	=> "", "DESC" => "Descending", "ASC" => "Ascending");
		module("manage_shop")->_sort_by = array(
			""			=> "",
			"name"		=> "Name",
			"price" 	=> "Price",
			"quantity" 	=> "Quantity",
			"add_date" 	=> "Date",
			"active" 	=> "Status",
		);
		if (module("manage_shop")->USE_FILTER) {
			module("manage_shop")->_prepare_filter_data();
		}
		// Sync company info with user section
#		module("manage_shop")->COMPANY_INFO = _class("shop", "modules/")->COMPANY_INFO;

//		$this->manufacturer_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
//		$this->manufacturer_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
	}

	function show() {
		return _class('manage_shop_show', 'admin_modules/manage_shop/')->show();
	}

	function home() {
		return _class('manage_shop_home', 'admin_modules/manage_shop/')->home();
	}

	function statistic() {
		return _class('manage_shop_statistic', 'admin_modules/manage_shop/')->statistic();
	}

	function products_manage() {
		return _class('manage_shop_products_manage', 'admin_modules/manage_shop/')->products_manage();
	}

	function product_add() {
		return _class('manage_shop_product_add', 'admin_modules/manage_shop/')->product_add();
	}

	function product_edit() {
		return _class('manage_shop_product_edit', 'admin_modules/manage_shop/')->product_edit();
	}

	function product_view() {
		return _class('manage_shop_product_view', 'admin_modules/manage_shop/')->product_view();
	}

	function product_delete() {
		return _class('manage_shop_product_delete', 'admin_modules/manage_shop/')->product_delete();
	}

	function product_activate() {
		return _class('manage_shop_product_activate', 'admin_modules/manage_shop/')->product_activate();
	}

	function image_upload() {
		return _class('manage_shop_image_upload', 'admin_modules/manage_shop/')->image_upload();
	}

	function _image_upload($product_id, $product_name) {
		return _class('manage_shop__image_upload', 'admin_modules/manage_shop/')->_image_upload($product_id, $product_name);
	}

	function _check_filed($path, $product_id, $product_name, $i) {
		return _class('manage_shop__check_filed', 'admin_modules/manage_shop/')->_check_filed($path, $product_id, $product_name, $i);
	}

	function _image_delete($id, $name, $k) {
		return _class('manage_shop__image_delete', 'admin_modules/manage_shop/')->_image_delete($id, $name, $k);
	}

	function image_delete() {
		return _class('manage_shop_image_delete', 'admin_modules/manage_shop/')->image_delete();
	}

	function show_product_by_category($cat = "") {
		return _class('manage_shop_show_product_by_category', 'admin_modules/manage_shop/')->show_product_by_category($cat);
	}

	function get_product_related($id = "") {
		return _class('manage_shop_get_product_related', 'admin_modules/manage_shop/')->get_product_related($id);
	}

	function show_settings() {
		return _class('manage_shop_show_settings', 'admin_modules/manage_shop/')->show_settings();
	}

	function orders_manage() {
		return _class('manage_shop_orders_manage', 'admin_modules/manage_shop/')->orders_manage();
	}

	function show_reports() {
		return _class('manage_shop_show_reports', 'admin_modules/manage_shop/')->show_reports();
	}

	function show_reports_viewed() {
		return _class('manage_shop_show_reports_viewed', 'admin_modules/manage_shop/')->show_reports_viewed();
	}

	function sort() {
		return _class('manage_shop_sort', 'admin_modules/manage_shop/')->sort();
	}

	function show_orders() {
		return _class('manage_shop_show_orders', 'admin_modules/manage_shop/')->show_orders();
	}

	function show_print() {
		return _class('manage_shop_show_print', 'admin_modules/manage_shop/')->show_print();
	}

	function view_order() {
		return _class('manage_shop_view_order', 'admin_modules/manage_shop/')->view_order();
	}

	function delete_order() {
		return _class('manage_shop_delete_order', 'admin_modules/manage_shop/')->delete_order();
	}

	function manufacturers() {
		return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->manufacturers();
	}

	function manufacturer_edit() {
		return _class('manage_shop_manufacturer_edit', 'admin_modules/manage_shop/')->manufacturer_edit();
	}

	function manufacturer_add() {
		return _class('manage_shop_manufacturer_add', 'admin_modules/manage_shop/')->manufacturer_add();
	}

	function manufacturer_delete() {
		return _class('manage_shop_manufacturer_delete', 'admin_modules/manage_shop/')->manufacturer_delete();
	}

	function manufacturer_view() {
		return _class('manage_shop_manufacturer_view', 'admin_modules/manage_shop/')->manufacturer_view();
	}

	function attributes() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->attributes();
	}

	function attribute_add() {
		return _class('manage_shop_attribute_add', 'admin_modules/manage_shop/')->attribute_add();
	}

	function attribute_edit() {
		return _class('manage_shop_attribute_edit', 'admin_modules/manage_shop/')->attribute_edit();
	}

	function attribute_delete() {
		return _class('manage_shop_attribute_delete', 'admin_modules/manage_shop/')->attribute_delete();
	}

	function _attributes_view($object_id = 0) {
		return _class('manage_shop__attributes_view', 'admin_modules/manage_shop/')->_attributes_view($object_id);
	}

	function _attributes_html($object_id = 0, $only_selected = false) {
		return _class('manage_shop__attributes_html', 'admin_modules/manage_shop/')->_attributes_html($object_id, $only_selected);
	}

	function _attributes_save($object_id = 0) {
		return _class('manage_shop__attributes_save', 'admin_modules/manage_shop/')->_attributes_save($object_id);
	}

	function _get_attributes($category_id = 0) {
		return _class('manage_shop__get_attributes', 'admin_modules/manage_shop/')->_get_attributes($category_id);
	}

	function _get_products_attributes($products_ids = array()) {
		return _class('manage_shop__get_products_attributes', 'admin_modules/manage_shop/')->_get_products_attributes($products_ids);
	}

	function _get_attributes_values($category_id = 0, $object_id = 0, $fields_ids = 0) {
		return _class('manage_shop__get_attributes_values', 'admin_modules/manage_shop/')->_get_attributes_values($category_id, $object_id, $fields_ids);
	}

	function _format_price($price = 0) {
		return _class('manage_shop__format_price', 'admin_modules/manage_shop/')->_format_price($price);
	}

	function _get_group_prices($product_id = 0) {
		return _class('manage_shop__get_group_prices', 'admin_modules/manage_shop/')->_get_group_prices($product_id);
	}

	function _save_group_prices($product_id = 0) {
		return _class('manage_shop__save_group_prices', 'admin_modules/manage_shop/')->_save_group_prices($product_id);
	}

	function _box($name = "", $selected = "") {
		return _class('manage_shop__box', 'admin_modules/manage_shop/')->_box($name, $selected);
	}

	function _quick_menu() {
		return _class('manage_shop__quick_menu', 'admin_modules/manage_shop/')->_quick_menu();
	}

	function _show_header() {
		return _class('manage_shop__show_header', 'admin_modules/manage_shop/')->_show_header();
	}

	function _prepare_filter_data() {
		return _class('manage_shop__prepare_filter_data', 'admin_modules/manage_shop/')->_prepare_filter_data();
	}

	function _create_filter_sql() {
		return _class('manage_shop__create_filter_sql', 'admin_modules/manage_shop/')->_create_filter_sql();
	}

	function _show_filter() {
		return _class('manage_shop__show_filter', 'admin_modules/manage_shop/')->_show_filter();
	}

	function save_filter($silent = false) {
		return _class('manage_shop_save_filter', 'admin_modules/manage_shop/')->save_filter($silent);
	}

	function clear_filter($silent = false) {
		return _class('manage_shop_clear_filter', 'admin_modules/manage_shop/')->clear_filter($silent);
	}

	function save_filter_order() {
		return _class('manage_shop_save_filter_order', 'admin_modules/manage_shop/')->save_filter_order();
	}

	function clear_filter_order() {
		return _class('manage_shop_clear_filter_order', 'admin_modules/manage_shop/')->clear_filter_order();
	}

	function save_filter_report() {
		return _class('manage_shop_save_filter_report', 'admin_modules/manage_shop/')->save_filter_report();
	}

	function clear_filter_report() {
		return _class('manage_shop_clear_filter_report', 'admin_modules/manage_shop/')->clear_filter_report();
	}

}

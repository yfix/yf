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
		$manage_shop = module('manage_shop');

		$manage_shop->_cats_for_select	= _class('cats')->_prepare_for_box("shop_cats", 0);
		
		$manage_shop->man = db()->query_fetch_all("SELECT * FROM ".db('shop_manufacturers')." ORDER BY name ASC");
		$manage_shop->_man_for_select[0] = "--NONE--";
		foreach ((array)$manage_shop->man as $k => $v) {
			$manage_shop->_man_for_select[$v["id"]] = $v["name"];
		}

		$manage_shop->_suppliers = db()->query_fetch_all("SELECT * FROM ".db('shop_suppliers')." ORDER BY name ASC");
		$manage_shop->_suppliers_for_select[0] = "--NONE--";
		foreach ((array)$manage_shop->_suppliers as $k => $v) {
			$manage_shop->_suppliers_for_select[$v["id"]] = $v["name"];
		}

		$manage_shop->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $manage_shop->PROD_IMG_DIR;
		$manage_shop->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $manage_shop->PROD_IMG_DIR;
		if (!file_exists($manage_shop->products_img_dir)) {
			_mkdir_m($manage_shop->products_img_dir);
		}
		$manage_shop->_boxes = array(
			"status"		=> 'select_box("status",		module("manage_shop")->_statuses,	$selected, false, 2, "", false)',
			"featured"		=> 'radio_box("featured",		module("manage_shop")->_featured,	$selected, false, 2, "", false)',
			"status_prod"	=> 'select_box("status_prod",	module("manage_shop")->_status_prod,$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		module("manage_shop")->_sort_by,	$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order", 	module("manage_shop")->_sort_orders,$selected, 0, 2, "", false)',
		);
		$manage_shop->_featured = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		$manage_shop->_status_prod = array(
			""		=> "",
			"1"	=> "Active",
			"0"	=> "Inacive",
		);
		$manage_shop->_sort_orders = array(""	=> "", "DESC" => "Descending", "ASC" => "Ascending");
		$manage_shop->_sort_by = array(
			""			=> "",
			"name"		=> "Name",
			"price" 	=> "Price",
			"quantity" 	=> "Quantity",
			"add_date" 	=> "Date",
			"active" 	=> "Status",
		);
		// Sync company info with user section
#		$manage_shop->COMPANY_INFO = _class("shop", "modules/")->COMPANY_INFO;

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

	function products() {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->products();
	}

	function product_add() {
		return _class('manage_shop_product_add', 'admin_modules/manage_shop/')->product_add();
	}

	function product_edit() {
		return _class('manage_shop_product_edit', 'admin_modules/manage_shop/')->product_edit();
	}

	function product_delete() {
		return _class('manage_shop_product_delete', 'admin_modules/manage_shop/')->product_delete();
	}

	function product_clone() {
		return _class('manage_shop_product_clone', 'admin_modules/manage_shop/')->product_clone();
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

	function orders() {
		return js_redirect("./?object=manage_shop&action=orders_manage");
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

	function suppliers() {
		return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->suppliers();
	}

	function supplier_edit() {
		return _class('manage_shop_supplier_edit', 'admin_modules/manage_shop/')->supplier_edit();
	}

	function supplier_add() {
		return _class('manage_shop_supplier_add', 'admin_modules/manage_shop/')->supplier_add();
	}

	function supplier_delete() {
		return _class('manage_shop_supplier_delete', 'admin_modules/manage_shop/')->supplier_delete();
	}

	function attributes() {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func();
	}

	function attribute_add() {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func();
	}

	function attribute_edit() {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func();
	}

	function attribute_delete() {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func();
	}

	function attribute_activate() {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func();
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

	function product_sets() {
		$func = __FUNCTION__; return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->$func();
	}

	function product_set_edit() {
		$func = __FUNCTION__; return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->$func();
	}

	function product_set_add() {
		$func = __FUNCTION__; return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->$func();
	}

	function product_set_delete() {
		$func = __FUNCTION__; return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->$func();
	}

	function _format_price($price = 0) {
		return _class('manage_shop__format_price', 'admin_modules/manage_shop/')->_format_price($price);
	}

	function _get_group_prices($product_id = 0) {
#		return _class('manage_shop__get_group_prices', 'admin_modules/manage_shop/')->_get_group_prices($product_id);
	}

	function _save_group_prices($product_id = 0) {
#		return _class('manage_shop__save_group_prices', 'admin_modules/manage_shop/')->_save_group_prices($product_id);
	}

	function _box($name = "", $selected = "") {
		return _class('manage_shop__box', 'admin_modules/manage_shop/')->_box($name, $selected);
	}

	function _show_header() {
#		return _class('manage_shop__show_header', 'admin_modules/manage_shop/')->_show_header();
	}

	function categories() {
		return js_redirect("./?object=category_editor&action=show_items&id=shop_cats");
	}

	function config() {
		return js_redirect("./?object=manage_conf&category=shop");
	}

	function _show_filter($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_filter', 'admin_modules/manage_shop/')->$func($params);
	}

	function filter_save($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_filter', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__new_products ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__latest_sold_products ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__top_sold_products ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__latest_orders ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__top_customers ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__latest_customers ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}

	function _hook_widget__stats ($params = array()) {
		$func = __FUNCTION__; return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->$func($params);
	}
}

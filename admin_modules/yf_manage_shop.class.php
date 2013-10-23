<?php

/**
* Shop managing module
*/
class yf_manage_shop {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var string Folder where product's images store */
	public $PROD_IMG_DIR	= 'shop/products/';
	/** @var string fullsize image suffix (underscore at the beginning required)*/
	public $FULL_IMG_SUFFIX	= '_big';
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $THUMB_SUFFIX	= '_thumb';
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $MEDIUM_SUFFIX	= '_medium';
	/** @var string Image prefix */
	public $IMG_PREFIX		= 'product_';
	/** @var int Thumb size X */
	public $THUMB_X			= 216;
	/** @var int Thumb size Y */
	public $THUMB_Y			= 216;
	/** @var int Big img size X */
	public $BIG_X			= 710;
	/** @var int Big img size Y */
	public $BIG_Y			= 750;
	/** @var string Default currency */
	public $CURRENCY		= '$';
	/** @var Shipping types */
	public $_ship_types = array(
		1	=> 'Free',
		2	=> 'Courier',
		3	=> 'FedEX',
	);
	/** @var Payment types */
	public $_pay_types = array(
		1	=> 'Courier',
		2	=> 'Authorize.Net',
	);
	/** @var @conf_skip */
	public $_statuses = array(
		''					=> '',
		'pending'			=> 'Pending',
		'pending payment'	=> 'Pending payment',
		'proccessed'		=> 'Proccessed',
		'delivery'			=> 'Delivery',
		'shipped'			=> 'Shipped',
	);
	/** @var Company info */
	public $COMPANY_INFO = array(
		'company_name'		=> 'Company Name',
		'company_address'	=> 'Company Address 1',
		'company_address2'	=> 'Company Address 2',
		'company_phone'		=> 'Company Phone',
		'company_website'	=> 'Company Website',
		'company_email'		=> 'Company Email',
	);
	/** @var */
	public $ATTRIBUTES = array();
	/** @var @conf_skip */
	public $ATTRIBUTES_CAT_ID = 1;

	/**
	* Constructor
	*/
	function _init() {
		$manage_shop = module('manage_shop');

		$manage_shop->_category_names	= _class('cats')->_get_items_names('shop_cats');
		$manage_shop->_cats_for_select	= _class('cats')->_prepare_for_box('shop_cats', 0);
		
		$manage_shop->man = db()->query_fetch_all('SELECT * FROM '.db('shop_manufacturers').' ORDER BY name ASC');
		$manage_shop->_man_for_select[0] = '--NONE--';
		foreach ((array)$manage_shop->man as $k => $v) {
			$manage_shop->_man_for_select[$v['id']] = $v['name'];
		}

		$manage_shop->_suppliers = db()->query_fetch_all('SELECT * FROM '.db('shop_suppliers').' ORDER BY name ASC');
		$manage_shop->_suppliers_for_select[0] = '--NONE--';
		foreach ((array)$manage_shop->_suppliers as $k => $v) {
			$manage_shop->_suppliers_for_select[$v['id']] = $v['name'];
		}

		$manage_shop->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $manage_shop->PROD_IMG_DIR;
		$manage_shop->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $manage_shop->PROD_IMG_DIR;
		if (!file_exists($manage_shop->products_img_dir)) {
			_mkdir_m($manage_shop->products_img_dir);
		}
		$manage_shop->_boxes = array(
			'status'		=> 'select_box("status",		module("manage_shop")->_statuses,	$selected, false, 2, "", false)',
			'featured'		=> 'radio_box("featured",		module("manage_shop")->_featured,	$selected, false, 2, "", false)',
			'status_prod'	=> 'select_box("status_prod",	module("manage_shop")->_status_prod,$selected, 0, 2, "", false)',
		);
		$manage_shop->_featured = array(
			'0' => '<span class="negative">NO</span>',
			'1' => '<span class="positive">YES</span>',
		);
		$manage_shop->_status_prod = array(
			''		=> '',
			'1'	=> 'Active',
			'0'	=> 'Inacive',
		);
		// Sync company info with user section
#		$manage_shop->COMPANY_INFO = _class('shop', 'modules/')->COMPANY_INFO;

//		$this->manufacturer_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
//		$this->manufacturer_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
	}

	function _box($name = '', $selected = '') {
		if (empty($name) || empty(module('manage_shop')->_boxes[$name])) {
			return false;
		} else {
			return eval('return common()->'.module('manage_shop')->_boxes[$name].';');
		}
	}

	function _format_price($price = 0) {
		if (module('manage_shop')->CURRENCY == '$') {
			return module('manage_shop')->CURRENCY.'&nbsp;'.$price;
		} else {
			return $price.'&nbsp;'.module('manage_shop')->CURRENCY;
		}
	}

	function show() {
		return _class('manage_shop_dashboard', 'admin_modules/manage_shop/')->dashboard();
	}

	function products() {
		$func = __FUNCTION__; return _class('manage_shop_products', 'admin_modules/manage_shop/')->$func();
	}

	function product_add() {
		$func = __FUNCTION__; return _class('manage_shop_product_add', 'admin_modules/manage_shop/')->$func();
	}

	function product_edit() {
		$func = __FUNCTION__; return _class('manage_shop_product_edit', 'admin_modules/manage_shop/')->$func();
	}

	function product_delete() {
		$func = __FUNCTION__; return _class('manage_shop_products', 'admin_modules/manage_shop/')->$func();
	}

	function product_clone() {
		$func = __FUNCTION__; return _class('manage_shop_products', 'admin_modules/manage_shop/')->$func();
	}

	function product_activate() {
		$func = __FUNCTION__; return _class('manage_shop_products', 'admin_modules/manage_shop/')->$func();
	}

	function product_image_upload() {
		$func = __FUNCTION__; return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->$func();
	}

	function _product_image_upload($product_id, $product_name) {
		$func = __FUNCTION__; return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->$func($product_id, $product_name);
	}

	function _product_image_delete($id, $name, $k) {
		$func = __FUNCTION__; return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->$func($id, $name, $k);
	}

	function product_image_delete() {
		$func = __FUNCTION__; return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->$func();
	}

	function products_by_category($cat = '') {
		$func = __FUNCTION__; return _class('manage_shop_products', 'admin_modules/manage_shop/')->$func($cat);
	}

	function related_products($id = '') {
		$func = __FUNCTION__; return _class('manage_shop_related_products', 'admin_modules/manage_shop/')->$func($id);
	}

	function orders() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->orders_manage();
	}

	function show_orders() {
		$func = __FUNCTION__; return _class('manage_shop_orders', 'admin_modules/manage_shop/')->$func();
	}

	function orders_manage() {
		$func = __FUNCTION__; return _class('manage_shop_orders', 'admin_modules/manage_shop/')->$func();
	}

	function show_print() {
		$func = __FUNCTION__; return _class('manage_shop_orders', 'admin_modules/manage_shop/')->$func();
	}

	function view_order() {
		$func = __FUNCTION__; return _class('manage_shop_orders', 'admin_modules/manage_shop/')->$func();
	}

	function delete_order() {
		$func = __FUNCTION__; return _class('manage_shop_orders', 'admin_modules/manage_shop/')->$func();
	}

	function manufacturers() {
		$func = __FUNCTION__; return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->$func();
	}

	function manufacturer_edit() {
		$func = __FUNCTION__; return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->$func();
	}

	function manufacturer_add() {
		$func = __FUNCTION__; return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->$func();
	}

	function manufacturer_delete() {
		$func = __FUNCTION__; return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->$func();
	}

	function suppliers() {
		$func = __FUNCTION__; return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->$func();
	}

	function supplier_edit() {
		$func = __FUNCTION__; return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->$func();
	}

	function supplier_add() {
		$func = __FUNCTION__; return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->$func();
	}

	function supplier_delete() {
		$func = __FUNCTION__; return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->$func();
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
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($object_id);
	}

	function _attributes_html($object_id = 0, $only_selected = false) {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($object_id, $only_selected);
	}

	function _attributes_save($object_id = 0) {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($object_id);
	}

	function _get_attributes($category_id = 0) {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($category_id);
	}

	function _get_products_attributes($products_ids = array()) {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($products_ids);
	}

	function _get_attributes_values($category_id = 0, $object_id = 0, $fields_ids = 0) {
		$func = __FUNCTION__; return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->$func($category_id, $object_id, $fields_ids);
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

	function _show_header() {
		return _class('manage_shop__show_header', 'admin_modules/manage_shop/')->_show_header();
	}

	function categories() {
		return js_redirect('./?object=category_editor&action=show_items&id=shop_cats');
	}

	function config() {
		return js_redirect('./?object=manage_conf&category=shop');
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
	function users() {
		$func = __FUNCTION__; return _class('manage_shop_users', 'admin_modules/manage_shop/')->$func();
	}	
	function user_activate() {
		$func = __FUNCTION__; return _class('manage_shop_users', 'admin_modules/manage_shop/')->$func();
	}	
	function user_delete() {
		$func = __FUNCTION__; return _class('manage_shop_users', 'admin_modules/manage_shop/')->$func();
	}	
	function user_edit() {
		$func = __FUNCTION__; return _class('manage_shop_users', 'admin_modules/manage_shop/')->$func();
	}
	function _productparams_container($product_id) {		
		$func = __FUNCTION__; return _class('manage_shop__productparams_container', 'admin_modules/manage_shop/')->$func($product_id);
	}
	
}

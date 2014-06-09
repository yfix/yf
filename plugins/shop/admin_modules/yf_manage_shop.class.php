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
	public $CURRENCY		= 'грн';
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
	public $_statuses = array();
	public $_products_statuses = array(
		0	=> 'standard',
		1	=> 'imported',
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
		$supplier = db()->get('SELECT supplier_id, main_cat_id FROM '.db('shop_admin_to_supplier').' WHERE admin_id='.intval(main()->ADMIN_ID));
		if ($supplier['supplier_id']) {
			$this->SUPPLIER_ID = $supplier['supplier_id'];
			$supplier_parent_cat_item = $supplier['main_cat_id'];
		}

		$this->_statuses = common()->get_static_conf('order_status');
		$this->_order_items_status = common()->get_static_conf('order_items_status');
		$this->_category_names	= _class('cats')->_get_items_names_cached('shop_cats', $sort = true, $all = true);

		if ($this->SUPPLIER_ID && $supplier_parent_cat_item) {
			$this->_cats_for_select	= _class('cats')->_prepare_for_box_cached('shop_cats', $all = true, $supplier_parent_cat_item);
		} else {
			$this->_cats_for_select	= _class('cats')->_prepare_for_box_cached('shop_cats', $all = true);
		}

		$this->man = db()->query_fetch_all('SELECT * FROM '.db('shop_manufacturers').' ORDER BY name ASC');
		$this->_man_for_select[''] = '--NONE--';
		foreach ((array)$this->man as $k => $v) {
			$this->_man_for_select[$v['id']] = $v['name'];
		}

		$this->_suppliers = db()->query_fetch_all('SELECT * FROM '.db('shop_suppliers').' ORDER BY name ASC');
		$this->_suppliers_for_select = array();
		if (!$this->SUPPLIER_ID) {
			$this->_suppliers_for_select[''] = '--NONE--';
			foreach ((array)$this->_suppliers as $k => $v) {
				$this->_suppliers_for_select[$v['id']] = $v['name'];
			}
		}
		$this->_units_for_select = db()->get_2d('SELECT id, title FROM '.db('shop_product_units'));

		$this->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		$this->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->PROD_IMG_DIR;
		if (!file_exists($this->products_img_dir)) {
			mkdir($this->products_img_dir, 0755, true);
		}
		$this->_boxes = array(
			'status'		=> 'select_box("status",		module("manage_shop")->_statuses,	$selected, false, 2, "", false)',
			'featured'		=> 'radio_box("featured",		module("manage_shop")->_featured,	$selected, false, 2, "", false)',
			'status_prod'	=> 'select_box("status_prod",	module("manage_shop")->_status_prod,$selected, 0, 2, "", false)',
			'status_item'	=> 'select_box("status_item",	module("manage_shop")->_order_items_status,	$selected, false, 2, "", false)',
		);
		$this->_featured = array(
			'0' => '<span class="negative">NO</span>',
			'1' => '<span class="positive">YES</span>',
		);
		$this->_status_prod = array(
			''		=> '',
			'1'	=> 'Active',
			'0'	=> 'Inacive',
		);
		// Sync company info with user section
#		$this->COMPANY_INFO = _class('shop', 'modules/')->COMPANY_INFO;

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
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function products_xls_export() {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_add() {
		return _class('manage_shop_product_add', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_edit() {
		return _class('manage_shop_product_edit', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_delete() {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_clone() {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_activate() {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_image_upload() {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _product_image_upload($product_id) {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}($product_id);
	}

	function product_image_search() {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function upload_images() {
		return _class('manage_shop_upload_images', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function set_main_image() {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _product_image_delete($id, $k) {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}($id, $k);
	}

	function product_image_delete() {
		return _class('manage_shop_product_images', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function products_by_category($cat = '') {
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}($cat);
	}

	function related_products($id = '') {
		return _class('manage_shop_related_products', 'admin_modules/manage_shop/')->{__FUNCTION__}($id);
	}

	function product_revisions() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_images_revisions() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function order_revisions() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_revisions_view() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

 	function product_images_revisions_view() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function order_revisions_view() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function checkout_images_revision() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function checkout_product_revision() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function checkout_order_revision() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function checkout_group_revision() {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function orders() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->orders_manage();
	}

	function show_orders() {
		$_GET['action'] = 'orders';
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function orders_manage() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function show_print() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function view_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function merge_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function delete_order() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _order_add_revision($action, $item_id) {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}($action, $item_id);
	}

	function manufacturers() {
		return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function manufacturer_edit() {
		return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function manufacturer_add() {
		return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function manufacturer_delete() {
		return _class('manage_shop_manufacturers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function suppliers() {
		return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function supplier_edit() {
		return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function supplier_add() {
		return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function supplier_delete() {
		return _class('manage_shop_suppliers', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function units() {
		return _class('manage_shop_units', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function unit_edit() {
		return _class('manage_shop_units', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function unit_add() {
		return _class('manage_shop_units', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function unit_delete() {
		return _class('manage_shop_units', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function price_markup_down() {
		return _class('manage_shop_price_markup_down', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function price_markup_down_active() {
		return _class('manage_shop_price_markup_down', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function price_markup_down_edit() {
		return _class('manage_shop_price_markup_down', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function price_markup_down_add() {
		return _class('manage_shop_price_markup_down', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function price_markup_down_delete() {
		return _class('manage_shop_price_markup_down', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function products_price_update() {
		return _class('manage_shop_price_update', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function novaposhta_ua() {
		return _class('manage_shop_novaposhta_ua', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function novaposhta_ua__import() {
		return _class('manage_shop_novaposhta_ua', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function attributes() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function attribute_add() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function attribute_edit() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function attribute_delete() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function attribute_activate() {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _attributes_view($object_id = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($object_id);
	}

	function _attributes_html($object_id = 0, $only_selected = false) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($object_id, $only_selected);
	}

	function _attributes_save($object_id = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($object_id);
	}

	function _get_attributes($category_id = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($category_id);
	}

	function _get_products_attributes($products_ids = array()) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($products_ids);
	}

	function _get_attributes_values($category_id = 0, $object_id = 0, $fields_ids = 0) {
		return _class('manage_shop_attributes', 'admin_modules/manage_shop/')->{__FUNCTION__}($category_id, $object_id, $fields_ids);
	}

	function product_sets() {
		return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_set_edit() {
		return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_set_add() {
		return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_set_delete() {
		return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function product_set_active() {
		return _class('manage_shop_product_sets', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _show_header() {
		return _class('manage_shop__show_header', 'admin_modules/manage_shop/')->_show_header();
	}

	function categories() {
		return js_redirect('./?object=category_editor&action=drag_items&id=shop_cats');
	}

	function config() {
		return js_redirect('./?object=manage_conf&category=shop');
	}

	function _show_filter($params = array()) {
		return _class('manage_shop_filter', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function filter_save($params = array()) {
		return _class('manage_shop_filter', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__new_products ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__latest_sold_products ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__top_sold_products ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__latest_orders ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__top_customers ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__latest_customers ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _hook_widget__stats ($params = array()) {
		return _class('manage_shop_hook_widgets', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function users() {
		return _class('manage_shop_users', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function user_activate() {
		return _class('manage_shop_users', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function user_delete() {
		return _class('manage_shop_users', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function user_edit() {
		return _class('manage_shop_users', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function express() {
		return _class('manage_shop_express', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function paywill() {
		return _class('manage_shop_paywill', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _prepare_paywill_body($params = false) {
		return _class('manage_shop_paywill', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function express_pdf($params = array()) {
		return _class('manage_shop_express', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function mail_pdf() {
		return _class('manage_shop_express', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _productparams_container($product_id) {
		return _class('manage_shop__productparams_container', 'admin_modules/manage_shop/')->{__FUNCTION__}($product_id);
	}

	function _product_add_revision($action, $item_id) {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}($action, $item_id);
	}

	function _add_group_revision($action, $item_id, $group_id) {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}($action, $item_id, $group_id);
	}

	function _product_images_add_revision($action, $product_id, $image_id) {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}($action, $product_id, $image_id);
	}

	function _product_check_first_revision($action, $item_id) {
		return _class('manage_shop__product_revisions', 'admin_modules/manage_shop/')->{__FUNCTION__}($action, $item_id);
	}

	function import_xls($params = array()) {
		return _class('manage_shop_import', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function export_zakaz_start($params = array()) {
		return _class('manage_shop_import', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function import_products() {
		return $this->import_xls();
	}

	function product_search_autocomplete(){
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function category_search_autocomplete(){
		return _class('manage_shop_products', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function order_product_add_ajax() {
		return _class('manage_shop_orders', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function productparams_container_ajax() {
		return _class('manage_shop__productparams_container', 'admin_modules/manage_shop/')->_productparams_container($params,'productparams_container_ajax');
	}

	function pics_browser($params = array()) {
		return _class('manage_shop_pics_browser', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function send_sms() {
		return _class('manage_shop_send_sms', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function category_mapping($params = array()) {
		return _class('manage_shop_categories', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function category_mapping_add($params = array()) {
		return _class('manage_shop_categories', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function category_mapping_edit($params = array()) {
		return _class('manage_shop_categories', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function category_mapping_delete($params = array()) {
		return _class('manage_shop_categories', 'admin_modules/manage_shop/')->{__FUNCTION__}($params);
	}

	function _product_cache_purge($product_id = 0) {
		if (!$product_id) {
			$product_id = $_GET['id'];
		}
		cache_del('_shop_products|_product_image|'.$product_id);
		cache_del('_shop_product_params|_product_image|'.$product_id);
		cache_del('_shop_product_params|_get_params_by_product|'.$product_id);
		cache_del('pattern_yf|_get_shop_product_details|'.$product_id);
		_class( '_shop_categories', 'modules/shop/' )->_refresh_cache();
	}

	function _product_get_info($product_id = 0) {
		$product_id = intval($product_id);
		if (!$product_id) {
			return false;
		}
		if (isset($this->_products_info_cache[$product_id])) {
			return $this->_products_info_cache[$product_id];
		}
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id
					WHERE p.id='.intval($product_id).'
						AND m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' WHERE id='.intval($product_id);
		}
		$product_info = db()->get($sql);
		$this->_products_info_cache[$product_id] = $product_info;
		return $product_info;
	}

	/*
	 * Patterns for massive corrections of names of products
	 */
	function clear_patterns() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_list() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_add() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_edit() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_delete() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_stop() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_run() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_rollback() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_child_process() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function clear_pattern_status() {
		return _class('manage_shop_clear_products', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function _hook_side_column() {
		return _class('manage_shop_hook_side_column', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}

	function feedback() {
		return _class('manage_shop_feedback', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function feedback_delete() {
		return _class('manage_shop_feedback', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
	function feedback_activate() {
		return _class('manage_shop_feedback', 'admin_modules/manage_shop/')->{__FUNCTION__}();
	}
}

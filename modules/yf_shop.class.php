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
	38000000000006 Diners Club/ baskete Blanche
	*/

	/** @var string Folder where product's images store */
	public $PROD_IMG_DIR		= 'shop/products/';
	/** @var string fullsize image suffix (underscore at the beginning required)*/
	public $FULL_IMG_SUFFIX	= '_full';
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $THUMB_SUFFIX		= '_small';
	/** @var string Image prefix */
	public $IMG_PREFIX			= 'product_';
	/** @var string Default currency */
	public $CURRENCY			= '$';
	/** @var bool SHOW_SIMILAR_PRICE or not */
	public $SHOW_SIMILAR_PRICE		= true;
	/** @var bool THIS_ITEM_OFTEN_BUY or not */
	public $THIS_ITEM_OFTEN_BUY		= true;
	 /** @var array forum settings (default values) */
	public $COMPANY_INFO = array(
		'company_name'		=> 'Shop.com ', //
		'company_address'	=> 'Company Address 1', //
		'company_address2'	=> 'Company Address 2', //
		'company_phone'		=> 'Company Phone', //
		'company_website'	=> 'Company Website', //
		'company_email'		=> 'Company Email', //
		'company_title'		=> 'Shop.com ', //
	);
	/** @var Billing info */
	public $_b_fields = array(
		'b_first_name',
		'b_last_name',
		'b_email',
		'b_phone',
		'b_address',
		'b_address2',
		'b_zip_code',
		'b_city',
		'b_state',
		'b_country',
		'b_company',
	);
	/** @var Shipping info */
	public $_s_fields = array(
		's_first_name',
		's_last_name',
		's_email',
		's_phone',
		's_address',
		's_address2',
		's_zip_code',
		's_city',
		's_state',
		's_country',
		's_company',
	);
	/** @var Required shipping and billing fields */
	public $_required_fields = array(
		'name',
		'phone',
	);
	/** @var @conf_skip */
	public $_statuses = array(
		'pending'			=> 'pending',
		'pending payment'	=> 'pending payment',
		'proccessed'		=> 'proccessed',
		'delivery'			=> 'delivery',
		'shipped'			=> 'shipped',
	);
	public $_ship_type = array(
		1 => 'Free',
		2 => 'Courier',
		3 => 'FedEX',
		4 => 'Post',
	);
	/** @var Shipping types */
	public $_ship_types = array(
		1 => array(
			'name'	=> 'Free',
			'price'	=> 0,
		),
		2 => array(
			'name'	=> 'Courier',
			'price'	=> 1,
		),
		3 => array(
			'name'	=> 'FedEX',
			'price'	=> 5,
		),
		4 => array(
			'name'	=> 'Post',
			'price'	=> 1,
		),
	);
	/** @var Shipping types names (creating automatically inside '_init') @conf_skip */
	public $_ship_types_name = array();
	/** @var Payment types */
	public $_pay_types = array(
		1 => 'Cash On Delivery',
		2 => 'Authorize.Net',
		3 => 'Bank Transfer',
		4 => 'Cheque / Money Order',
	);
	/** @var Payment methods params */
	public $_pay_method_params = array(
		2 => array( // Authorize.Net
			'LOGIN_ID'			=> '{CLIENT_ID}',
			'TRANSACTION_KEY'	=> '{CLIENT_SECRET}',
			'TEST_MODE'			=> 1,
			'IN_PRODUCTION'		=> 0,
			'DESCRIPTION'		=> 'Shop Description Here',
		),
	);

	/** @var Force payment method (Set to 0 to disable) */
	public $FORCE_PAY_METHOD	= 0;
	/** @var Inline registration */
	public $INLINE_REGISTER		= true;
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
		'return_action'		=> 'product_details',
		'object_name'		=> 'shop',
		'allow_guests_posts'=> '1',
	);

	function _init() {
		$shop = module('shop');
		$shop->_shop_cats				= _class('cats')->_get_items_names('shop_cats');
		$shop->_shop_cats_all			= _class('cats')->_get_items_array('shop_cats');
		$shop->_shop_cats_for_select	= _class('cats')->_prepare_for_box('shop_cats');

		$sql_man = 'SELECT * FROM '.db('shop_manufacturers').' ORDER BY name ASC';
		$shop->_manufacturer = db()->query_fetch_all($sql_man);

		$shop->_man_for_select['none'] = '--NONE--';
		foreach ((array)$shop->_manufacturer as $k => $v) {
			$shop->_man_for_select[$v['url']] = $v['name'];
		}
		$shop->_man_id = 'none';
		$shop->products_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $shop->PROD_IMG_DIR;
		$shop->products_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $shop->PROD_IMG_DIR;
		if (!file_exists($shop->products_img_dir)) {
			_mkdir_m($shop->products_img_dir);
		}

		$shop->_boxes = array(
			'ship_type'	=> 'select_box("ship_type", $shop->_ship_types_names, $selected, false, 2, "", false)',
			'pay_type'	=> 'radio_box("pay_type", $shop->_pay_types, $selected, 1, 2, "", false)',
		);

		$shop->_ship_types_names = array();
		foreach ((array)$shop->_ship_types as $_id => $_info) {
			$_price_text = ' ('.($_info['price'] < 0 ? '-' : '+'). $shop->_format_price(abs($_info['price'])).')';
			$shop->_ship_types_names[$_id] = $_info['name']. ($_info['price'] ? $_price_text : '');
		}

		// Override pay type for group
		$force_group_pay_type = $shop->FORCE_GROUP_PAY[main()->USER_GROUP];
		if ($force_group_pay_type/* && isset($shop->_pay_types[$force_group_pay_type])*/) {
			$shop->FORCE_PAY_METHOD = $force_group_pay_type;
		}
	}

	function show() {
		return _class('shop_show', 'modules/shop/')->show();
	}

	function products_show($search = '', $str_search = '') {
		return _class('shop_products_show', 'modules/shop/')->products_show($search, $str_search);
	}

	function product_details() {
		return _class('shop_product_details', 'modules/shop/')->product_details();
	}

	function products_related($id = '') {
		return _class('shop_products_related', 'modules/shop/')->products_related($id);
	}

	function products_similar_by_price($price, $id) {
		return _class('shop_products_similar_by_price', 'modules/shop/')->products_similar_by_price($price, $id);
	}

	function products_similar_by_basket($id) {
		return _class('shop_products_similar_by_basket', 'modules/shop/')->products_similar_by_basket($id);
	}

	function basket($params = array()) {
		return _class('shop_basket', 'modules/shop/')->basket($params);
	}

	function basket_main($params = array()) {
		return _class('shop_basket_main', 'modules/shop/')->basket_main($params);
	}

	function basket_add() {
		return _class('shop_basket_add', 'modules/shop/')->basket_add();
	}

	function basket_clean() {
		return _class('shop_basket_clean', 'modules/shop/')->basket_clean();
	}

	function _basket_side() {
		return _class('shop__basket_side', 'modules/shop/')->_basket_side();
	}

	function _basket_save() {
		return _class('shop__basket_save', 'modules/shop/')->_basket_save();
	}

	function _basket_api() {
		return _class('shop__basket_api', 'modules/shop/')->_basket_api();
	}

	function order() {
		return _class('shop_order', 'modules/shop/')->order();
	}

	function orders($FORCE_DISPLAY_FORM = false) {
		return _class('shop_orders', 'modules/shop/')->orders($FORCE_DISPLAY_FORM);
	}

	function order_validate_data($FORCE_DISPLAY_FORM = false) {
		return _class('shop_order_validate_data', 'modules/shop/')->order_validate_data($FORCE_DISPLAY_FORM);
	}

	function order_view($FORCE_DISPLAY_FORM = false) {
		return _class('shop_order_view', 'modules/shop/')->order_view($FORCE_DISPLAY_FORM);
	}

	function order_delete($FORCE_DISPLAY_FORM = false) {
		return _class('shop_order_delete', 'modules/shop/')->order_delete($FORCE_DISPLAY_FORM);
	}

	function _order_step_start($FORCE_DISPLAY_FORM = false) {
		return _class('shop__order_step_start', 'modules/shop/')->_order_step_start($FORCE_DISPLAY_FORM);
	}

	function _order_step_delivery($FORCE_DISPLAY_FORM = false) {
		return _class('shop__order_step_delivery', 'modules/shop/')->_order_step_delivery($FORCE_DISPLAY_FORM);
	}

	function _order_validate_delivery() {
		return _class('shop__order_validate_delivery', 'modules/shop/')->_order_validate_delivery();
	}

	function _order_step_select_payment($FORCE_DISPLAY_FORM = false) {
		return _class('shop__order_step_select_payment', 'modules/shop/')->_order_step_select_payment($FORCE_DISPLAY_FORM);
	}

	function _order_validate_select_payment() {
		return _class('shop__order_validate_select_payment', 'modules/shop/')->_order_validate_select_payment();
	}

	function _order_create() {
		return _class('shop__order_create', 'modules/shop/')->_order_create();
	}

	function _order_step_do_payment($FORCE_DISPLAY_FORM = false) {
		return _class('shop__order_step_do_payment', 'modules/shop/')->_order_step_do_payment($FORCE_DISPLAY_FORM);
	}

	function _order_validate_do_payment() {
		return _class('shop__order_validate_do_payment', 'modules/shop/')->_order_validate_do_payment();
	}

	function _order_step_finish($FORCE_DISPLAY_FORM = false) {
		return _class('shop__order_step_finish', 'modules/shop/')->_order_step_finish($FORCE_DISPLAY_FORM);
	}

	function _order_pay_authorize_net($order_info = array(), $params = array()) {
		return _class('shop__order_pay_authorize_net', 'modules/shop/')->_order_pay_authorize_net($order_info, $params);
	}

	function payment_callback() {
		return _class('shop_payment_callback', 'modules/shop/')->payment_callback();
	}

	function _format_price($price = 0) {
		return _class('shop__format_price', 'modules/shop/')->_format_price($price);
	}

	function _hidden_field($name = '', $value = '') {
		return _class('shop__hidden_field', 'modules/shop/')->_hidden_field($name, $value);
	}

	function _product_id_url($product_info = array()) {
		return _class('shop__product_id_url', 'modules/shop/')->_product_id_url($product_info);
	}

	function _product_get_price($product_info = array()) {
		return _class('shop__product_get_price', 'modules/shop/')->_product_get_price($product_info);
	}

	function _get_group_prices($product_ids = array()) {
		return _class('shop__get_group_prices', 'modules/shop/')->_get_group_prices($product_ids);
	}

	function _search_form_short() {
		return _class('shop__search_form_short', 'modules/shop/')->_search_form_short();
	}

	function search() {
		return _class('shop_search', 'modules/shop/')->search();
	}

	function _categories_show() {
		return _class('shop__categories_show', 'modules/shop/')->_categories_show();
	}

	function _products_bestsellers() {
		return _class('shop__products_bestsellers', 'modules/shop/')->_products_bestsellers();
	}

	function _products_last_viewed() {
		return _class('shop__products_last_viewed', 'modules/shop/')->_products_last_viewed();
	}

	function _products_get_attributes($products_ids = array()) {
		return _class('shop__products_get_attributes', 'modules/shop/')->_products_get_attributes($products_ids);
	}

	function _get_select_attributes($atts = array()) {
		return _class('shop__get_select_attributes', 'modules/shop/')->_get_select_attributes($atts);
	}

	function _box($name = '', $selected = '') {
		return _class('shop__box', 'modules/shop/')->_box($name, $selected);
	}

	function _site_title($title) {
		return _class('shop__site_title', 'modules/shop/')->_site_title($title);
	}

	function _hook_meta_tags($meta) {
		return _class('shop__hook_meta_tags', 'modules/shop/')->_hook_meta_tags($meta);
	}

	function _site_map_items($SITE_MAP_OBJ = false) {
		return _class('shop__site_map_items', 'modules/shop/')->_site_map_items($SITE_MAP_OBJ);
	}

	function _nav_bar_items($params = array()) {
		return _class('shop__nav_bar_items', 'modules/shop/')->_nav_bar_items($params);
	}

	function _get_children_cat($id) {
		return _class('shop__get_children_cat', 'modules/shop/')->_get_children_cat($id);
	}

	function _manufacturer_show() {
# TODO: redo current manufacturers
#		return _class('shop__manufacturer_show', 'modules/shop/')->_manufacturer_show();
	}

	function manufacturer() {
// TODO: show products by given manufacturer
	}

	function manufacturers() {
// TODO: show list of manufacturers
	}

	function supplier() {
// TODO: show products by given supplier
	}

	function suppliers() {
// TODO: show list of suppliers
	}

	function product_set() {
// TODO: show details of given product set
	}

	function product_sets() {
// TODO: show list of product set
	}

	function category() {
// TODO: show category contents
	}
}

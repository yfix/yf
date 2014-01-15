<?php

/**
* Special panel for the supplier
*/
//load('manage_shop', 'framework');
class yf_shop_supplier_panel /*extends yf_manage_shop*/ {

	/**
	*/
	function _init() {
		$this->SUPPLIER_ID = (int)db()->get_one('SELECT supplier_id FROM '.db('shop_admin_to_supplier').' WHERE admin_id='.intval(main()->ADMIN_ID));
	}

	/**
	*/
	function show () {
		return form()
			->link('Products', './?object='.$_GET['object'].'&action=products')
			->link('Orders', './?object='.$_GET['object'].'&action=orders')
			->link('Category mapping', './?object='.$_GET['object'].'&action=category_mapping')
			->link('Import XLS', './?object='.$_GET['object'].'&action=import_xls')
			->link('Upload images', './?object='.$_GET['object'].'&action=upload_images')
		;
	}
	
	function _product_image_delete($id, $k) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_images', 'admin_modules/'.$cl.'/')->$func($id, $k);
	}
	
	function _product_image_upload($product_id) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_images', 'admin_modules/'.$cl.'/')->$func($product_id);
	}
		
	function product_image_delete() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_images', 'admin_modules/'.$cl.'/')->$func();
	}	

	function set_main_image() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_images', 'admin_modules/'.$cl.'/')->$func();
	}	

	function products($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_products', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function product_edit($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_products', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function product_add($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_products', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function product_delete($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_products', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function orders($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_orders', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function order_view($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_orders', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function category_mapping($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_categories', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function category_mapping_add($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_categories', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function category_mapping_edit($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_categories', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function category_mapping_delete($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_categories', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function import_xls($params = array()) {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_import', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function import_products() {
		return $this->import_xls();
	}

	function filter_save() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_filter', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function upload_images() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_upload_images', 'admin_modules/'.$cl.'/')->$func($params);
	}
	function _show_filter() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_filter', 'admin_modules/'.$cl.'/')->$func($params);
	}

}

<?php

/**
* Special panel for the supplier
*/
class yf_shop_contentm_panel {

	/**
	*/
	function show () {
		return form()
			->link('Products', './?object='.$_GET['object'].'&action=products')
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

	function filter_save() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_filter', 'admin_modules/'.$cl.'/')->$func($params);
	}

	function upload_images() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_upload_images', 'admin_modules/'.$cl.'/')->$func($params);
	}
	/**
	* Hook
	*/
	function _show_filter() {
		$func = __FUNCTION__; $cl = $_GET['object']; return _class($cl.'_filter', 'admin_modules/'.$cl.'/')->$func($params);
	}
}

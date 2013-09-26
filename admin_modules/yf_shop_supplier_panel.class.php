<?php

/**
* Special panel for the supplier
*/
class yf_shop_supplier_panel {

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
		;
	}

	function products($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_products', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function product_edit($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_products', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function product_add($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_products', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function product_delete($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_products', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function orders($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_orders', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function order_view($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_orders', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function category_mapping($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_categories', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function category_mapping_add($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_categories', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function category_mapping_edit($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_categories', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function category_mapping_delete($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_categories', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function import_xls($params = array()) {
		$func = __FUNCTION__; return _class(__CLASS__.'_import', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	function import_products() {
		return $this->import_xls();
	}

	function filter_save() {
		$func = __FUNCTION__; return _class(__CLASS__.'_filter', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}

	/**
	* Hook
	*/
	function _show_filter() {
		$func = __FUNCTION__; return _class(__CLASS__.'_filter', 'admin_modules/'.__CLASS__.'/')->$func($params);
	}
}

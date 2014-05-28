<?php

/**
* Special panel for the supplier
*/
load('manage_shop', 'framework');
class yf_shop_supplier_panel extends yf_manage_shop {

	/**
	*/
	function show () {
		return form()
			->link('Products', './?object=manage_shop&action=products')
			->link('Orders', './?object=manage_shop&action=orders')
			->link('Category mapping', './?object=manage_shop&action=category_mapping')
			->link('Import XLS', './?object=manage_shop&action=import_xls')
			->link('Upload images', './?object=manage_shop&action=upload_images')
		;
	}
}

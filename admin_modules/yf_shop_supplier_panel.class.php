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
			->link('Products', './?object='.$_GET['object'].'&action=products')
			->link('Orders', './?object='.$_GET['object'].'&action=orders')
			->link('Category mapping', './?object='.$_GET['object'].'&action=category_mapping')
			->link('Import XLS', './?object='.$_GET['object'].'&action=import_xls')
			->link('Upload images', './?object='.$_GET['object'].'&action=upload_images')
		;
	}
}

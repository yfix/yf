<?php

class table2_new_controls {
	function show() {
/*
		$sql = 'SELECT * FROM '.db('countries').' ORDER BY name ASC';
		return table($sql)
			->text('code', array('width' => '5%'))
			->text('name')
		;
*/
		return table('SELECT * FROM '.db('shop_products'), array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))
			->image('id', 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_thumb.jpg', array('width' => '50px'))
			->text('name')
			->link('cat_id', './?object=category_editor&action=show_items&&id=%d', _class('cats')->_get_items_names('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object=manage_shop&action=product_delete&id=%d')
			->btn_clone('', './?object=manage_shop&action=product_clone&id=%d')
			->btn_active('', './?object=manage_shop&action=product_activate&id=%d')
			->footer_add('Add product', './?object=manage_shop&action=product_add',array('no_ajax' => 1))
			->footer_link('Attributes', './?object=manage_shop&action=attributes')
			->footer_link('Categories', './?object=category_editor&action=show_items&id=shop_cats')
			->footer_link('Orders', './?object=manage_shop&action=show_orders');
	}
}

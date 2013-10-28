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
		$values = array('', 'k1' => 'v1', 'k2' => 'v2');

		return table('SELECT * FROM '.db('shop_products'), array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))

			->check_box('id', array('header_tip' => 'This is checkbox'))
			->select_box('id', array('values' => $values, 'selected' => 'k1', 'tip' => 'Checkbox value tip', 'nowrap' => 1))
			->radio_box('id')
			->input('id')

			->image('id', 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_thumb.jpg', array('width' => '50px'))
			->text('name')
			->link('cat_id', './?object=category_editor&action=show_items&&id=%d', _class('cats')->_get_items_names('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date')
			->btn_edit(array('icon' => 'icon-star'))
			->btn_delete()
			->btn_clone()
			->btn_active()
			->footer_add()
		;
	}
}

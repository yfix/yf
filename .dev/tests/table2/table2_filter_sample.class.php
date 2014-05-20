<?php

class table2_filter_sample {
	function show() {
		$values = array('', 'k1' => 'v1', 'k2' => 'v2');

		$f = __DIR__.'/products_data.json';
		// $data = db()->get_all('SELECT * FROM '.db('shop_products').' WHERE cat_id=10077 ORDER BY RAND() LIMIT 20');
		// file_put_contents($f, json_encode($data));
		$data = json_decode(file_get_contents($f), true);
		return table($data, array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))
/*
			->check_box('id', array('header_tip' => 'This is checkbox'))
			->select_box('id', array('values' => $values, 'selected' => 'k1', 'tip' => 'Checkbox value tip', 'nowrap' => 1, 'class' => 'input-small'))
			->radio_box('id')
			->input('id', array('class' => 'input-small'))
*/
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

	function _show_filter () {
		return module('form2_filter_sample')->show();
	}
}

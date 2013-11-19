<?php

class table2_new_controls {
	function show() {
		$values = array('', 'k1' => 'v1', 'k2' => 'v2');

		return table('SELECT *, "4" AS stars FROM '.db('shop_products'))
			->stars('stars')

			->check_box('id', array('header_tip' => 'This is checkbox'))
			->select_box('id', array('values' => $values, 'selected' => 'k1', 'tip' => 'Checkbox value tip', 'nowrap' => 1, 'class' => 'input-small'))
			->radio_box('id')
			->input('id', array('class' => 'input-small'))

			->image('id', 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_thumb.jpg', array('width' => '50px'))
			->text('name')
			->link('cat_id', './?object=category_editor&action=show_items&&id=%d', _class('cats')->_get_items_names('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date')
			->date('add_date', array('format' => '%y-%m-%d %H:%M'))
			->btn_edit(array('icon' => 'icon-star'))
			->btn_delete()
			->btn_clone()
			->btn_active()
			->footer_add()
		;
	}
}

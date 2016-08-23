<?php

return function() {
	$data = json_decode(file_get_contents(__DIR__.'/products.json'), true);
	return table($data, ['rotate_table' => 1])
		->image('id', 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_thumb.jpg', ['width' => '50px'])
		->text('name')
		->link('cat_id', url('/@object/@action/%d'), _class('cats')->_get_items_names('shop_cats'))
		->text('price')
		->text('quantity')
		->date('add_date')
		->date('add_date', ['format' => '%y-%m-%d %H:%M'])
		->btn_edit(['icon' => 'icon-star'])
		->btn_delete()
		->btn_clone()
		->btn_active()
		->footer_add()
	;
};

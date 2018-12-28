<?php

return function () {
    $data = json_decode(file_get_contents(__DIR__ . '/products.json'), true);
    foreach ((array) $data as $k => $v) {
        $data[$k]['stars'] = rand(1, 5);
        $data[$k]['stars_big'] = rand(10, 40);
    }
    return table($data)
        ->stars('stars')
        ->stars('stars_big', ['stars' => 5, 'max' => 100])

        ->check_box('id', ['header_tip' => 'This is checkbox'])
        ->select_box('id', ['values' => ['', 'k1' => 'v1', 'k2' => 'v2'], 'selected' => 'k1', 'tip' => 'Checkbox value tip', 'nowrap' => 1, 'class' => 'input-small'])
        ->radio_box('id')
        ->input('id', ['class' => 'input-small'])

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
        ->footer_add();
};

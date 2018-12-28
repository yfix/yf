<?php

class yf_shop_basket_main
{
    /**
     * basket_main.
     */
    public function basket_main()
    {
        $products_ids = [];
        $basket_contents = module('shop')->_basket_api()->get_all();
        foreach ((array) $basket_contents as $_item_id => $_info) {
            if ($_info['product_id']) {
                $products_ids[$_info['product_id']] = $_info['product_id'];
            }
        }
        if ( ! empty($products_ids)) {
            $products_infos = db()->query_fetch_all('SELECT * FROM ' . db('shop_products') . " WHERE active='1' AND id IN(" . implode(',', $products_ids) . ')');
            $products_atts = module('shop')->_products_get_attributes($products_ids);
            $group_prices = module('shop')->_get_group_prices($products_ids);
        }
        $total_price = 0;
        foreach ((array) $products_infos as $_info) {
            $_product_id = $_info['id'];
            $_info['_group_price'] = $group_prices[$_product_id][module('shop')->USER_GROUP];
            $quantity2 = $basket_contents[$_info['id']]['quantity'];
            $price = module('shop')->_product_get_price($_info);
            $dynamic_atts = [];
            foreach ((array) $products_atts[$_product_id] as $_attr_id => $_attr_info) {
                if ($basket_contents[$_product_id]['atts'][$_attr_info['name']] == $_attr_info['value']) {
                    $dynamic_atts[$_attr_id] = '- ' . $_attr_info['name'] . ' ' . $_attr_info['value'];
                    $price += $_attr_info['price'];
                }
            }
            $total_price += $price * $quantity2;
            $quantity += (int) $quantity2;
        }
        $replace = [
            'total_price' => module('shop')->_format_price($total_price),
            'currency' => _prepare_html(module('shop')->CURRENCY),
            'quantity' => $quantity,
            'order_link' => './?object=shop&action=basket',
            'basket_link' => './?object=shop&action=basket',
        ];
        return tpl()->parse('shop/basket_main', $replace);
    }
}

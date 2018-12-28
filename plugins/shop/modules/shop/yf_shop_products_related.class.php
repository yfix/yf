<?php

class yf_shop_products_related
{
    public function products_related($id = '')
    {
        $product_related_data = [];
        $sql = 'SELECT * FROM ' . db('shop_product_related') . ' WHERE product_id = ' . $id;
        $product = db()->query($sql);
        while ($A = db()->fetch_assoc($product)) {
            $product_related_id .= $A['related_id'] . ',';
        }
        $product_related_id = rtrim($product_related_id, ',');
        if ($product_related_id != '') {
            $product_related = module('shop')->products_show($product_related_id);
        }
        return $product_related;
    }
}

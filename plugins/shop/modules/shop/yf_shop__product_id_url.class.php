<?php

class yf_shop__product_id_url
{
    public function _product_id_url($product_info = [])
    {
        return strlen($product_info['url']) ? $product_info['url'] : $product_info['id'];
    }
}

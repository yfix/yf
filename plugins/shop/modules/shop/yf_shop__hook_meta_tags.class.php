<?php

class yf_shop__hook_meta_tags
{
    public function _hook_meta_tags($meta)
    {
        $subtitle = null;
        if (in_array(($_GET['action'] ?? null), ['show', 'products_show']) && ($_GET['id'] ?? null)) {
            $subtitle .= module('shop')->_shop_cats[($_GET['id'] ?? null)];
        } elseif (in_array(($_GET['action'] ?? null), ['product_details']) /* && $_GET["id"] */) {
            $meta['keywords'] = module('shop')->_product_info['meta_keywords'];
            $meta['description'] = module('shop')->_product_info['meta_desc'];
        }
        return $meta;
    }
}

<?php

class yf_shop__basket_save
{
    /**
     * Save basket.
     */
    public function _basket_save()
    {
        if ( ! empty($_POST['quantity']) && ! module('shop')->_basket_is_processed) {
            module('shop')->_basket_api()->clean();
            $products_ids = [];
            foreach ((array) $_POST['quantity'] as $_product_id => $_quantity) {
                $_product_id = (int) $_product_id;
                $_quantity = (int) $_quantity;
                if ($_product_id && $_quantity) {
                    module('shop')->_basket_api()->set($_product_id, [
                        'product_id' => $_product_id,
                        'quantity' => $_quantity,
                        'atts' => $_POST['atts'][$_product_id],
                    ]);
                }
            }
            // Prevent double processing
            module('shop')->_basket_is_processed = true;
        }
    }
}

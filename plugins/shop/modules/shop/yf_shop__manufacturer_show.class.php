<?php

class yf_shop__manufacturer_show
{
    public function _manufacturer_show()
    {
        // Prepare manufacturer
        $replace = [
            'brand' => module('shop')->_manufacturer,
            'manufacturer_box' => common()->select_box('manufacturer', module('shop')->_man_for_select, $_SESSION['man_id'], false, 2),
            'url_manufacturer' => process_url('./?object=shop&action=products_show'),
        ];
        unset($_SESSION['man_id']);
        return tpl()->parse('shop/manufacturer', $replace);
    }
}

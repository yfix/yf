<?php

class yf_shop__format_price
{
    public function _format_price($price = 0)
    {
        $price = number_format($price, 2, '.', ' ');
        if (module('shop')->CURRENCY == '$') {
            return module('shop')->CURRENCY . '&nbsp;' . $price;
        }
        return $price . '&nbsp;' . module('shop')->CURRENCY;
    }
}

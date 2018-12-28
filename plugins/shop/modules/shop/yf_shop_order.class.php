<?php

class yf_shop_order
{
    public function order()
    {
        $_avail_steps = [
            'start',
            'delivery',
            'select_payment',
            'do_payment',
            'finish',
        ];
        $step = $_GET['id'];
        if ( ! $step || ! in_array($step, $_avail_steps)) {
            $step = 'start';
        }
        // Prevent ordering with empty shopping basket
        $basket_contents = module('shop')->_basket_api()->get_all();
        if (empty($basket_contents) && in_array($step, ['start', 'delivery', 'select_payment'])) {
            return js_redirect('./?object=shop');
        }
        $func = '_order_step_' . $step;
        return module('shop')->$func();
    }
}

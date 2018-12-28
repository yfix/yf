<?php

class yf_shop_order_validate_data
{
    /**
     * validate order data for view order.
     */
    public function _order_validate_data()
    {
        if (empty($_POST['order_id'])) {
            _re('Order empty');
        }
        $order_info = db()->query_fetch('SELECT * FROM ' . db('shop_orders') . ' WHERE id=' . (int) ($_POST['order_id']));
        if (empty($order_info)) {
            _re('No such order');
        }
        if (empty($_POST['email'])) {
            _re('e-mail empty');
        } elseif ( ! common()->email_verify($_POST['email'])) {
            _re('email not valid.');
        } elseif ($order_info['email'] != $_POST['email']) {
            _re('The order has been issued on other name');
        }
    }
}

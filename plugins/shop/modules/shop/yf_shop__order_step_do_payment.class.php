<?php

class yf_shop__order_step_do_payment
{
    /**
     * Order step.
     * @param mixed $FORCE_DISPLAY_FORM
     */
    public function _order_step_do_payment($FORCE_DISPLAY_FORM = false)
    {
        $basket_contents = module('shop')->_basket_api()->get_all();

        if (module('shop')->FORCE_PAY_METHOD) {
            $_POST['pay_type'] = module('shop')->FORCE_PAY_METHOD;
        }
        // Show previous form if needed
        if (common()->_error_exists() || empty($_POST)) {
            return module('shop')->_order_step_select_payment();
        }
        $ORDER_ID = (int) ($_POST['order_id'] ? $_POST['order_id'] : module('shop')->_CUR_ORDER_ID);
        if (empty($ORDER_ID)) {
            _re('Missing order ID');
        }
        // Get order info
        $order_info = db()->query_fetch('SELECT * FROM ' . db('shop_orders') . ' WHERE id=' . (int) $ORDER_ID . ' AND user_id=' . (int) (main()->USER_ID) . " AND status='pending'");
        if (empty($order_info['id'])) {
            _re('Missing order record');
        }
        // Payment by courier, skip next step
        if ( ! common()->_error_exists() && $_POST['pay_type'] == 1 or $_POST['pay_type'] == 3 or $_POST['pay_type'] == 4) {
            module('shop')->_basket_api()->clean();
            return js_redirect('./?object=shop&action=' . $_GET['action'] . '&id=finish&page=' . (int) $ORDER_ID);
        }
        // Authorize.net payment type
        if ($_POST['pay_type'] == 2) {
            module('shop')->_basket_api()->clean();
            return module('shop')->_order_pay_authorize_net($order_info);
        }
    }
}

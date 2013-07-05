<?php
class yf_shop__order_step_select_payment{

	/**
	* Order step
	*/
	function _order_step_select_payment($FORCE_DISPLAY_FORM = false) {
		$cart = &$_SESSION["SHOP_CART"];
		// Show previous form if needed
		if (common()->_error_exists() || empty($_POST)) {
			return module('shop')->_order_step_delivery();
		}
		if (module('shop')->FORCE_PAY_METHOD) {
			$_POST["pay_type"] = module('shop')->FORCE_PAY_METHOD;
			$FORCE_DISPLAY_FORM = false;
		}
		if (!empty($_POST) && !$FORCE_DISPLAY_FORM) {
			module('shop')->_order_validate_select_payment();
			// Verify products
			if (!common()->_error_exists()) {
				$ORDER_ID = module('shop')->_create_order_record();
				$ORDER_ID = intval($ORDER_ID);
			}
			// Order id is required to continue, check it again
			if (empty($ORDER_ID) && !common()->_error_exists()) {
				_re("SHOP: Error while creating `order`, please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				module('shop')->_CUR_ORDER_ID = $ORDER_ID;
				return module('shop')->_order_step_do_payment(true);
			}
		}
		$DATA = $_POST;
		if (!isset($DATA["pay_type"])) {
			$DATA["pay_type"] = key(module('shop')->_pay_types);
		}
		$hidden_fields = "";
		$hidden_fields .= module('shop')->_hidden_field("ship_type", $_POST["ship_type"]);
		foreach ((array)module('shop')->_b_fields as $_field) {
			$hidden_fields .= module('shop')->_hidden_field($_field, $_POST[$_field]);
		}
		/* foreach ((array)module('shop')->_s_fields as $_field) {
			$hidden_fields .= module('shop')->_hidden_field($_field, $_POST[$_field]);
		} */
		$hidden_fields .= module('shop')->_hidden_field('card_num', $_POST['card_num']);
		$hidden_fields .= module('shop')->_hidden_field('exp_date', $_POST['exp_date']);
		$replace = array(
			"form_action"	=> "./?object=shop&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"pay_type_box"	=> module('shop')->_box("pay_type", $DATA["pay_type"]),
			"hidden_fields"	=> $hidden_fields,
			"back_link"		=> "./?object=shop&action=order&id=delivery",
			"cats_block"	=> module('shop')->_show_shop_cats(),
		);
		return tpl()->parse("shop/order_select_payment", $replace);
	}
	
}
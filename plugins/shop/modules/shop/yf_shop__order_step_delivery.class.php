<?php
class yf_shop__order_step_delivery{

	/**
	* Order step
	*/
	function _order_step_delivery($FORCE_DISPLAY_FORM = false) {
		// Validate previous form
		if (main()->is_post() && !$FORCE_DISPLAY_FORM) {
			module('shop')->_order_validate_delivery();
			// Display next form if we have no errors
			if (!common()->_error_exists()) {
				return module('shop')->_order_step_select_payment(true);
			}
		}
		if (main()->USER_ID) {
			$order_info = module('shop')->_user_info;
		}
		// Fill fields
		foreach ((array)module('shop')->_b_fields as $_field) {
			$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : module('shop')->_user_info[substr($_field, 2)]);
					
		}
		// Fill shipping from billing
	 	foreach ((array)module('shop')->_s_fields as $_field) {
			if (module('shop')->_user_info["shipping_same"] && !isset($_POST[$_field])) {
				$s_field = "b_".substr($_field, 2);
				$replace[$_field] = _prepare_html(isset($_POST[$s_field]) ? $_POST[$s_field] : module('shop')->_user_info[$s_field]);
			} else {
				$replace[$_field] = _prepare_html(isset($_POST[$_field]) ? $_POST[$_field] : module('shop')->_user_info[$_field]);
			}
		}
		$force_ship_type = module('shop')->FORCE_GROUP_SHIP[module('shop')->USER_GROUP];

		$SELF_METHOD_ID = substr(__FUNCTION__, strlen("_order_step_"));

		$replace = my_array_merge((array)$replace, array(
			"form_action"	=> "./?object=shop&action=".$_GET["action"]."&id=".$SELF_METHOD_ID,
			"error_message"	=> _e(),
			"ship_type_box"	=> module('shop')->_box("ship_type", $force_ship_type ? $force_ship_type : $_POST["ship_type"]),
			"back_link"		=> "./?object=shop&action=order",
			"cats_block"	=> module('shop')->_categories_show(),
		));
		return tpl()->parse("shop/order_delivery", $replace);
	}
	
}
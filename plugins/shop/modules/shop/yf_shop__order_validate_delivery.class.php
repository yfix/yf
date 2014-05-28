<?php
class yf_shop__order_validate_delivery{

	/**
	* Order validation
	*/
	function _order_validate_delivery() {
		$_POST['exp_date'] = $_POST['exp_date_mm']. $_POST['exp_date_yy'];

		$force_ship_type = module('shop')->FORCE_GROUP_SHIP[module('shop')->USER_GROUP];
		if ($force_ship_type) {
			$_POST["ship_type"] = $force_ship_type;
		}
		if (!strlen($_POST["ship_type"]) || !isset(module('shop')->_ship_types[$_POST["ship_type"]])) {
			_re("Shipping type required");
		}
		foreach ((array)module('shop')->_b_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, module('shop')->_required_fields)) {
				_re(t(str_replace("b_", "Billing ", $_field))." ".t("is required"));
			}
		}
		if ($_POST["email"] != "" && !common()->email_verify($_POST["email"])) {
			_re("email not valid.");
		}
		/* foreach ((array)module('shop')->_s_fields as $_field) {
			if (!strlen($_POST[$_field]) && in_array($_field, module('shop')->_required_fields)) {
				_re(t(str_replace("s_", "Shipping ", $_field))." ".t("is required"));
			}
		}
		if (!common()->email_verify($_POST["s_email"])) {
				_re("Shipping email not valid.");
			} */
	}
	
}
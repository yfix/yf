<?php
class yf_shop__order_validate_select_payment{

	/**
	* Order validation
	*/
	function _order_validate_select_payment() {
		module('shop')->_order_validate_delivery();
		if (!$_POST["pay_type"] || !isset(module('shop')->_pay_types[$_POST["pay_type"]])) {
			_re("Wrong payment type");
		}
	}
	
}
<?php
class yf_shop__order_validate_do_payment{

	/**
	* Order validation
	*/
	function _order_validate_do_payment() {
		module('shop')->_order_validate_select_payment();
		if (empty($_POST["order_id"])) {
			_re("Missing order ID");
		}
		if (empty($_POST["total_sum"])) {
			_re("Missing total sum");
		}
	}
	
}
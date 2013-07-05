<?php
class yf_shop_payment_callback{

	function payment_callback() {
//		main()->NO_GRAPHICS = true;
		_debug_log(print_r($_POST, 1));
	}
	
}
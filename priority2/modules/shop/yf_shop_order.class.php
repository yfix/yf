<?php
class yf_shop_order{

	function order() {
		if (!module("shop")->USER_ID) {
// TODO
//			if (!module("shop")->INLINE_REGISTER) {
//			} else {
//				return _error_need_login("./?object=shop&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""). ($_GET["page"] ? "&page=".$_GET["page"] : ""));
//			}
		}
		$_avail_steps = array(
			"start",
			"delivery",
			"select_payment",
			"do_payment",
			"finish",
		);
		// Switch between checkout steps
		$step = $_GET["id"];
		if (!$step || !in_array($step, $_avail_steps)) {
			$step = "start";
		}
		// Prevent ordering with empty shopping cart
		$cart = &$_SESSION["SHOP_CART"];
		if (empty($cart) && in_array($step, array("start", "delivery", "select_payment"))) {
			return js_redirect("./?object=shop");
		}
		$func = "_order_step_". $step;
		return module("shop")->$func();
	}
	
}
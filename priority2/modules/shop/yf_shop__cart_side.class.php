<?php
class yf_shop__cart_side{

	/**
	* Display cart contents (usually for side block)
	*/
	function _cart_side() {
		return $this->cart(array("STPL" => "shop/cart_side"));
	}
	
}
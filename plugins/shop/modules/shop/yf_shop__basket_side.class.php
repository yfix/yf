<?php
class yf_shop__basket_side{

	/**
	* Display basket contents (usually for side block)
	*/
	function _basket_side() {
		return $this->basket(["STPL" => "shop/basket_side"]);
	}
	
}
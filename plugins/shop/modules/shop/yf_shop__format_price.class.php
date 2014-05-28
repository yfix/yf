<?php
class yf_shop__format_price{

	function _format_price($price = 0) {
		$price = number_format($price, 2, '.', ' ');
		if (module("shop")->CURRENCY == "\$") {
			return module("shop")->CURRENCY."&nbsp;".$price;
		} else {
			return $price."&nbsp;".module("shop")->CURRENCY;
		}
	}

}
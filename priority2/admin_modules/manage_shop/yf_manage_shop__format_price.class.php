<?php
class yf_manage_shop__format_price{

	function _format_price($price = 0) {
		if (module("manage_shop")->CURRENCY == "\$") {
			return module("manage_shop")->CURRENCY."&nbsp;".$price;
		} else {
			return $price."&nbsp;".module("manage_shop")->CURRENCY;
		}
	}
	
}
<?php
class yf_shop_show{

	function show() {
		return tpl()->parse("shop/home");
#		return module("shop")->products_show(1);
	}
	
}
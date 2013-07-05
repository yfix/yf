<?php
class yf_shop_show{

	function show() {
		return module("shop")->products_show(1);
	}
	
}
<?php
class yf_shop_show{

	function show() {
		return module("shop")->show_products(1);
	}
	
}
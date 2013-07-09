<?php
class yf_manage_shop_orders_manage{

	function orders_manage() {
		return module("manage_shop")->show_orders();
	}
	
}
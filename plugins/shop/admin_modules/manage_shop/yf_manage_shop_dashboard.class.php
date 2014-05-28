<?php
class yf_manage_shop_dashboard{

	function dashboard () {
		return module('manage_dashboards')->display('manage_shop');
	}
	
}
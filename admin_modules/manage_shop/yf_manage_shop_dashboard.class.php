<?php
class yf_manage_shop_dashboard{

	function dashboard () {
		return module('manage_dashboards')->home('manage_shop');
	}
	
}
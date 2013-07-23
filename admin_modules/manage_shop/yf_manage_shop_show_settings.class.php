<?php
class yf_manage_shop_show_settings{

	function show_settings() {
		$replace = array(
			"items"		=> "settings",
		);
		return tpl()->parse("manage_shop/settings_show", $replace);
	}
	
}